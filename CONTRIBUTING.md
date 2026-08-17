# Contributing

Thanks for taking the time to work on Moon Mining Manager.

`CONTRIBUTING.md` is the entry point. It covers how to get set up, how to branch, and how to open a pull request. `AGENTS.md` in the repo root holds the detail: domain vocabulary, commands that are unsafe to run, code conventions, and how to verify a change. It is written for both humans and AI assistants, and it is the authoritative version. If the two ever disagree, `AGENTS.md` is right.

## Getting set up

The [README](README.md) covers installation. Read [the development environment section of `AGENTS.md`](AGENTS.md#4-development-environment) as well, because it covers three traps that the install steps do not.

In short: the EVE static data is not in the repo and the app is broken without it, the import command needs the `mysql` binary that lives in the container, and `DB_HOST=moon_db` resolves inside the compose network only. Each trap produces symptoms that look like application bugs.

## Branches and commits

Name the branch after the change. The prefixes in use are `feat/`, `fix/`, `refactor/` and `try/`. Keep the name short and kebab-case.

Conventional-commit subjects are the recent convention, for example `fix(miners): allow empty alliance / corp in .env`. Plain imperative subjects are also fine.

Keep one logical change per pull request. If the diff needs more than a couple of minutes to read, split it.

## Verification

[The verification section of `AGENTS.md`](AGENTS.md#10-verifying-a-change) lists the steps in the order to run them. The pull request template asks which steps you did.

Be honest about the gaps. "I could not verify this" is useful to a reviewer. A confident wrong number is not.

Do not treat a green PHPUnit run as evidence. The `tests/` directory holds four files. Two are the Laravel example stubs, and one of those asserts `true`. Only `MinerSearchTest` and `GenerateInvoicesTest` test real behavior, and each builds its own schema in an in-memory SQLite database.

## Show it working

A pull request that changes what a user sees includes a screenshot of the change against real data. For a page that exists already, include one screenshot before the change and one after it.

A description does not replace the screenshot. If you cannot produce one, write down why, and expect the review to wait.

The exemption is narrow. These changes have no observable output and need no screenshot:

- A class, method or variable rename.
- A move of code between files.
- A type declaration or docblock change.
- An import cleanup.

Everything else needs one. A diff that touches a Blade file, a controller that renders one, a query behind a rendered page, or any file under `resources/assets/` is not exempt.

Work without an interface still gets shown. For a job, a console command or a schedule change, paste the output instead of a screenshot: the terminal output, the rows the job wrote, or the rows it added to the `logs` table.

## Reaching a page in development

Most routes sit behind the `login` or `admin` middleware, so a screenshot needs a real EVE character. Do not comment out the middleware, and do not add a local bypass. Set up a login instead. It takes about ten minutes, once.

1. Register an EVE application at [developers.eveonline.com](https://developers.eveonline.com/). Set the callback URL to `http://localhost:8000/callback`.
2. Put the credentials in `.env` as `EVEONLINE_CLIENT_ID`, `EVEONLINE_CLIENT_SECRET` and `EVEONLINE_REDIRECT`.
3. Put your own corporation ID in `EVE_CORPORATIONS_LOGIN`, or your own alliance ID in `EVE_ALLIANCES_LOGIN`. `AuthController` checks the character against these two lists and sends everybody else back to the login page.
4. Log in at `/login`. This gives you the pages behind the `login` middleware, such as `/moons` and `/timers`.
5. For the admin pages, add a row to the `whitelist` table of your development database. Set `eve_id` to your character ID and `is_admin` to 1. `CheckAdminLogin` reads exactly that row.

A row in your own development database is not a middleware bypass. It is how the application grants admin in production as well.

Seed the moon list with `MOON_SEED_COUNT=1000 php artisan db:seed --class=MoonSeeder` so the screenshots show realistic data.

## Discuss it first

Open an issue before you start a large change. Three kinds of change need agreement in an issue before the work starts:

- A schema change. Migrations run against a live production database with years of billing history.
- A new dependency, composer or npm.
- Anything that touches mining tax, invoicing or rent arithmetic. A wrong number here bills a real player the wrong amount.

## AI assistance

Use of an AI assistant to write code for this project is fine. Attribution of it in the branch name, the commit trailer or the pull request body is not.

Name the branch after the change and not after the tool. Do not use an `agent/`, `claude/`, `ai/`, `bot/` or `codex/` prefix. Do not add a `Co-Authored-By` trailer for an AI, and do not add a "Generated with" footer.

Whatever produced the diff, you are the person who submits it. The review expectations do not change. You understand every line, and you verified it. `AGENTS.md` gives your assistant the project context it needs to produce something reviewable.
