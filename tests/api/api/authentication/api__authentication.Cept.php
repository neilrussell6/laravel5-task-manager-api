<?php

use App\Models\Role;
use App\Models\User;
use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use Illuminate\Support\Facades\Hash;

$I = new ApiTester($scenario);

///////////////////////////////////////////////////////
//
// before
//
///////////////////////////////////////////////////////

// ====================================================
// create data
// ====================================================

$email = "aaa@bbb.ccc";
$password = "abcABC123!";

$I->comment("given 1 user");
factory(User::class, 1)->create([
    'email' => $email,
    'password' => Hash::make($password),
]);

$I->assertCount(1, User::all());

$user_admin = User::find(1);
$user_admin->assignRole(Role::ROLE_ADMINISTRATOR);

// ====================================================
// set headers
// ====================================================

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

///////////////////////////////////////////////////////
//
// Test
//
// * authentication
//
///////////////////////////////////////////////////////

// ====================================================
// Authorization header
// ====================================================

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $email;
$credentials['data']['attributes']['password'] = $password;

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

// ----------------------------------------------------

$I->comment("when we make a with a valid Authorization header");
$I->haveHttpHeader('Authorization', "Bearer {$access_token}");
$I->sendGET('/api/users');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);
