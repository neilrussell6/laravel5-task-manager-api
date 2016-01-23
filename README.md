# Laravel 5 - Task List

[![Build Status](https://travis-ci.org/neilrussell6/l5-task-list.svg?branch=master)](https://travis-ci.org/neilrussell6/l5-task-list)

The Laravel 5 Intermediate Task List tutorial, with a few cool extras.

## Quick Start

#### Clone Github repository

```bash
git clone https://github.com/neilrussell6/l5-task-list.git
```

#### Install dependencies with Composer

```bash
composer install
```

#### Copy config files

##### env files

```bash
cp .env.example .env
cp .env-testing.example .env-testing
```

##### Codeception files
```bash
cp codeception.yml.example codeception.yml
cp tests/acceptance.suite.yml.example acceptance.suite.yml
cp tests/functional.suite.yml.example functional.suite.yml
cp tests/unit.suite.yml.example unit.suite.yml
```

#### Update local config files

This usually includes:

**.env & .env-testing**
```
APP_KEY
APP_URL
APP_TIMEZONE
DB_MYSQL_USERNAME
DB_MYSQL_PASSWORD
DB_MYSQL_DATABASE
DB_SQLITE_DATABASE
MAIL_DRIVER
```

**.codeception.yml**

Config for NeilRussell6\CodeceptionLaravel5Extensions\ArtisanMigrateExtension extension:
 * **db_connection** must match **DB_CONNECTION** in .env-testing
 * **db_sqlite_path** must match **DB_SQLITE_DATABASE** in .env-testing


## Running Tests

```bash
./vendor/bin/codecept build
./vendor/bin/codecept run
```