# Moon Mining Manager

This application manages moon-mining revenue and invoicing for EVE Online corporations and alliances.

## Requirements

* PHP >=8.1
* MySQL 8 or MariaDB (tested with 10.6)
* An [EVE app](https://developers.eveonline.com) with the following scopes:
  - esi-mail.send_mail.v1
  - esi-universe.read_structures.v1
  - esi-corporations.read_structures.v1
  - esi-wallet.read_corporation_wallets.v1
  - esi-characters.read_notifications.v1
  - esi-industry.read_corporation_mining.v1  
    Callback URL: https://your.domain.tld/callback

## Installation Instructions

* Create a database for the application.
* Download the following EVE static data tables from [Fuzzworks](https://www.fuzzwork.co.uk/dump/latest/) as SQL files
  and place them in `database/eve-static/`:
  * `invTypes.sql`
  * `invTypeMaterials.sql`
  * `invUniqueNames.sql`
  * `mapSolarSystems.sql`
  * `mapRegions.sql`
* Run `npm install` to install frontend dependencies.
* Rename the `.env.example` file to `.env` and adjust values.
* Run `composer install` to install backend dependencies.
* Run `php artisan key:generate`.
* Run `php artisan migrate` to create the database tables.
* Regenerate js/css with `npm run production`, if they have changed.

See also https://laravel.com/docs/10.x/installation.

### Development Environment

You can use Docker to run the app.

Copy the example override file before starting (required once per clone):
```
cp docker-compose.override.yml.example docker-compose.override.yml
```

`docker-compose.override.yml` is gitignored so you can customise it locally (e.g. port mappings, volume mounts) without affecting other developers. The example ships the Vite dev-server port mapping (`5173:5173`).

Start the dev containers and shells:
```
docker-compose up
docker-compose exec moon_php /bin/sh
docker-compose run moon_node /bin/sh
```

Import EVE static data (place SQL files in `database/eve-static/` first — see [Installation Instructions](#installation-instructions)):
```
docker-compose exec moon_php php artisan eve:import-static-data
```

To re-import a single table (e.g. after an EVE patch):
```
docker-compose exec moon_php php artisan eve:import-static-data --table=invTypes
```

Generate files and annotations with the IDE Helper for Laravel:
```
php artisan ide-helper:generate
php artisan ide-helper:meta
php artisan ide-helper:models
```

Run the queue:
```
php artisan schedule:run
php artisan queue:work
```

To run a single job use, e.g.:  
(Simply add constructor arguments after the class name.)
```
php artisan command:run-job PollStructures 2113076059
```

## Initial setup

- Add your admin user to the table `whitelist` with `is_admin` = `1`. They can now log in and authorise other users.
- Add the following mail templates to the table `templates`: `weekly_invoice`, `receipt`, `renter_invoice`,
  `renter_notification`, `renter_reminder`.
- Login at http://your.domain/admin with a director of your corporations to create the required ESI tokens. Add the
  IDs to the environment variables `*_PRIME_USER_ID`.
- Login at http://your.domain/admin with a character that should be used to send mails and add the ID to the
  environment variable `MAIL_USER_ID`.

## Operation instructions

* Run `php artisan queue:work` to start the job queue process. See the
  [Laravel documentation on Queues](https://laravel.com/docs/10.x/queues) for more information on how to use
  Supervisor to manage job queues.
* Add a Cron for the [Task Scheduler](https://laravel.com/docs/10.x/scheduling)

## Further Configuration

Login with you admin user and configure the application:

- Edit the mail templates at "Manage Emails".
- Set the tax for each ore (the list will be populated automatically by a cron job).
- Make sure at least one character receives mails from the contact form (at "Settings") or set up a Slack hook.
- Import moon data.

## Changelog

Only updates that require work are listed here at the moment.

### 2023-10-17

- Raised minimum required PHP version to 8.1.0.
- Added `APP_URL` environment variable to generate URLs (see [.env.example](.env.example)).

### 2021-12-20

- Added esi-characters.read_notifications.v1 scope to admin login - add this to your EVE app.
- Added `invUniqueNames` table with data from Fuzzworks - see [Installation Instructions](#installation-instructions).

## License

This application is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
