<?php namespace NeilRussell6\CodeceptionLaravel5Extensions;

use \Codeception\Event\SuiteEvent;
use \Codeception\Event\TestEvent;
use Codeception\Lib\Console\Output;
use \Codeception\Platform\Extension;
use \Illuminate\Support\Facades\Artisan;

/**
 * Class ArtisanMigrateExtension
 * @package NeilRussell6\CodeceptionLaravel5Extensions
 */
class ArtisanMigrateExtension extends Extension
{
    public $connection          = 'sqlite';
    public $sqlite_path         = 'storage/database_testing.sqlite';

    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    |
    */

    public static $events = [
        'suite.before' => 'beforeSuite',
        'test.before' => 'beforeTest',
    ];

    /*
    |--------------------------------------------------------------------------
    | Event Handlers
    |--------------------------------------------------------------------------
    |
    */
    public function beforeSuite(SuiteEvent $e)
    {
        // get config

        if ( array_key_exists('db_connection', $this->config) ) {
            $this->connection = $this->config['db_connection'];
        }
        if ( array_key_exists('db_sqlite_path', $this->config) ) {
            $this->sqlite_path = $this->config['db_sqlite_path'];
        }

        // if using sqlite

        if ( $this->connection === "sqlite" ) {

            $sqlite_db_path = $this->sqlite_path;

            // ... create sqlite file directory if it doesn't exist

            if( !file_exists( dirname( $sqlite_db_path ) ) ) {
                mkdir( dirname( $sqlite_db_path ), 0777, true );
            }

            // ... and create sqlite file if it doesn't exist

            if( !file_exists( $sqlite_db_path ) ) {
                touch( $sqlite_db_path );
            }
        }

        putenv( 'DB_CONNECTION=' . $this->connection );
    }

    /**
     * before each test runs
     * here we migrate if no migration and reset if migration already run
     * TODO: This is not working when Laravel5 transaction mode (cleanup=true in config) is activated, figure out why (eg error: no such table: users in routing/RegisterCest)
     *
     * @param TestEvent $e
     * @return bool
     */
    public function beforeTest(TestEvent $e) {

        // instantiate Codeception console output
        $output = new Output([]);

        // get laravel5 module
        $l5 = $this->getModule('Laravel5');

        // disable transaction mode for tests (this extension does not work with Laravel 5 transaction mode) TODO: figure out why
        if ( $l5->config['cleanup'] ) {
            $output->writeln("\n\e[41m" . "Please set Laravel5 Codeception module's cleanup to false (in tests/functional.suite.yml) before using NeilRussell6\\CodeceptionLaravel5Extensions\\ArtisanMigrateExtension." . "\e[0m");
            die();
        }

        // get current migration status
        Artisan::call('migrate:status', ['--database' => $this->connection]);
        $status = Artisan::output();
        //var_dump($status);

        // ... if no migrations the run migrate
        if ( str_contains( $status, "No migrations found") ) {

            Artisan::call('migrate', ['--database' => $this->connection]);
            //$result = Artisan::output();
            //var_dump($result);
        }

        // ... else if migrations already exist
        else {

            Artisan::call('migrate:refresh', ['--database' => $this->connection]);
            //$result = Artisan::output();
            //var_dump($result);
        }
    }
}
?>