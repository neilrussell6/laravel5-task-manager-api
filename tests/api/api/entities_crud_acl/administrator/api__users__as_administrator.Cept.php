<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
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

// ----------------------------------------------------

$I->comment("given 1 demo user");
$user_demo = factory(User::class)->create();
$user_demo->roles()->attach([ $demo_role->id ]);

// ----------------------------------------------------

$I->comment("given 2 subscriber users");
$subscriber_users = factory(User::class, 2)->create()->map(function($user) use ($subscriber_role) {
    $user->roles()->attach([ $subscriber_role->id ]);
    return $user;
});

// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
$public_users = factory(User::class, 2)->create();

// ----------------------------------------------------

$I->expect("should be 6 users");
$I->assertSame(6, User::all()->count());

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
// * users as administrator
//
// Endpoints
//
// * users.index
// * users.show
// * users.store
// * users.update
// * users.destroy
//
///////////////////////////////////////////////////////

// ====================================================
// users.index
// ====================================================

$I->comment("when we index all users");
$I->sendGET('/api/users');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should return all 6 users");
$I->assertCount(6, $I->grabResponseJsonPath('$.data[*]'));
$I->seeResponseJsonPathSame('$.data[*].type', 'users');

// ====================================================
// users.show
// ====================================================

$I->comment("when we view any user");
$requests = [
    [ 'GET', "/api/users/{$user_admin->id}" ],
    [ 'GET', "/api/users/{$user_demo->id}" ],
    [ 'GET', "/api/users/{$subscriber_users[0]->id}" ],
    [ 'GET', "/api/users/{$subscriber_users[1]->id}" ],
    [ 'GET', "/api/users/{$public_users[0]->id}" ],
    [ 'GET', "/api/users/{$public_users[1]->id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return requested user");
    $I->seeResponseJsonPathRegex('$.data.id', '/\d+/');

});

// ====================================================
// users.store
// ====================================================

$I->comment("when we store a user");
$user = Fixtures::get('user');
$I->sendPOST('/api/users', $user);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should return new user's id");
$I->seeResponseJsonPathSame('$.data.type', 'users');
$I->seeResponseJsonPathSame('$.data.id', '7');

$new_user_1_id = intval($I->grabResponseJsonPath('$.data.id')[0]);
$new_user_1 = User::find($new_user_1_id);

// ====================================================
// users.update
// ====================================================

$I->comment("when we update any user");
$user = [
    'data' => [
        'type' => 'users',
        'attributes' => [
            'first_name' => "AAABBBCCC",
        ]
    ]
];
$requests = [
    [ 'PATCH', "/api/users/{$user_admin->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_admin->id ] ]) ],
    [ 'PATCH', "/api/users/{$user_demo->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_demo->id ] ]) ],
    [ 'PATCH', "/api/users/{$subscriber_users[0]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $subscriber_users[0]->id ] ]) ],
    [ 'PATCH', "/api/users/{$subscriber_users[1]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $subscriber_users[1]->id ] ]) ],
    [ 'PATCH', "/api/users/{$public_users[0]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $public_users[0]->id ] ]) ],
    [ 'PATCH', "/api/users/{$public_users[1]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $public_users[1]->id ] ]) ],
    [ 'PATCH', "/api/users/{$new_user_1_id}", array_merge_recursive($user, [ 'data' => [ 'id' => $new_user_1_id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

});

$I->expect("should have the first_name of all users");
$I->seeJsonPathSame(User::all()->toArray(), '$[*].first_name', "AAABBBCCC");

// ====================================================
// users.destroy
// ====================================================

$I->comment("when we delete any user (excluding our own)");
$requests = [
//    [ 'DELETE', "/api/users/{$user_admin->id}" ],
    [ 'DELETE', "/api/users/{$user_demo->id}" ],
    [ 'DELETE', "/api/users/{$subscriber_users[0]->id}" ],
    [ 'DELETE', "/api/users/{$subscriber_users[1]->id}" ],
    [ 'DELETE', "/api/users/{$public_users[0]->id}" ],
    [ 'DELETE', "/api/users/{$public_users[1]->id}" ],
    [ 'DELETE', "/api/users/{$new_user_1_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

});

$I->expect("should have deleted all users (expect our own)");
$I->assertSame(1, User::all()->count());

// ----------------------------------------------------

$I->comment("when we delete our own user record");
$I->sendDELETE("/api/users/{$user_admin->id}");

$I->expect("should return 204 HTTP code");
$I->seeResponseCodeIs(HttpCode::NO_CONTENT);

$I->expect("should have deleted all users");
$I->assertSame(0, User::all()->count());
