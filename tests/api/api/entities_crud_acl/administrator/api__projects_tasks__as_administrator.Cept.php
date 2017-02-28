<?php

use App\Models\Project;
use App\Models\Role as RoleModel;
use App\Models\Task;
use App\Models\User;
use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use Illuminate\Support\Facades\Hash;
use Kodeine\Acl\Models\Eloquent\Role;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// before
//
///////////////////////////////////////////////////////

// ====================================================
// create data
// ====================================================

$password = "abcABC123!";

// ----------------------------------------------------
// admin
// ----------------------------------------------------

$I->comment("given 1 admin user");
factory(User::class, 1)->create([
    'email' => 'admin@bbb.ccc',
    'password' => Hash::make($password),
]);
$user_admin_id = 1;
$user_admin = User::find($user_admin_id);
$user_admin->assignRole(RoleModel::ROLE_ADMINISTRATOR);

// projects

$I->comment("given 2 projects owned by admin user");
factory(Project::class, 2)->create(['user_id' => $user_admin_id]);
$user_admin_project_1_id = 1;
$user_admin_project_2_id = 2;

// tasks

$I->comment("given 2 tasks for each project owned by admin user");
factory(Task::class, 2)->create(['user_id' => $user_admin_id, 'project_id' => $user_admin_project_1_id]);
factory(Task::class, 2)->create(['user_id' => $user_admin_id, 'project_id' => $user_admin_project_2_id]);

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
factory(User::class, 1)->create([
    'email' => "demo@abc.def",
    'password' => Hash::make($password),
]);
$user_demo_id = 2;
$user_demo = User::find($user_demo_id);
$user_demo->assignRole(RoleModel::ROLE_DEMO);

// projects

$I->comment("given 2 projects owned by demo user");
factory(Project::class, 2)->create(['user_id' => $user_demo_id]);
$user_demo_project_1_id = 3;
$user_demo_project_2_id = 4;

// tasks

$I->comment("given 2 tasks for each project owned by demo user");
factory(Task::class, 2)->create(['user_id' => $user_demo_id, 'project_id' => $user_demo_project_1_id]);
factory(Task::class, 2)->create(['user_id' => $user_demo_id, 'project_id' => $user_demo_project_2_id]);

// ----------------------------------------------------
// subscriber
// ----------------------------------------------------

$I->comment("given 2 subscriber users");
factory(User::class, 2)->create()->each(function($user) {
    $user->assignRole(RoleModel::ROLE_SUBSCRIBER);
});

$user_subscriber_1_id = 3;
$user_subscriber_2_id = 4;

// projects

$I->comment("given 2 projects owned by each subscriber user");
factory(Project::class, 2)->create(['user_id' => $user_subscriber_1_id]);
$user_subscriber_1_project_1_id = 5;
$user_subscriber_1_project_2_id = 6;
factory(Project::class, 2)->create(['user_id' => $user_subscriber_2_id]);
$user_subscriber_2_project_1_id = 7;
$user_subscriber_2_project_2_id = 8;

// tasks

$I->comment("given 2 tasks for each project owned by each subscriber user");
factory(Task::class, 2)->create(['user_id' => $user_subscriber_1_id, 'project_id' => $user_subscriber_1_project_1_id]);
factory(Task::class, 2)->create(['user_id' => $user_subscriber_1_id, 'project_id' => $user_subscriber_1_project_2_id]);
factory(Task::class, 2)->create(['user_id' => $user_subscriber_2_id, 'project_id' => $user_subscriber_2_project_1_id]);
factory(Task::class, 2)->create(['user_id' => $user_subscriber_2_id, 'project_id' => $user_subscriber_2_project_2_id]);

// ----------------------------------------------------
// public
// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
factory(User::class, 2)->create();

$user_public_1_id = 5;
$user_public_2_id = 6;

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
    'email' => 'admin@bbb.ccc',
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
    [ 'GET', "/api/projects/{$user_admin_project_1_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_admin_project_2_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_project_1_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_project_2_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_project_1_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_project_2_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_project_1_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_project_2_id}/tasks" ],
    [ 'GET', "/api/projects/{$user_admin_project_1_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_admin_project_2_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_project_1_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_demo_project_2_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_project_1_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_project_2_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_project_1_id}/relationships/tasks" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_project_2_id}/relationships/tasks" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return 2 tasks");
    $I->assertCount(2, $I->grabResponseJsonPath('$.data[*]'));
    $I->seeResponseJsonPathSame('$.data[*].type', 'tasks');

});
