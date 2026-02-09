## Phinx Setup

### About

This branch contains setup and sample files for getting started with database migration tool Phinx.

### Setup

Docs: [https://book.cakephp.org/phinx/0/en/](https://book.cakephp.org/phinx/0/en/)

_In your own project_, while your docker container is running, in a separate terminal, run:

Install Phinx: `docker compose run --rm php composer require robmorgan/phinx`

Init Phinx: `docker compose run --rm php vendor/bin/phinx init`

Update config details in ./phinx.php, (you can copy/paste the one from this branch)

Note you may need to create the migration directory manually: `app\db\migrations`

Create a migration (change CreateUserTable to whatever makes sense for your migration): `docker compose run --rm php vendor/bin/phinx create CreateUserTable`

View Phinx docs and create a migration (see app\db\migrations\20260209125845_create_user_table.php as an example)

Run migration(s): `docker compose run --rm php vendor/bin/phinx migrate`
