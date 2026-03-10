## Phinx Setup

### About

This branch contains setup and sample files for getting started with the database migration tool Phinx.

### Setup

Docs: [https://book.cakephp.org/phinx/0/en/](https://book.cakephp.org/phinx/0/en/)

_In your own project_, while your docker container is running, in a separate terminal, run:

Install Phinx: `docker compose run --rm php composer require robmorgan/phinx`

Init Phinx: `docker compose run --rm php vendor/bin/phinx init`

Update config details in ./phinx.php, (you can copy/paste the one from this branch)

Note you may need to create the migration directory manually: `app\db\migrations`

Referencing Phinx docs create a migration (change "CreateUserTable" to a name that makes sense for your migration): `docker compose run --rm php vendor/bin/phinx create CreateUserTable`

Run migration(s): `docker compose run --rm php vendor/bin/phinx migrate`

## Expired Hold Cleanup

Expired ticket holds are now cleaned up on checkout-critical requests only:

- `GET /checkout` runs at most once per session minute
- checkout confirmation and pending-payment routes force a fresh cleanup
- planner and jazz browsing no longer run expiry cleanup on every request

For near-real-time stock recovery outside user traffic, schedule the shared CLI task:

`php app/bin/release_expired_holds.php`

Example cron entry:

`*/5 * * * * cd /path/to/HaarlemFest && php app/bin/release_expired_holds.php >> /var/log/haarlemfest-expiry-cleanup.log 2>&1`
