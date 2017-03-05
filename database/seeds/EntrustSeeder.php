<?php

use App\Models\Permission as PermissionModel;
use App\Models\Permission;
use App\Models\Role as RoleModel;
use App\Models\Role;
use Illuminate\Database\Seeder;

class EntrustSeeder extends Seeder
{
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

        $createPost = new Permission();
        $createPost->name         = 'create-post';
        $createPost->display_name = 'Create Posts'; // optional
        // Allow a user to...
        $createPost->description  = 'create new blog posts'; // optional
        $createPost->save();

        $editUser = new Permission();
        $editUser->name         = 'edit-user';
        $editUser->display_name = 'Edit Users'; // optional
        // Allow a user to...
        $editUser->description  = 'edit existing users'; // optional
        $editUser->save();

        // ----------------------------------------------------
        // roles
        // ----------------------------------------------------

        $admin = new Role();
        $admin->name         = 'admin';
        $admin->display_name = 'User Administrator'; // optional
        $admin->description  = 'User is allowed to manage and edit other users'; // optional
        $admin->save();
        
        $owner = new Role();
        $owner->name         = 'owner';
        $owner->display_name = 'Resource Owner'; // optional
        $owner->description  = 'User is the owner of a given resource'; // optional
        $owner->save();
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
