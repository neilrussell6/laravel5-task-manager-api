<?php

use App\Models\Task;
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

$demo_role = Role::where('name', '=', 'demo')->first();
$password = "abcABC123!";

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
$user_demo = factory(User::class)->create(['username' => 'demo']);
$user_demo->roles()->attach([ $demo_role->id ]);

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

$I->comment("given 2 additional projects owned by demo user are then added");
$demo_projects = factory(Project::class, 2)->create(['user_id' => $user_demo->id]);

// tasks

$I->comment("given 2 tasks owned by demo user are then also added");
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
