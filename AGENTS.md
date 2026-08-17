# AGENTS.md

Conventions for anyone changing this repo, human or agent. Read it before writing code — it records the decisions you can't recover by reading the tree, including the several places where the code looks wrong and is deliberately right.

## 1. What this is

Moon Mining Manager bills EVE Online players for moon mining, across two revenue streams that share almost nothing. The first is a weekly mining tax, charged per player against the ore they actually mined. The second is a monthly rent, charged per moon against whoever holds it. Separate invoices, separate templates, separate schedules, separate jobs.

Payment is detected by polling the corporation wallet for ISK donations and matching them against open invoices. There's no payment gateway and no callback — the wallet poll is the entire payment pipeline. `Invoice` and `RentalInvoice` aren't duplicated code, and neither are `Payment` and `RentalPayment`. Don't offer to merge them.

## 2. Domain vocabulary

| Term | What must be written |
|---|---|
| Moon | Row in `moons`. Up to four ore types in `mineral_1_type_id`..`mineral_4_type_id` with matching `mineral_N_percent`. Denormalised into four column pairs on purpose — don't normalise it. `Moon::MINERAL_COLUMNS` holds the four column names. |
| Goo / moon material | The 20 moon-mining materials, grouped into rarity tiers that drive rent. Canonical list is `Moon::MOON_MATERIALS`, in display order; `MoonSeeder::GOO_TIERS` has the same 20 grouped by tier. |
| Refinery | An Athanor or Tatara. Row in `refineries`, but keyed for mining data by `observer_id` — the ESI mining-observer ID — not by `id`. `Refinery::mining_activity()` joins `mining_activities.refinery_id` against `refineries.observer_id`. |
| Extraction / chunk | A scheduled moon pull. The timers are three columns on `refineries`, not a separate table: `extraction_start_time` → `chunk_arrival_time` → `natural_decay_time`. The timers page lets players claim one. |
| Rent corp vs tax corp | Two separate EVE corporations, configured independently (`RENT_CORPORATION_ID`, `TAX_CORPORATION_ID`). Most polling jobs are scheduled twice, once per corp. Code that assumes a single corporation is wrong. |
| Prime user | The character whose ESI refresh token makes a corporation's API calls. Must be a director. If `RENT_CORPORATION_PRIME_USER_ID` or `TAX_CORPORATION_PRIME_USER_ID` is unset, `Console\Kernel::schedule()` **silently skips that corporation's entire job block** (`app/Console/Kernel.php:50,67`). No error, no log. |
| Whitelist | The `whitelist` table is the entire authorisation model. `is_admin = 1` grants admin; otherwise a user is limited to the `login` routes. There are no gates, policies or roles. |
| Renter | Row in `renters`, which keeps history, so it's much larger than the count of currently-rented moons. `renters.moon_id` is not indexed. |
| Templates | Invoice and notification mail bodies live in the `templates` **database table**, keyed by `name`: `weekly_invoice`, `receipt`, `renter_invoice`, `renter_notification`, `renter_reminder`. Not in `resources/views/`. Editing a Blade file will not change an invoice email. |
| SDE / Fuzzwork | `invTypes`, `invTypeMaterials`, `invUniqueNames`, `mapSolarSystems`, `mapRegions`. Imported wholesale from third-party SQL dumps. **No migrations, by design.** Never write one against them. |
| Evemail | In-game mail sent through ESI (the `SendEvemail` job). Not SMTP. The only SMTP mail in the app is the rate-limiter alert to `ADMIN_EMAIL`. |

## 3. Architecture map

- `app/Jobs/` (30 files) is the workhorse layer, and plural/singular pairs are a fan-out convention: `PollRefineries` queues one `PollRefinery` per refinery, same for `GenerateInvoices`/`GenerateInvoice`, `UpdateMaterialValues`/`UpdateMaterialValue`, `CorporationChecks`/`CorporationCheck`. Keep that shape when adding work.
- `app/Classes/` holds the only two non-job service classes, `EsiConnection` and `CalculateRent`. `EsiConnection` is the sole ESI entry point — callers take an `Eseye` client from `EsiConnection::getConnection()` rather than constructing one.
- `app/Console/Kernel.php` is the whole schedule. There are no event listeners; `EventServiceProvider` still carries the stock Laravel `$listen` entry pointing at `App\Events\Event`, and neither that event nor its listener exists.
- Controllers render Blade directly. No API routes, no form requests, no policies, no resources. `routes/api.php` is the untouched Laravel stub.
- `App\Models\Log` is an Eloquent model over the `logs` table and nothing in the codebase currently references it; `Illuminate\Support\Facades\Log` is the logger and is used everywhere. Read the imports before touching either name.
- All 24 models carry generated `@property`/`@method`/`@mixin \Eloquent` docblocks from `barryvdh/laravel-ide-helper`. Keep them accurate after a schema change; regenerate with `php artisan ide-helper:models`.

## 4. Development environment

The README covers installation. These are the three traps it doesn't.

1. **The EVE static data isn't in the repo, and the app is broken without it.** `database/eve-static/*.sql` is gitignored. Download the five dumps from Fuzzwork, then run `php artisan eve:import-static-data`. Until you do, the moon list, extractions, taxes and reports pages all fail, because every one of them joins `invTypes` and `mapRegions` to get the names it displays. The symptoms look exactly like application bugs. They aren't.
2. **`eve:import-static-data` shells out to the `mysql` client binary** via `Symfony\Component\Process`. That binary is installed in the `moon_php` image and is almost certainly not on a Windows host. Run it in the container.
3. **`.env.example` ships `DB_HOST=moon_db`, which only resolves inside the compose network.** From the host, use `127.0.0.1:3307` — the port `docker-compose.yml` publishes. Don't "fix" `.env.example`.

Once the static data is in, `MOON_SEED_COUNT=1000 php artisan db:seed --class=MoonSeeder` gives you realistic data without a live ESI import. The seeder defaults to 100,000 moons, so set the variable unless that's what you want. It truncates `moons` and `renters` first.

## 5. Commands

**Safe any time:** `route:list`, `config:clear`, `ide-helper:models -N`, `php -l`, `tinker` for reads, `eve:import-static-data`, `db:seed --class=MoonSeeder`.

**Ask first:** `migrate`; `queue:work` (executes whatever is already queued, including ESI jobs); `schedule:run` (dispatches whatever the clock says is due); `db:seed` (the default seeder truncates).

**Never:** `php artisan command:run-job <AnyJob>`. `RunJob::handle()` instantiates the class and calls `$job->handle()` directly — synchronous, unqueued, no dry-run, no confirmation (`app/Console/Commands/RunJob.php:38-56`). Against `Poll*`, `SendEvemail`, `GenerateInvoice*`, `GenerateRent*`, `SendRenterDelinquencyList`, `CorporationCheck*`, `UpdateMaterialValue*` or `PostSlackMessage` that means real requests to CCP's ESI under the corporation's real token, real in-game mail to real players, or a real Slack post. The README documents it as an operator command. It is not a development command, and it must not be offered as a verification step.

Also forbidden outright: `migrate:fresh`, `migrate:refresh`, `db:wipe`. They drop the five EVE static tables, which have no migrations and can only be restored by re-downloading and re-importing several hundred MB of SQL.

## 6. Code conventions

Treat this as a two-era codebase, because that's the decision you actually face in every file you open.

> **Legacy** (most of `app/Jobs/`, `app/Http/Middleware/`, older models and controllers): Laravel 5 skeleton style. Untyped properties and params with `@var`/`@param` docblocks, snake_case locals. **Leave it alone.**
>
> **Current** (`app/Models/Moon.php`, `app/Http/Controllers/MoonController.php`, `app/Console/Commands/ImportEveStaticData.php`, `database/seeders/MoonSeeder.php`): PHP 8 `match`, arrow functions, `private const` lookup tables at the top of the class, parameter and return types, docblocks only where they say something the signature doesn't.
>
> **New code follows the current era. Do not modernise a file you are only passing through.**

The invariants, all observable in the tree:

- PSR-12, with one house deviation that is load-bearing: **manual alignment of `=>` in array literals and `=` in assignment blocks**, as in `MoonController::SORTS` and `ImportEveStaticData::handle()`. Preserve it.
- Zero `declare(strict_types=1)`, zero `final` classes, zero typed properties, zero enums in the codebase today. Don't introduce any of them drive-by in an unrelated change.
- Eloquent relation methods are `snake_case` to match the attributes they front — `mineral_1()`, `mining_activity()`. This looks wrong and is intentional; renaming one breaks eager-load strings and Blade property access silently.
- Read config through `config('eve.*')`, never `env()` outside `config/`. Commit `refactor(env): fix issue #32 by using proper config` was a deliberate cleanup; don't regress it. `MoonSeeder` reads `MOON_SEED_COUNT` directly — that dev fixture is the single exception.

## 7. Comments and diff hygiene

Calibrate the comment budget against the examples already in the tree. In `MoonController`, the comment on `TOTAL_PERCENT` exists because that sort key has no backing column, and the comment on `setRelation(new Collection())` exists because the line prevents an N+1 the reader can't see. Neither restates what its line does.

Don't write section-banner comments (`// ---- Validation ----`), a comment above every block of a method you just wrote, comments narrating the change rather than the code (`// Added filtering here`, `// NEW:`), docblocks that only restate the signature on new code, or `@author`/`@since`/dates.

> Rule of thumb: if a comment would be equally true and equally useful in any other Laravel project, delete it.

Diff hygiene: no drive-by reformatting or import reordering in code you aren't otherwise changing, no whitespace or EOL sweeps, no renaming or method extraction in files you're only passing through, no leftover `dd()`/`dump()`/`Log::info('here')`. Unrelated problems you spot go in the PR description, not the diff. A reviewer should be able to read the whole diff in a couple of minutes; if yours can't be, split it.

## 8. Branches, commits, pull requests

Branch prefixes already in use: `feat/`, `fix/`, `refactor/`, `try/`. Short, kebab-case, describing the change.

> **Never name a branch after the tool that produced it.** No `agent/`, `claude/`, `ai/`, `bot/`, `codex/`. A reviewer should not be able to tell from the branch name who or what typed the code.

Conventional-commit subjects are the dominant recent convention (`fix(miners): allow empty alliance / corp in .env`, `feat(eve-static): add artisan command to properly seed database`); plain imperative subjects are also fine. No `Co-Authored-By: <any AI>` trailers, no "Generated with" footers, no emoji. The history has none of these and the project didn't ask for them.

One logical change per PR. If the change touches the moon list, tax calculation, invoicing, or anything a player sees an ISK figure from, say so explicitly.

## 9. Never without asking

1. **Schema changes.** Migrations run against a live production database with years of billing history. The parts that look like mistakes are not.
2. **Never migrate the EVE static tables.**
3. **No new dependencies**, composer or npm.
4. **No caching layer.** There is none today, and a staleness bug in a billing app is expensive. If a page is slow, fix the query — that's what #71 did, with server-side pagination and an antijoin rather than a cache.
5. **Never touch `vendor/`.**
6. **Never commit `.env`.** It holds live ESI client secrets. New settings go in `.env.example` and are read through `config/eve.php`.
7. **Compiled front-end assets.** `public/js/app.js` (1.6 MB), `public/css/app.css` and `public/mix-manifest.json` **are committed to the repo**. This is deliberate: the release tarball ships `public/` without running a Node build. So if you didn't change anything under `resources/assets/`, don't commit the output of `npm run production` — `git checkout -- public/js public/css public/mix-manifest.json` and move on, because a rebuild-only diff is 1.6 MB of noise that hides the real change. If you did change `resources/assets/`, rebuild and commit the output in the same commit, and say so in the PR. Never hand-edit anything in `public/js/`. `public/css/login.css` is hand-written and not Mix output; that one is editable.
8. **No new front-end framework.** `vue` and `vue-template-compiler` are in `package.json`, but nothing imports them — zero `.vue` files, zero `Vue` references anywhere in `resources/`. Their presence is not permission to start writing Vue.
9. **Do not modify `.gitlab-ci.yml`.** The build tars an allowlist of paths. If your change adds a root file that must ship, flag it in the PR rather than editing the allowlist yourself.

## 10. Verifying a change

> There is effectively no test suite. `tests/` holds four files: `tests/Unit/ExampleTest.php` asserts `true`, `tests/Feature/ExampleTest.php` asserts a single redirect, and only `MinerSearchTest` and `GenerateInvoicesTest` exercise real behaviour — both building their own schema in in-memory SQLite, because there are no factories. A green PHPUnit run is not evidence that anything works.

Most routes sit behind the `login` or `admin` middleware, both of which need an authenticated EVE character, which needs a registered EVE application and a real character. `/login`, `/admin`, `/search`, `/sso`, `/admin-sso`, `/callback` and `/logout` are the only routes without middleware. **Do not** comment out middleware, add a local-only bypass, or edit `routes/web.php` to look at a page. Ask instead.

Verify in this order, and state in the PR description which you actually did:

1. `php -l` on every changed file.
2. `vendor/bin/phpstan analyse`. Larastan runs at level 0 over `app/`, `database/`, `routes/` and `tests/`, with `phpstan-baseline.neon` holding the pre-existing errors. Your change must add none. Don't regenerate the baseline to bury one.
3. `php artisan route:list`. This boots the container, providers, config and every route, and catches syntax errors, bad imports, broken controller references and provider mistakes. It's the cheapest real signal in the repo.
4. For query or model changes: `php artisan tinker`, run the exact builder chain, paste row counts and `->toSql()` into the PR.
5. For data-heavy pages: seed at scale with `MOON_SEED_COUNT=100000` and check for N+1s, not just correctness.
6. For money paths (`app/Classes/CalculateRent.php`, `GenerateInvoice*`, `GenerateRental*`, `ProcessMiningActivity`, `TaxController`): a written worked example in the PR with inputs, expected ISK and actual ISK. A wrong number here bills a real player the wrong amount.

## 11. If you are unsure

Stop and ask when the change needs a schema change, when you can't verify it with the tools above, when the EVE domain rule you're encoding is one you inferred rather than found in the code, or when the diff is growing past a couple of hundred lines.

"I could not verify this" in a PR description is a useful contribution. A confident wrong number is not.
