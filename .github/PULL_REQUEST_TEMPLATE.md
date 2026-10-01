## What and why

Closes #

<!-- What changes, and what problem it solves. -->

## Screenshots

<!-- Before and after for a page that exists already. See "Show it working" in CONTRIBUTING.md. -->

- [ ] Screenshot attached, or output pasted for a job or console command
- [ ] Not applicable: no observable output (rename, file move, type or docblock change, import cleanup)

## Money paths

<!-- Mining tax, invoicing, rent calculation, or any ISK figure a player sees. -->

- [ ] This change touches a money path. Worked example below: inputs, expected ISK, actual ISK
- [ ] It does not

## How this was verified

<!-- Tick what you did. An honest gap is more useful than a ticked box. -->

- [ ] `php -l` on every changed file
- [ ] `vendor/bin/phpstan analyse` adds no new error
- [ ] `php artisan route:list` boots cleanly
- [ ] Query or model changes: ran the builder in tinker, row counts and `->toSql()` below
- [ ] Data-heavy page: checked at scale with `MOON_SEED_COUNT=100000`
- [ ] Wrote or updated a test
- [ ] Could not verify part of this. What, and why:

## Compiled assets

- [ ] This pull request changes `public/js/app.js` or `public/css/app.css`, because:
- [ ] It does not, and they do not appear in the diff

## Anything else

<!-- Unrelated problems you noticed and deliberately did not fix. -->
