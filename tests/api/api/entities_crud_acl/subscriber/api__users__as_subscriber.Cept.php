<?php

use App\Models\Role;
use App\Models\User;
use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;

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
// * users as subscriber
//
// Endpoints
//
// * users.index
// * users.store
// * users.update
// * users.destroy
// * users.show
//
///////////////////////////////////////////////////////

// ====================================================
// users.index
// ====================================================

$I->comment("when we index all users");
$I->sendGET('/api/users');

$I->expect("should return 403 HTTP code");
$I->seeResponseCodeIs(HttpCode::FORBIDDEN);

$I->expect("should return an errors array");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');

// ====================================================
// users.show
// ====================================================

$I->comment("when we view our own user record");
$I->sendGET("/api/users/{$subscriber_users[0]->id}");

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should return requested user");
$I->seeResponseJsonPathSame('$.data.id', "{$subscriber_users[0]->id}");

// ----------------------------------------------------

$I->comment("when we view any user other than our own");
$requests = [
    [ 'GET', "/api/users/{$user_admin->id}" ],
    [ 'GET', "/api/users/{$user_demo->id}" ],
//    [ 'GET', "/api/users/{$subscriber_users[0]->id}" ],
    [ 'GET', "/api/users/{$subscriber_users[1]->id}" ],
    [ 'GET', "/api/users/{$public_users[0]->id}" ],
    [ 'GET', "/api/users/{$public_users[1]->id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});

// ====================================================
// users.store
// ====================================================

$I->comment("when we store a user");
$user = Fixtures::get('user');
$I->sendPOST('/api/users', $user);

$I->expect("should return 403 HTTP code");
$I->seeResponseCodeIs(HttpCode::FORBIDDEN);

$I->expect("should return an errors array");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');

// ====================================================
// users.update
// ====================================================

$I->comment("when we update our own user record");
$user = [
    'data' => [
        'type' => 'users',
        'attributes' => [
            'first_name' => "AAABBBCCC",
        ]
    ]
];
$I->sendPATCH("/api/users/{$subscriber_users[0]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $subscriber_users[0]->id ] ]));

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should have updated the first_name of our user record");
$I->assertSame(User::find($subscriber_users[0]->id)->first_name, "AAABBBCCC");

// ----------------------------------------------------

$I->comment("when we update any user other than our own");
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
//    [ 'PATCH', "/api/users/{$subscriber_users[0]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $subscriber_users[0]->id ] ]) ],
    [ 'PATCH', "/api/users/{$subscriber_users[1]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $subscriber_users[1]->id ] ]) ],
    [ 'PATCH', "/api/users/{$public_users[0]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $public_users[0]->id ] ]) ],
    [ 'PATCH', "/api/users/{$public_users[1]->id}", array_merge_recursive($user, [ 'data' => [ 'id' => $public_users[1]->id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});

// ====================================================
// users.destroy
// ====================================================

$I->comment("when we delete any user (including our own)");
$requests = [
    [ 'DELETE', "/api/users/{$user_admin->id}" ],
    [ 'DELETE', "/api/users/{$user_demo->id}" ],
    [ 'DELETE', "/api/users/{$subscriber_users[0]->id}" ],
    [ 'DELETE', "/api/users/{$subscriber_users[1]->id}" ],
    [ 'DELETE', "/api/users/{$public_users[0]->id}" ],
    [ 'DELETE', "/api/users/{$public_users[1]->id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});
