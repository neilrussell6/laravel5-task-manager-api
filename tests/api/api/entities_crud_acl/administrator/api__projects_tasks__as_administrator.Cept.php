<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;

$I = new ApiTester($scenario);

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

// tasks

$I->comment("given 2 tasks for each project owned by admin user");
$user_admin_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $user_admin->id, 'project_id' => $user_admin_projects[0]->id]);
$user_admin_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $user_admin->id, 'project_id' => $user_admin_projects[1]->id]);

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
$user_demo = factory(User::class)->create();
$user_demo->roles()->attach([ $demo_role->id ]);

// projects

$I->comment("given 2 projects owned by demo user");
$user_demo_projects = factory(Project::class, 2)->create(['user_id' => $user_demo->id]);

// tasks

$I->comment("given 2 tasks for each project owned by demo user");
$user_demo_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $user_demo->id, 'project_id' => $user_demo_projects[0]->id]);
$user_demo_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $user_demo->id, 'project_id' => $user_demo_projects[1]->id]);

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

// tasks

$I->comment("given 2 tasks for each project owned by each subscriber user");
$user_subscriber_1_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[0]->id, 'project_id' => $user_subscriber_1_projects[0]->id]);
$user_subscriber_1_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[0]->id, 'project_id' => $user_subscriber_1_projects[1]->id]);
$user_subscriber_2_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[1]->id, 'project_id' => $user_subscriber_2_projects[0]->id]);
$user_subscriber_2_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[1]->id, 'project_id' => $user_subscriber_2_projects[1]->id]);

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

$I->expect("should be 8 projects");
$I->assertSame(8, Project::all()->count());

$I->expect("should be 16 tasks");
$I->assertSame(16, Task::all()->count());

// ====================================================
// authenticate user and set headers
// ====================================================

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes'] = [
    'email' => $user_admin->email,
    'password' => $password,
];

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

$I->haveHttpHeader('Authorization', "Bearer {$access_token}");

///////////////////////////////////////////////////////
//
// Test
//
// * project tasks as administrator
//
// Endpoints
//
// * projects.tasks.index
// * projects.relationships.tasks.index
//
///////////////////////////////////////////////////////

// ====================================================
// projects.tasks.index
// ====================================================

$I->comment("when we index all tasks of any project belonging to any user");
$requests = [
    [ 'GET', "/api/projects/{$user_admin_projects[0]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_admin_projects[1]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_projects[0]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_projects[1]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_projects[0]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_projects[1]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_projects[0]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_projects[1]->id}/tasks" ],
    [ 'GET', "/api/projects/{$user_admin_projects[0]->id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_admin_projects[1]->id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_projects[0]->id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_projects[1]->id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_projects[0]->id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_projects[1]->id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_projects[0]->id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_projects[1]->id}/relationships/tasks" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return 2 tasks");
    $I->assertCount(2, $I->grabResponseJsonPath('$.data[*]'));
    $I->seeResponseJsonPathSame('$.data[*].type', 'tasks');

});
