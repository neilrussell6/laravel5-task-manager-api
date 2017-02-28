<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Role as RoleModel;
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

// ----------------------------------------------------

$email = "aaa@bbb.ccc";
$password = "abcABC123!";

// ----------------------------------------------------
// admin
// ----------------------------------------------------

$I->comment("given 1 admin user");
factory(User::class, 1)->create([
    'email' => "aaa@bbb.ccc",
    'password' => Hash::make($password),
]);
$user_admin_id = 1;
$user_admin = User::find($user_admin_id);
$user_admin->assignRole(RoleModel::ROLE_ADMINISTRATOR);

// tasks

$I->comment("given 2 tasks owned by admin user");
factory(Task::class, 2)->create(['user_id' => $user_admin_id]);
$user_admin_task_1_id = 1;
$user_admin_task_2_id = 2;

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
factory(User::class, 1)->create([
    'email' => "bbb@ccc.ddd",
    'password' => Hash::make($password),
]);
$user_demo_id = 2;
$user_demo = User::find($user_demo_id);
$user_demo->assignRole(RoleModel::ROLE_DEMO);

// projects

$I->comment("given 1 project owned by demo user");
factory(Project::class, 2)->create(['user_id' => $user_demo_id]);
$user_demo_project_1_id = 1;

// tasks

$I->comment("given 2 tasks owned by demo user");
factory(Task::class, 2)->create(['user_id' => $user_demo_id]);
$user_demo_task_1_id = 3;
$user_demo_task_2_id = 4;

// ----------------------------------------------------
// subscriber
// ----------------------------------------------------

$I->comment("given 2 subscriber users");
factory(User::class, 2)->create()->each(function($user) {
    $user->assignRole(RoleModel::ROLE_SUBSCRIBER);
});

$user_subscriber_1_id = 3;
$user_subscriber_2_id = 4;

// tasks

$I->comment("given 2 tasks owned by each subscriber user");
factory(Task::class, 2)->create(['user_id' => $user_subscriber_1_id]);
$user_subscriber_1_task_1_id = 5;
$user_subscriber_1_task_2_id = 6;
factory(Task::class, 2)->create(['user_id' => $user_subscriber_2_id]);
$user_subscriber_2_task_1_id = 7;
$user_subscriber_2_task_2_id = 8;

// ----------------------------------------------------
// public
// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
factory(User::class, 2)->create();

$user_public_1_id = 5;
$user_public_2_id = 6;

// tasks

$I->comment("given no tasks owned by public users");

// ----------------------------------------------------

$I->expect("should be 6 users");
$I->assertSame(6, User::all()->count());

$I->expect("should be 8 tasks");
$I->assertSame(8, Task::all()->count());

// ====================================================
// authenticate user and set headers
// ====================================================

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $email;
$credentials['data']['attributes']['password'] = $password;

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

$I->haveHttpHeader('Authorization', "Bearer {$access_token}");

///////////////////////////////////////////////////////
//
// Test
//
// * tasks as administrator
//
// Endpoints
//
// * tasks.index
// * tasks.store
// * tasks.update
// * tasks.destroy
// * tasks.show
//
///////////////////////////////////////////////////////

// ====================================================
// tasks.index
// ====================================================

$I->comment("when we index all tasks");
$I->sendGET('/api/tasks');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should return all 8 tasks");
$I->assertCount(8, $I->grabResponseJsonPath('$.data[*]'));
$I->seeResponseJsonPathSame('$.data[*].type', 'tasks');

// ====================================================
// tasks.show
// ====================================================

$I->comment("when we view any user's task");
$requests = [
    [ 'GET', "/api/tasks/{$user_admin_task_1_id}" ],
    [ 'GET', "/api/tasks/{$user_admin_task_2_id}" ],
    [ 'GET', "/api/tasks/{$user_demo_task_1_id}" ],
    [ 'GET', "/api/tasks/{$user_demo_task_2_id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_1_task_1_id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_1_task_2_id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_task_1_id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_task_2_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return requested task");
    $I->seeResponseJsonPathRegex('$.data.id', '/\d+/');

});

// ====================================================
// tasks.store
// ====================================================

$I->comment("when we store a task");
$task = Fixtures::get('task');
$I->sendPOST('/api/tasks', $task);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should return new task's id");
$I->seeResponseJsonPathSame('$.data.type', 'tasks');
$I->seeResponseJsonPathSame('$.data.id', '9');

$I->expect("should return relationships, including authenticated user as owner");
$I->seeResponseJsonPathType('$.data.relationships.owner', 'array:!empty');
$I->seeResponseJsonPathType('$.data.relationships.project', 'array:!empty');

$I->expect("should create 1 new record");
$I->assertSame(9, Task::all()->count());

$new_task_1_id = $I->grabResponseJsonPath('$.data.id')[0];
$new_task_1 = Task::find($new_task_1_id);

$I->expect("new task should belong to authenticated user (because none was explicitly set)");
$I->assertSame($user_admin_id, $new_task_1->owner->id);

$I->expect("new task should belong to no project");
$I->assertNull($new_task_1->project);

// ====================================================
// projects.store (with owner & project relationships)
// ====================================================

$I->comment("when we store a task and set the owner to demo user and the project to project 1");
$task = Fixtures::get('task');
$task['data']['relationships'] = [
    'owner' => [
        'data' => [
            'type' => 'users',
            'id' => $user_demo_id
        ]
    ],
    'project' => [
        'data' => [
            'type' => 'projects',
            'id' => $user_demo_project_1_id
        ]
    ]
];
$I->sendPOST('/api/tasks', $task);
$I->expect("should return relationships, including owner & project");
$I->seeResponseJsonPathType('$.data.relationships.owner', 'array:!empty');
$I->seeResponseJsonPathType('$.data.relationships.project', 'array:!empty');

$I->expect("should create 1 new record");
$I->assertSame(10, Task::all()->count());

$new_task_2_id = $I->grabResponseJsonPath('$.data.id')[0];
$new_task_2 = Task::find($new_task_2_id);

$I->expect("new task should belong to demo user (because it was explicitly set)");
$I->assertSame($user_demo_id, $new_task_2->owner->id);

$I->expect("new task should belong to project 1 (because it was explicitly set)");
$I->assertSame($user_demo_project_1_id, $new_task_2->project->id);

// ====================================================
// tasks.update
// ====================================================

$I->comment("when we update any user's task (excluding public who can't have tasks)");
$task = [
    'data' => [
        'type' => 'tasks',
        'attributes' => [
            'name' => "AAABBBCCC",
        ]
    ]
];
$requests = [
    [ 'PATCH', "/api/tasks/{$user_admin_task_1_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_admin_task_1_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_admin_task_2_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_admin_task_2_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_demo_task_1_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_demo_task_1_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_demo_task_2_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_demo_task_2_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_task_1_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_1_task_1_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_task_2_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_1_task_2_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_task_1_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_2_task_1_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_task_2_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_2_task_2_id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

});

// ====================================================
// tasks.destroy
// ====================================================

$I->comment("when we delete any user's task");
$requests = [
    [ 'DELETE', "/api/tasks/{$user_admin_task_1_id}" ],
    [ 'DELETE', "/api/tasks/{$user_admin_task_2_id}" ],
    [ 'DELETE', "/api/tasks/{$user_demo_task_1_id}" ],
    [ 'DELETE', "/api/tasks/{$user_demo_task_2_id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_task_1_id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_task_2_id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_task_1_id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_task_2_id}" ],
    [ 'DELETE', "/api/tasks/{$new_task_1_id}" ],
    [ 'DELETE', "/api/tasks/{$new_task_2_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    $I->expect("should also delete all that task's tasks");
    // TODO: test

});

$I->expect("should have deleted all tasks");
$I->assertSame(0, Task::all()->count());
