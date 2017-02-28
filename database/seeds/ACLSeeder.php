<?php

use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use Illuminate\Database\Seeder;
use Kodeine\Acl\Models\Eloquent\Permission;
use Kodeine\Acl\Models\Eloquent\Role;

class ACLSeeder extends Seeder
{
    public $routes_users = [
        'index',
        'view',
        'store',
        'update',
        'destroy',
        'projects.index',
        'relationships.projects.index',
        'tasks.index',
        'relationships.tasks.index',
    ];

    public $routes_projects = [
        'index',
        'view',
        'store',
        'update',
        'destroy',
        'owner.show',
        'relationships.owner.show',
        'tasks.index',
        'relationships.tasks.index',
    ];

    public $routes_tasks = [
        'index',
        'view',
        'store',
        'update',
        'destroy',
        'owner.show',
        'relationships.owner.show',
        'project.show',
        'relationships.project.show',
    ];
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ----------------------------------------------------
        // permissions
        // ----------------------------------------------------

        // users

        $permissions_users_all = Permission::create([
            'name'        => 'users',
            'slug'        => $this->createSlugs($this->routes_users, true),
            'description' => 'manage user permissions'
        ]);

        $permissions_users_none = Permission::create([
            'name'        => 'users',
            'slug'        => $this->createSlugs($this->routes_users, false),
            'description' => 'manage user permissions'
        ]);

        $permissions_users_view_update = Permission::create([
            'name'        => 'users',
            'slug'        => [
                'view'     => true,
                'update'   => true,
            ],
            'inherit_id'  => $permissions_users_none->getKey(),
            'description' => 'manage user permissions'
        ]);

        // projects

        $permissions_projects_all = Permission::create([
            'name'        => 'projects',
            'slug'        => $this->createSlugs($this->routes_projects, true),
            'description' => 'manage project permissions'
        ]);

        // tasks

        $permissions_tasks_all = Permission::create([
            'name'        => 'tasks',
            'slug'        => $this->createSlugs($this->routes_tasks, true),
            'description' => 'manage task permissions'
        ]);

        // ----------------------------------------------------
        // roles
        // ----------------------------------------------------

        // administrator

        $administrator = Role::create([
            'name' => 'Administrator',
            'slug' => RoleModel::ROLE_ADMINISTRATOR,
            'description' => 'manage administration privileges'
        ]);

        $administrator->assignPermission([
            $permissions_users_all,
            $permissions_projects_all,
            $permissions_tasks_all,
        ]);

        // demo

        $demo = Role::create([
            'name' => 'Demo',
            'slug' => RoleModel::ROLE_DEMO,
            'description' => 'manage demo privileges'
        ]);

        $demo->assignPermission([
            $permissions_users_view_update,
            $permissions_projects_all,
            $permissions_tasks_all,
        ]);

        // subscriber

        $subscriber = Role::create([
            'name' => 'Subscriber',
            'slug' => RoleModel::ROLE_SUBSCRIBER,
            'description' => 'manage subscriber privileges'
        ]);

        $subscriber->assignPermission([
            $permissions_users_view_update,
            $permissions_projects_all,
            $permissions_tasks_all,
        ]);
    }

    /**
     * Helper method to create an object of slugs
     *
     * @param $routes
     * @param $value
     * @return mixed
     */
    public function createSlugs ($routes, $value)
    {
        return array_reduce($routes, function ($carry, $route) use ($value) {
            $carry[ $route ] = $value;
            return $carry;
        }, []);
    }
}
