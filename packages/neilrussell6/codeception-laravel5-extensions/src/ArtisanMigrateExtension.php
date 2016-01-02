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
    public $connection          = 'sqlite_testing';
    public $sqlite_path         = 'storage/database_testing.sqlite';
    public $migrate             = true;

    // event listeners

    public static $events = [
        'suite.before' => 'beforeSuite',
        'test.before' => 'beforeTest'
    ];

    // event handlers

    public function beforeSuite(SuiteEvent $e) {

        // get config
        if ( array_key_exists('db_connection_key', $this->config) ) {
            $this->connection_key = $this->config['db_connection_key'];
        }
        if ( array_key_exists('db_connection', $this->config) ) {
            $this->connection = $this->config['db_connection'];
        }
        if ( array_key_exists('db_sqlite_path', $this->config) ) {
            $this->sqlite_path = $this->config['db_sqlite_path'];
        }
        if ( array_key_exists('migrate', $this->config) ) {
            $this->migrate = $this->config['migrate'];
        }

        // create DB connection env string
        $env_str = $this->connection_key . "=" . $this->connection;

        // set DB connection env
        putenv( $env_str );

        // create sqlite file directory if it doesn't exist
        if( !file_exists( dirname($this->sqlite_path) ) ) {
            mkdir( dirname(  $this->sqlite_path ), 0777, true );
        }

        // create sqlite file if it doesn't exist
        if( !file_exists( $this->sqlite_path ) ) {
            touch( $this->sqlite_path );
        }
    }

    public function beforeTest(TestEvent $e) {

        if ( !$this->migrate ) {
            return;
        }

        Artisan::call('migrate:status', ['--database' => $this->connection]);
        $status = Artisan::output();

        if ( str_contains( $status, "No migrations found") ) {
            Artisan::call('migrate', ['--database' => $this->connection]);
//            $result = Artisan::output();
        } else {
            Artisan::call('migrate:refresh', ['--database' => $this->connection]);
//            $result = Artisan::output();
        }
    }
}
?>

