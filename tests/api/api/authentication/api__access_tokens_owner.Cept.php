<?php

use \Mockery as m;
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
$demo_role = Role::where('name', '=', 'demo')->first();
$subscriber_role = Role::where('name', '=', 'subscriber')->first();

$password = "abcABC123!";

// ----------------------------------------------------
// admin
// ----------------------------------------------------

$I->comment("given 1 admin user");
$user_admin = factory(User::class)->create();
$user_admin->roles()->attach([ $user_admin->id ]);

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
// * access_tokens.owner.view
//
///////////////////////////////////////////////////////

// ====================================================
// access_tokens.owner.view (success)
// ====================================================

$I->comment("when we view the owner of our access token");
$I->sendGET('/api/access_tokens/owner');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should return the access token's owner");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/access_tokens\/owner$/');
$I->seeResponseJsonPathSame('$.data.type', 'users');
$I->seeResponseJsonPathSame('$.data.id', "{$user_admin->id}");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');
$I->seeNotResponseJsonPath('$.data.relationships');

// ====================================================
// access_tokens.relationships.owner.view (success)
// ====================================================

$I->comment("when we view the owner relationship of our access token");
$I->sendGET('/api/access_tokens/relationships/owner');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should return the access token's owner");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/access_tokens\/relationships\/owner$/');
$I->seeResponseJsonPathSame('$.data.type', 'users');
$I->seeResponseJsonPathSame('$.data.id', "{$user_admin->id}");
$I->seeNotResponseJsonPath('$.data.attributes');
$I->seeNotResponseJsonPath('$.data.relationships');
