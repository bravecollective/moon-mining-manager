# AGENTS.md

Conventions for anyone who changes this repo, human or agent. Read it before you write code. It records the decisions you cannot recover from the tree. That includes the places where the code looks wrong and is deliberately right.

## 1. What this is

Moon Mining Manager bills EVE Online players for moon mining. It runs two revenue streams that share almost nothing. The first stream is a weekly mining tax. The app charges each player for the ore that player mined. The second stream is a monthly rent. The app charges the holder of each moon. The two streams have separate invoices, templates, schedules and jobs.

The app finds payments in the corporation wallet. It polls the wallet for ISK donations and matches them against open invoices. There is no payment gateway and no callback. The wallet poll is the whole payment path. `Invoice` and `RentalInvoice` are not duplicated code. Neither are `Payment` and `RentalPayment`. Do not offer to merge them.

## 2. Domain vocabulary

| Term | What must be written |
|---|---|
| Moon | Row in `moons`. Up to four ore types in `mineral_1_type_id`..`mineral_4_type_id`, each with a matching `mineral_N_percent`. Denormalized into four column pairs on purpose. Do not normalize it. `Moon::MINERAL_COLUMNS` holds the four column names. |
| Goo / moon material | The 20 moon-mining materials, in rarity tiers that set the rent. The canonical list is `Moon::MOON_MATERIALS`, in display order. `MoonSeeder::GOO_TIERS` holds the same 20 names in tier groups. |
| Refinery | An Athanor or a Tatara. Row in `refineries`. Mining data keys on `observer_id`, the ESI mining-observer ID, and not on `id`. `Refinery::mining_activity()` joins `mining_activities.refinery_id` to `refineries.observer_id`. |
| Extraction / chunk | A scheduled moon pull. Three columns on `refineries` hold the timers, not a separate table: `extraction_start_time`, then `chunk_arrival_time`, then `natural_decay_time`. The timers page lets a player claim one. |
| Rent corp vs tax corp | Two separate EVE corporations. Configure them independently with `RENT_CORPORATION_ID` and `TAX_CORPORATION_ID`. The schedule runs most polling jobs twice, once for each corporation. Code that assumes one corporation is wrong. |
| Prime user | The character whose ESI refresh token makes the API calls for a corporation. The character must be a director. If `RENT_CORPORATION_PRIME_USER_ID` or `TAX_CORPORATION_PRIME_USER_ID` is unset, `Console\Kernel::schedule()` skips that corporation's whole job block. There is no error and no log line. See `app/Console/Kernel.php:50` and `app/Console/Kernel.php:67`. |
| Whitelist | The `whitelist` table is the whole authorization model. `is_admin = 1` grants admin. Every other user gets the `login` routes only. There are no gates, policies or roles. |
| Renter | Row in `renters`. The table keeps history, so it is much larger than the number of moons rented today. `renters.moon_id` has no index. |
| Templates | The `templates` database table holds the invoice and notification mail bodies. It keys them by `name`: `weekly_invoice`, `receipt`, `renter_invoice`, `renter_notification` and `renter_reminder`. They are not in `resources/views/`. An edit to a Blade file does not change an invoice email. |
| SDE / Fuzzwork | `invTypes`, `invTypeMaterials`, `invUniqueNames`, `mapSolarSystems` and `mapRegions`. Imported whole from third-party SQL dumps. They have no migrations, by design. Never write one against them. |
| Evemail | In-game mail that the `SendEvemail` job sends through ESI. It is not SMTP. The only SMTP mail in the app is the rate-limiter alert to `ADMIN_EMAIL`. |

## 3. Architecture map

- `app/Jobs/` holds 30 files and does most of the work. Plural and singular pairs are a fan-out convention. `PollRefineries` queues one `PollRefinery` for each refinery. `GenerateInvoices`/`GenerateInvoice`, `UpdateMaterialValues`/`UpdateMaterialValue` and `CorporationChecks`/`CorporationCheck` use the same shape. Keep that shape when you add work.
- `app/Classes/` holds the only two service classes, `EsiConnection` and `CalculateRent`. `EsiConnection` is the single ESI entry point. Take an `Eseye` client from `EsiConnection::getConnection()` instead of building one.
- `app/Console/Kernel.php` is the whole schedule. There are no event listeners. `EventServiceProvider` still carries the stock Laravel `$listen` entry for `App\Events\Event`. Neither that event nor its listener exists.
- Controllers render Blade directly. There are no API routes, form requests, policies or resources. `routes/api.php` is the untouched Laravel stub.
- `App\Models\Log` is an Eloquent model over the `logs` table, and nothing in the codebase references it today. `Illuminate\Support\Facades\Log` is the logger, and the code uses it everywhere. Read the imports before you touch either name.
- All 24 models carry generated `@property`, `@method` and `@mixin \Eloquent` docblocks from `barryvdh/laravel-ide-helper`. Keep them correct after a schema change. Regenerate them with `php artisan ide-helper:models`.

## 4. Development environment

The README covers installation. This section covers the three traps that it does not.

1. **The EVE static data is not in the repo, and the app is broken without it.** `database/eve-static/*.sql` is gitignored. Download the five dumps from Fuzzwork, then run `php artisan eve:import-static-data`. Until you do, the moon list, extractions, taxes and reports pages all fail. Each of those pages joins `invTypes` and `mapRegions` to get the names it shows. The symptoms look exactly like application bugs. They are not.
2. **`eve:import-static-data` runs the `mysql` client binary through `Symfony\Component\Process`.** The `moon_php` image installs that binary, and a Windows host almost certainly does not have it. Run the command in the container.
3. **`.env.example` ships `DB_HOST=moon_db`, which resolves inside the compose network only.** From the host, use `127.0.0.1:3307`, the port that `docker-compose.yml` publishes. Do not "fix" `.env.example`.

After the static data import, `MOON_SEED_COUNT=1000 php artisan db:seed --class=MoonSeeder` gives you realistic data without a live ESI import. The seeder defaults to 100,000 moons, so set the variable unless you want that many. It truncates `moons` and `renters` first.

## 5. Commands

**Safe at any time:** `route:list`, `config:clear`, `ide-helper:models -N`, `php -l`, `tinker` for reads, `eve:import-static-data`, `db:seed --class=MoonSeeder`.

**Ask first:** `migrate`. Also `queue:work`, which runs whatever sits in the queue already, including ESI jobs. Also `schedule:run`, which dispatches whatever the clock makes due. Also `db:seed`, because the default seeder truncates.

**Never run `php artisan command:run-job <AnyJob>`.** `RunJob::handle()` builds the class and calls `$job->handle()` directly. See `app/Console/Commands/RunJob.php:38-56`. The call is synchronous and unqueued. There is no dry-run and no confirmation. Against `Poll*`, `SendEvemail`, `GenerateInvoice*`, `GenerateRent*`, `SendRenterDelinquencyList`, `CorporationCheck*`, `UpdateMaterialValue*` or `PostSlackMessage`, that means real requests to the ESI of CCP under the real token of the corporation. It also means real in-game mail to real players, or a real Slack post. The README documents it as an operator command. It is not a development command. Do not offer it as a verification step.

**Never run `migrate:fresh`, `migrate:refresh` or `db:wipe`.** They drop the five EVE static tables. Those tables have no migrations. The only way back is a new download and import of several hundred megabytes of SQL.

## 6. Code conventions

Treat this as a two-era codebase. That is the decision you face in every file you open.

> **Legacy.** Most of `app/Jobs/` and `app/Http/Middleware/`, plus the older models and controllers. Laravel 5 skeleton style: untyped properties and parameters with `@var` and `@param` docblocks, and snake_case locals. **Leave it alone.**
>
> **Current.** `app/Models/Moon.php`, `app/Http/Controllers/MoonController.php`, `app/Console/Commands/ImportEveStaticData.php` and `database/seeders/MoonSeeder.php`. PHP 8 `match`, arrow functions, `private const` lookup tables at the top of the class, parameter and return types. Docblocks only where they say something that the signature does not.
>
> **New code follows the current era. Do not modernize a file that you only pass through.**

The invariants, all visible in the tree:

- PSR-12, with one house deviation that carries meaning. **Align `=>` in array literals and `=` in assignment blocks by hand**, as in `MoonController::SORTS` and `ImportEveStaticData::handle()`. Keep the alignment.
- The codebase today has zero `declare(strict_types=1)`, zero `final` classes, zero typed properties and zero enums. Do not add any of them to an unrelated change.
- Eloquent relation methods use snake_case to match the attributes they front, such as `mineral_1()` and `mining_activity()`. This looks wrong and is intentional. A rename breaks eager-load strings and Blade property access silently.
- Read config through `config('eve.*')`. Never call `env()` outside `config/`. The commit "refactor(env): fix issue #32 by using proper config" was a deliberate cleanup, so do not regress it. `MoonSeeder` reads `MOON_SEED_COUNT` directly, and that development fixture is the single exception.

## 7. Comments and diff hygiene

Calibrate the comment budget against the examples already in the tree. In `MoonController`, the comment on `TOTAL_PERCENT` exists because that sort key has no backing column. The comment on `setRelation(new Collection())` exists because the line prevents an N+1 that the reader cannot see. Neither comment restates what its line does.

Do not write:

- Section-banner comments such as `// ---- Validation ----`.
- A comment above every block of a method you just wrote.
- Comments that narrate the change instead of the code, such as `// Added filtering here` or `// NEW:`.
- Docblocks on new code that only restate the signature.
- `@author` tags, `@since` tags, or dates.

> Rule of thumb: if a comment would be equally true and equally useful in any other Laravel project, delete it.

Diff hygiene. Do not reformat code or reorder imports in a file you do not otherwise change. Do not sweep whitespace or line endings. Do not rename or extract methods in a file you only pass through. Remove every `dd()`, `dump()` and `Log::info('here')` before you commit. Put unrelated problems you find in the pull request description, not in the diff. A reviewer must be able to read the whole diff in a couple of minutes. If yours is longer than that, split it.

## 8. Branches, commits, pull requests

The branch prefixes already in use are `feat/`, `fix/`, `refactor/` and `try/`. Keep the name short and kebab-case, and describe the change.

> **Never name a branch after the tool that produced it.** No `agent/`, `claude/`, `ai/`, `bot/` or `codex/`. A reviewer must not be able to tell from the branch name who or what typed the code.

Conventional-commit subjects are the dominant recent convention. Two examples from the history are `fix(miners): allow empty alliance / corp in .env` and `feat(eve-static): add artisan command to properly seed database`. Plain imperative subjects are also fine. Do not add a `Co-Authored-By` trailer for any AI, a "Generated with" footer, or emoji. The history has none of these, and the project did not ask for them.

One logical change per pull request. State it explicitly if the change touches the moon list, tax calculation, invoicing, or anything that shows a player an ISK figure.

## 9. Never without asking

1. **Schema changes.** Migrations run against a live production database with years of billing history. The parts that look like mistakes are not.
2. **Never migrate the EVE static tables.**
3. **No new dependencies**, composer or npm.
4. **No caching layer.** There is none today, and a staleness bug in a billing app is expensive. If a page is slow, fix the query. That is what #71 did, with server-side pagination and an antijoin instead of a cache.
5. **Never touch `vendor/`.**
6. **Never commit `.env`.** It holds live ESI client secrets. New settings go in `.env.example` and read through `config/eve.php`.
7. **Compiled front-end assets.** The repo commits `public/js/app.js` (1.6 MB), `public/css/app.css` and `public/mix-manifest.json` on purpose, because the release tarball ships `public/` without a Node build. If you changed nothing under `resources/assets/`, do not commit the output of `npm run production`. Run `git checkout -- public/js public/css public/mix-manifest.json` and continue. A rebuild-only diff is 1.6 MB of noise that hides the real change. If you did change `resources/assets/`, rebuild, commit the output in the same commit, and say so in the pull request. Never edit anything in `public/js/` by hand. `public/css/login.css` is hand-written rather than Mix output, and you may edit that one.
8. **No new front-end framework.** `package.json` lists `vue` and `vue-template-compiler`, and nothing imports them. There are zero `.vue` files and zero `Vue` references in `resources/`. Their presence is not permission to start Vue work.
9. **Do not modify `.gitlab-ci.yml`.** The build tars an allowlist of paths. If your change adds a root file that must ship, flag it in the pull request instead of an edit to the allowlist.

## 10. Verifying a change

> There is almost no test suite. `tests/` holds four files. `tests/Unit/ExampleTest.php` asserts `true`, and `tests/Feature/ExampleTest.php` asserts one redirect. Only `MinerSearchTest` and `GenerateInvoicesTest` exercise real behavior. Both build their own schema in an in-memory SQLite database, because there are no factories. A green PHPUnit run is not evidence that anything works.

Most routes sit behind the `login` or `admin` middleware. Both need an authenticated EVE character, which needs a registered EVE application and a real character. The only routes without middleware are `/login`, `/admin`, `/search`, `/sso`, `/admin-sso`, `/callback` and `/logout`. **Do not** comment out middleware, add a local-only bypass, or edit `routes/web.php` to see a page. Ask instead.

Verify in this order. State in the pull request description which steps you did.

1. `php -l` on every changed file.
2. `vendor/bin/phpstan analyse`. Larastan runs at level 0 over `app/`, `database/`, `routes/` and `tests/`. `phpstan-baseline.neon` holds the errors that exist already. Your change must add none. Do not regenerate the baseline to bury one.
3. `php artisan route:list`. This boots the container, the providers, the config and every route. It catches syntax errors, bad imports, broken controller references and provider mistakes. It is the cheapest real signal in the repo.
4. For a query or model change, run the exact builder chain in `php artisan tinker`. Paste the row counts and the `->toSql()` output into the pull request.
5. For a data-heavy page, seed at scale with `MOON_SEED_COUNT=100000`. Look for N+1 queries, not only for correct output.
6. For a money path, write a worked example in the pull request with the inputs, the expected ISK and the actual ISK. The money paths are `app/Classes/CalculateRent.php`, `GenerateInvoice*`, `GenerateRental*`, `ProcessMiningActivity` and `TaxController`. A wrong number here bills a real player the wrong amount.

## 11. If you are unsure

Stop and ask when the change needs a schema change.

Stop and ask when you cannot verify the change with the tools above.

Stop and ask when you inferred the EVE domain rule you encode instead of finding it in the code.

Stop and ask when the diff grows past a couple of hundred lines.

"I could not verify this" in a pull request description is a useful contribution. A confident wrong number is not.
