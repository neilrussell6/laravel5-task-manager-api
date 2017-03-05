<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
use App\Models\Task;
use App\Models\Role;
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

$I->comment("given 2 tasks owned by each subscriber user, but with no project");
$user_subscriber_1_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[0]->id]);
$user_subscriber_2_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[1]->id]);

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

$I->expect("should be 8 tasks");
$I->assertSame(8, Task::all()->count());

// ====================================================
// authenticate user and set headers
// ====================================================

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes'] = [
    'email' => $subscriber_users[0]->email,
    'password' => $password,
];

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

$I->haveHttpHeader('Authorization', "Bearer {$access_token}");

///////////////////////////////////////////////////////
//
// Test
//
// * each user's tasks as subscriber
//
// Endpoints
//
// * users.tasks.index
// * users.relationships.projects.index
//
///////////////////////////////////////////////////////

// ====================================================
// users.tasks.index
// users.relationships.tasks.index
// ====================================================

$I->comment("when we index the tasks of our user record");
$requests = [
    [ 'GET', "/api/users/{$subscriber_users[0]->id}/tasks" ],
    [ 'GET', "/api/users/{$subscriber_users[0]->id}/relationships/tasks" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return 2 tasks");
    $I->assertCount(2, $I->grabResponseJsonPath('$.data[*]'));
    $I->seeResponseJsonPathSame('$.data[*].type', 'tasks');

});

// ----------------------------------------------------

$I->comment("when we index the tasks of any other users");
$requests = [
    [ 'GET', "/api/users/{$user_admin->id}/tasks" ],
    [ 'GET', "/api/users/{$user_demo->id}/tasks" ],
//    [ 'GET', "/api/users/{$subscriber_users[0]->id}/tasks" ],
    [ 'GET', "/api/users/{$subscriber_users[1]->id}/tasks" ],
    [ 'GET', "/api/users/{$user_admin->id}/relationships/tasks" ],
    [ 'GET', "/api/users/{$user_demo->id}/relationships/tasks" ],
//    [ 'GET', "/api/users/{$subscriber_users[0]->id}/relationships/tasks" ],
    [ 'GET', "/api/users/{$subscriber_users[1]->id}/relationships/tasks" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});
