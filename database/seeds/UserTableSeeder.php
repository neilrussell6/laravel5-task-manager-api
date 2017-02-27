<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // admin

        DB::table('users')->insert([
            'username' => 'administrator',
            'first_name' => env('ADMIN_FIRST_NAME') ?: null,
            'last_name' => env('ADMIN_LAST_NAME') ?: null,
            'email' => env('ADMIN_EMAIL'),
            'password' => bcrypt(env('ADMIN_PASSWORD'))
        ]);

        // demo

        $user_admin = User::find(DB::getPdo()->lastInsertId());
        $user_admin->assignRole(Role::ROLE_ADMINISTRATOR);

        DB::table('users')->insert([
            'username' => 'demo',
            'first_name' => env('DEMO_FIRST_NAME') ?: null,
            'last_name' => env('DEMO_LAST_NAME') ?: null,
            'email' => env('DEMO_EMAIL'),
            'password' => bcrypt(env('DEMO_PASSWORD'))
        ]);

        $user_demo = User::find(DB::getPdo()->lastInsertId());
        $user_demo->assignRole(Role::ROLE_DEMO);

        if (App::environment() === 'local') {

            // subscribers

            $collection = factory(User::class, 10)->create();
            $collection->each(function($user) {
                $user->assignRole(Role::ROLE_SUBSCRIBER);
            });

            // public (no role)

            $collection = factory(User::class, 10)->create();
        }
    }
}
