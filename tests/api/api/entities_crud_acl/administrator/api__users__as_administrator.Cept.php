<?php

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

// ----------------------------------------------------

$I->comment("given 1 demo user");
factory(User::class, 1)->create([
    'email' => "demo@abc.def",
    'password' => Hash::make($password),
]);
$user_demo_id = 2;
$user_demo = User::find($user_demo_id);
$user_demo->assignRole(RoleModel::ROLE_DEMO);

// ----------------------------------------------------

$I->comment("given 2 subscriber users");
factory(User::class, 2)->create()->each(function($user) {
    $user->assignRole(RoleModel::ROLE_SUBSCRIBER);
});

$user_subscriber_1_id = 3;
$user_subscriber_2_id = 4;

// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
factory(User::class, 2)->create();

$user_public_1_id = 5;
$user_public_2_id = 6;

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
    [ 'GET', "/api/users/{$user_admin_id}" ],
    [ 'GET', "/api/users/{$user_demo_id}" ],
    [ 'GET', "/api/users/{$user_subscriber_1_id}" ],
    [ 'GET', "/api/users/{$user_subscriber_2_id}" ],
    [ 'GET', "/api/users/{$user_public_1_id}" ],
    [ 'GET', "/api/users/{$user_public_2_id}" ],
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

$new_user_1_id = $I->grabResponseJsonPath('$.data.id')[0];
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
    [ 'PATCH', "/api/users/{$user_admin_id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_admin_id ] ]) ],
    [ 'PATCH', "/api/users/{$user_demo_id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_demo_id ] ]) ],
    [ 'PATCH', "/api/users/{$user_subscriber_1_id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_subscriber_1_id ] ]) ],
    [ 'PATCH', "/api/users/{$user_subscriber_2_id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_subscriber_2_id ] ]) ],
    [ 'PATCH', "/api/users/{$user_public_1_id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_public_1_id ] ]) ],
    [ 'PATCH', "/api/users/{$user_public_2_id}", array_merge_recursive($user, [ 'data' => [ 'id' => $user_public_2_id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

});

// ====================================================
// users.destroy
// ====================================================

$I->comment("when we delete any user (excluding our own)");
$requests = [
//    [ 'DELETE', "/api/users/{$user_admin_id}" ],
    [ 'DELETE', "/api/users/{$user_demo_id}" ],
    [ 'DELETE', "/api/users/{$user_subscriber_1_id}" ],
    [ 'DELETE', "/api/users/{$user_subscriber_2_id}" ],
    [ 'DELETE', "/api/users/{$user_public_1_id}" ],
    [ 'DELETE', "/api/users/{$user_public_2_id}" ],
    [ 'DELETE', "/api/users/{$new_user_1_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

});

$I->expect("should have deleted all users (expect our own)");
$I->assertSame(1, User::all()->count());

// ----------------------------------------------------

$I->comment("when we delete our own user record");
$I->sendDELETE("/api/users/{$user_admin_id}");

$I->expect("should return 204 HTTP code");
$I->seeResponseCodeIs(HttpCode::NO_CONTENT);

$I->expect("should have deleted all users");
$I->assertSame(0, User::all()->count());
