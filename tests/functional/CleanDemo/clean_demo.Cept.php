<?php

use App\Models\Task;
use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

$I = new FunctionalTester($scenario);

///////////////////////////////////////////////////////
//
// before
//
///////////////////////////////////////////////////////

// ====================================================
// create data
// ====================================================

$administrator_role = Role::where('name', '=', 'administrator')->first();
$demo_role = Role::where('name', '=', 'demo')->first();
$subscriber_role = Role::where('name', '=', 'subscriber')->first();

$password = "abcABC123!";

// ----------------------------------------------------
// admin
// ----------------------------------------------------

$I->comment("given 1 admin user");
$user_admin = factory(User::class)->create();
$user_admin->roles()->attach([ $user_admin->id ]);

// projects

$I->comment("given 2 projects owned by admin user");
$user_admin_projects = factory(Project::class, 2)->create(['user_id' => $user_admin->id]);

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
$user_demo = factory(User::class)->create(['username' => 'demo']);
$user_demo->roles()->attach([ $demo_role->id ]);

// ----------------------------------------------------
// subscriber
// ----------------------------------------------------

$I->comment("given 2 subscriber users");
$subscriber_users = factory(User::class, 2)->create()->map(function($user) use ($subscriber_role) {
    $user->roles()->attach([ $subscriber_role->id ]);
    return $user;
});

// projects

$I->comment("given 2 projects owned by each subscriber user");
$user_subscriber_1_projects = factory(Project::class, 2)->create(['user_id' => $subscriber_users[0]->id]);
$user_subscriber_2_projects = factory(Project::class, 2)->create(['user_id' => $subscriber_users[1]->id]);

// ----------------------------------------------------
// public
// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
$public_users = factory(User::class, 2)->create();

// projects

$I->comment("given no projects owned by public users");

// ----------------------------------------------------

$I->expect("should be 6 users");
$I->assertSame(6, User::all()->count());

$I->expect("should be 6 projects");
$I->assertSame(6, Project::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * demo:clean command
//
///////////////////////////////////////////////////////

// default projects & tasks

$I->comment("given the default amount of projects and task for the demo user (created by the demo seeder)");
Artisan::call('db:seed', ['--class' => 'DatabaseDemoSeeder']);
$default_demo_project_count = Project::all()->count();
$default_demo_task_count = Task::all()->count();

// projects

$I->comment("given 2 projects owned by demo user are then added");
$demo_projects = factory(Project::class, 2)->create(['user_id' => $user_demo->id]);

// tasks

$I->comment("given 2 tasks owned by demo user are then added");
factory(Task::class, 1)->create(['user_id' => $user_demo->id, 'project_id' => $demo_projects[0]->id]);
factory(Task::class, 1)->create(['user_id' => $user_demo->id, 'project_id' => $demo_projects[1]->id]);

$current_demo_project_count = Project::all()->count();
$current_demo_task_count = Task::all()->count();

// ----------------------------------------------------
// demo:clean
// ----------------------------------------------------

$I->comment("when we run the demo:clean command");
Artisan::call('demo:clean');

$I->expect("should have deleted all projects and re-seeded, resulting in original project count");
$I->assertSame($default_demo_project_count, Project::all()->count());
$I->assertSame($default_demo_task_count, Task::all()->count());
