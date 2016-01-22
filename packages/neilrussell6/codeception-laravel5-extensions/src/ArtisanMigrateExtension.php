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
    public $connection_key      = 'DB_CONNECTION';
    public $connection          = 'sqlite';
    public $sqlite_path         = 'storage/database_testing.sqlite';
    public $migrate             = true;

    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    |
    */

    public static $events = [
        'test.before' => 'beforeTest'
    ];

    /*
    |--------------------------------------------------------------------------
    | Event Handlers
    |--------------------------------------------------------------------------
    |
    */

    /**
     * before each test runs
     *
     * @param TestEvent $e
     * @return bool
     */
    public function beforeTest(TestEvent $e) {

        // if using sqlite

        if ( env('DB_CONNECTION') === "sqlite" ) {

            $sqlite_db_path = storage_path( env('DB_SQLITE_DATABASE'));

            // ... create sqlite file directory if it doesn't exist

            if( !file_exists( dirname( $sqlite_db_path ) ) ) {
                mkdir( dirname( $sqlite_db_path ), 0777, true );
            }

            // ... and create sqlite file if it doesn't exist

            if( !file_exists( $sqlite_db_path ) ) {
                touch( $sqlite_db_path );
            }
        }

        // get current migration status

        Artisan::call('migrate:status', ['--database' => $this->connection]);
        $status = Artisan::output();
//        var_dump($status);

        // ... if no migrations the run migrate

        if ( str_contains( $status, "No migrations found") ) {
            Artisan::call('migrate', ['--database' => $this->connection]);
//            $result = Artisan::output();
//            var_dump($result);die();
            return true;
        }

        // ... if migrations already exist then run migrate:refresh

        Artisan::call('migrate:refresh', ['--database' => $this->connection]);
//        $result = Artisan::output();
//        var_dump($result);die();
    }
}
?>