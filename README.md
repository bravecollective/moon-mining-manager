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
* A MySQL/MariaDB database

## Installation Instructions

* Create a database for the application.
* You will need to import the following EVE dump tables into your database. They can be downloaded from
  [Fuzzworks](https://www.fuzzwork.co.uk/dump/latest/).
  * invTypes
  * invTypeMaterials
  * invUniqueNames
  * mapSolarSystems
  * mapRegions
* Run `npm install` to install frontend dependencies.
* Rename the `.env.example` file to `.env` and adjust values.
* Run `composer install` to install backend dependencies.
* Run `php artisan key:generate`.
* Run `php artisan migrate` to create the database tables.
* Regenerate js/css with `npm run production`, if they have changed.

See also https://laravel.com/docs/10.x/installation.

### Development Environment

You can use Docker to run the app.

* Start the dev containers: `docker-compose up`.
* Shell into the PHP container via `docker-compose exec moon_php /bin/sh`.
* Shell into the Node.js container via `docker-compose run moon_node /bin/sh`.

> If you wish, there is a `docker-compose.override.yml.example` file included. You can rename it to
> `docker-compose.override.yml` to use a containerized database.

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

## Updates

Only updates that require work are listed here at the moment.

### 2021-12-20

- Added esi-characters.read_notifications.v1 scope to admin login - add this to your EVE app.
- Added `invUniqueNames` table with data from Fuzzworks - see [Installation Instructions](#installation-instructions).

## License

This application is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
