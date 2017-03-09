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

// tasks

$I->comment("given 2 tasks owned by admin user");
$user_admin_tasks = factory(Task::class, 2)->create(['user_id' => $user_admin->id]);

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

$I->comment("given 2 tasks owned by demo user, but with no project");
$user_demo_tasks = factory(Task::class, 2)->create(['user_id' => $user_demo->id]);

// ----------------------------------------------------
// subscriber
// ----------------------------------------------------

$I->comment("given 2 subscriber users");
$subscriber_users = factory(User::class, 2)->create()->map(function($user) use ($subscriber_role) {
    $user->roles()->attach([ $subscriber_role->id ]);
    return $user;
});

// tasks

$I->comment("given 2 tasks owned by each subscriber user");
$user_subscriber_1_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[0]->id]);
$user_subscriber_2_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[1]->id]);

// ----------------------------------------------------
// public
// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
$public_users = factory(User::class, 2)->create();

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
// * tasks as administrator
//
// Endpoints
//
// * tasks.index
// * tasks.store
// * tasks.store (with owner & project relationships)
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
    [ 'GET', "/api/tasks/{$user_admin_tasks[0]->id}" ],
    [ 'GET', "/api/tasks/{$user_admin_tasks[1]->id}" ],
    [ 'GET', "/api/tasks/{$user_demo_tasks[0]->id}" ],
    [ 'GET', "/api/tasks/{$user_demo_tasks[1]->id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_1_tasks[0]->id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_1_tasks[1]->id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_tasks[0]->id}" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_tasks[1]->id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

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

$new_task_1_id = intval($I->grabResponseJsonPath('$.data.id')[0]);
$new_task_1 = Task::find($new_task_1_id);

$I->expect("new task should belong to authenticated user (because none was explicitly set)");
$I->assertSame($user_admin->id, $new_task_1->owner->id);

$I->expect("new task should belong to no project");
$I->assertNull($new_task_1->project);

// ====================================================
// projects.store (with owner & project relationships)
// TODO: test once implemented
// ====================================================

$I->comment("when we store a task and set the owner to demo user and the project to project 1");
$task = Fixtures::get('task');
$task['data']['relationships'] = [
    'owner' => [
        'data' => [
            'type' => 'users',
            'id' => $user_demo->id
        ]
    ],
    'project' => [
        'data' => [
            'type' => 'projects',
            'id' => $user_demo_projects[0]->id
        ]
    ]
];
$I->sendPOST('/api/tasks', $task);
$I->expect("should return relationships, including owner & project");
$I->seeResponseJsonPathType('$.data.relationships.owner', 'array:!empty');
$I->seeResponseJsonPathType('$.data.relationships.project', 'array:!empty');

$I->expect("should create 1 new record");
$I->assertSame(10, Task::all()->count());

$new_task_2_id = intval($I->grabResponseJsonPath('$.data.id')[0]);
$new_task_2 = Task::find($new_task_2_id);

$I->expect("new task should belong to demo user (because it was explicitly set)");
$I->assertSame($user_demo->id, $new_task_2->owner->id);

$I->expect("new task should belong to project 1 (because it was explicitly set)");
$I->assertSame($user_demo_projects[0]->id, $new_task_2->project->id);

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
    [ 'PATCH', "/api/tasks/{$user_admin_tasks[0]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_admin_tasks[0]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_admin_tasks[1]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_admin_tasks[1]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_demo_tasks[0]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_demo_tasks[0]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_demo_tasks[1]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_demo_tasks[1]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_tasks[0]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_1_tasks[0]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_tasks[1]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_1_tasks[1]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_tasks[0]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_2_tasks[0]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_tasks[1]->id}", array_merge_recursive($task, [ 'data' => [ 'id' => $user_subscriber_2_tasks[1]->id ] ]) ],
    [ 'PATCH', "/api/tasks/{$new_task_1_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $new_task_1_id ] ]) ],
    [ 'PATCH', "/api/tasks/{$new_task_2_id}", array_merge_recursive($task, [ 'data' => [ 'id' => $new_task_2_id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

});

$I->expect("should have updated the name of each tasks");
$I->seeJsonPathSame(Task::all()->toArray(), '$[*].name', "AAABBBCCC");

// ====================================================
// tasks.destroy
// ====================================================

$I->comment("when we delete any user's task");
$requests = [
    [ 'DELETE', "/api/tasks/{$user_admin_tasks[0]->id}" ],
    [ 'DELETE', "/api/tasks/{$user_admin_tasks[1]->id}" ],
    [ 'DELETE', "/api/tasks/{$user_demo_tasks[0]->id}" ],
    [ 'DELETE', "/api/tasks/{$user_demo_tasks[1]->id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_tasks[0]->id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_tasks[1]->id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_tasks[0]->id}" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_tasks[1]->id}" ],
    [ 'DELETE', "/api/tasks/{$new_task_1_id}" ],
    [ 'DELETE', "/api/tasks/{$new_task_2_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    $I->expect("should also delete all that task's tasks");
    // TODO: test

});

$I->expect("should have deleted all tasks");
$I->assertSame(0, Task::all()->count());
