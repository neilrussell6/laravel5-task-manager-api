<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
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

$password = "abcABC123!";

// admin

$I->comment("given 1 admin user");
$user_admin = factory(User::class)->create();
$user_admin->roles()->attach([ $user_admin->id ]);
$I->assertCount(1, User::all());

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
$credentials['data']['attributes']['email'] = $user_admin->email;
$credentials['data']['attributes']['password'] = $password;

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

// ----------------------------------------------------

$I->comment("when we make a with a valid Authorization header");
$I->haveHttpHeader('Authorization', "Bearer {$access_token}");
$I->sendGET('/api/users');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);
