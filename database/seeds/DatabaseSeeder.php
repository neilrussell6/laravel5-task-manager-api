<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    protected $tables = ['users', 'tasks'];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // truncate tables
        foreach ($this->tables as $table) {
            DB::table($table)->truncate();
        }

        // seed tables
        $this->call(UserWithTasksSeeder::class);

        Model::reguard();
    }
}
