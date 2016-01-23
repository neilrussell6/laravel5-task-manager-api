<?php namespace NeilRussell6\CodeceptionLaravel5Extensions;

use \Codeception\Event\SuiteEvent;
use \Codeception\Event\TestEvent;
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
     * here we migrate if no migration and reset if migration already run or if no transaction mode
     * TODO: not working, figure out why sometime (ERROR: no such table: users in routing/RegisterCest)
     *
     * @param TestEvent $e
     * @return bool
     */
    public function beforeTest(TestEvent $e) {

        // get laravel5 module

        $l5 = $this->getModule('Laravel5');

        // get current migration status

        Artisan::call('migrate:status', ['--database' => $this->connection]);
        $status = Artisan::output();
        //var_dump($status);

        // ... if no migrations the run migrate

        if ( str_contains( $status, "No migrations found") ) {

            Artisan::call('migrate', ['--database' => $this->connection]);
            //$result = Artisan::output();
        }

        // ... if migrations already exist and either:
        // ... a) we are not using the Laravel5 module
        // ... b) we are not using transaction mode (cleanup in Laravel5 config)

        else if ( !$this->hasModule('Laravel5') || !$l5->config['cleanup'] ) {

            Artisan::call('migrate:refresh', ['--database' => $this->connection]);
            //$result = Artisan::output();
            //var_dump($result);
        }
    }
}
?>