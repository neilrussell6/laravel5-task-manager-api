<?php

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
// * each user's tasks as administrator
//
// Endpoints
//
// * users.tasks.index
// * users.relationships.tasks.index
//
///////////////////////////////////////////////////////

// ====================================================
// users.tasks.index
// users.relationships.tasks.index
// ====================================================

$I->comment("when we index tasks for all users (excluding public users)");
$requests = [
    [ 'GET', "/api/users/{$user_admin_id}/tasks", ],
    [ 'GET', "/api/users/{$user_demo_id}/tasks", ],
    [ 'GET', "/api/users/{$user_subscriber_1_id}/tasks", ],
    [ 'GET', "/api/users/{$user_subscriber_2_id}/tasks", ],
    [ 'GET', "/api/users/{$user_admin_id}/relationships/tasks", ],
    [ 'GET', "/api/users/{$user_demo_id}/relationships/tasks", ],
    [ 'GET', "/api/users/{$user_subscriber_1_id}/relationships/tasks", ],
    [ 'GET', "/api/users/{$user_subscriber_2_id}/relationships/tasks", ],
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

$I->comment("when we index tasks for all public users");
$requests = [
    [ 'GET', "/api/users/{$user_public_1_id}/tasks", ],
    [ 'GET', "/api/users/{$user_public_2_id}/tasks", ],
    [ 'GET', "/api/users/{$user_public_1_id}/relationships/tasks", ],
    [ 'GET', "/api/users/{$user_public_2_id}/relationships/tasks", ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return no tasks");
    $I->assertCount(0, $I->grabResponseJsonPath('$.data[*]'));

});
