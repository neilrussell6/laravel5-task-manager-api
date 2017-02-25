Laravel 5 Task Manager API
==========================

> A Laravel 5 Task Manager API, that implements the [JSON API](http://jsonapi.org/format/) spec using [Laravel5JsonApi](https://github.com/neilrussell6/laravel5-json-api).

Response format:

* [JSON API](http://jsonapi.org/format/)

Uses:

* [Laravel 5](https://laravel.com/docs/5.3)
* [Laravel5JsonApi](https://github.com/neilrussell6/laravel5-json-api)
* [Codeception](http://codeception.com/)

Installation
------------

#### Clone Github repository

```bash
git clone https://github.com/neilrussell6/laravel5-task-manager-api.git
```

#### Install dependencies with Composer

```bash
composer install
```

Configuration
-------------

#### Generate Laravel key

```bash
php artisan key:generate
```

#### Copy config files

##### env files

```bash
cp .env.example .env
cp .env.testing.example .env.testing
```

##### Codeception files

```bash
cp codeception.yml.example codeception.yml
cp tests/acceptance.suite.yml.example acceptance.suite.yml
cp tests/functional.suite.yml.example functional.suite.yml
cp tests/unit.suite.yml.example unit.suite.yml
```

#### Update local config files

**.env & .env-testing**

Usage
-----

#### 1) Run migrations

```bash
php artisan migrate
```

#### 2) Seed database (optional)

```bash
php artisan db:seed
```

#### 3) Serve

```bash
php artisan serve
```

#### 4) View in [Postman](https://chrome.google.com/webstore/detail/postman/fhbjgbiflinjbdggehcddcbncdddomop?hl=en)

```bash
php artisan serve
```

Testing
-------

#### 1) Create a SQLite testing DB and make it executable:

```bash
touch database/laravel5_task_manager_api_testing.sqlite
sudo chmod -R 777 database/laravel5_task_manager_api_testing.sqlite
```

#### 2) Run migrations on testing DB

```bash
php artisan migrate --database=sqlite_testing
```
