<?php

use App\Models\Role as RoleModel;
use Illuminate\Database\Seeder;
use Kodeine\Acl\Models\Eloquent\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = new Role();
        $role->create([
            'name' => 'Administrator',
            'slug' => RoleModel::ROLE_ADMINISTRATOR,
            'description' => 'manage administration privileges'
        ]);

        $role = new Role();
        $role->create([
            'name' => 'Demo',
            'slug' => RoleModel::ROLE_DEMO,
            'description' => 'manage demo privileges'
        ]);

        $role = new Role();
        $role->create([
            'name' => 'Subscriber',
            'slug' => RoleModel::ROLE_SUBSCRIBER,
            'description' => 'manage subscriber privileges'
        ]);
    }
}
