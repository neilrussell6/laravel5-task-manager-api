<?php

use \Mockery as m;
use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use Tymon\JWTAuth\Facades\JWTAuth;
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

$password = "abcABC123!";

$I->comment("given 1 user");
$user = factory(User::class)->create();
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
// * access_tokens.store
//
///////////////////////////////////////////////////////

// ====================================================
// access_tokens.store (via username)
// ====================================================

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['username'] = $user->username;
$credentials['data']['attributes']['password'] = $password;

$I->comment("when we create an access token");
$I->sendPOST('/api/access_tokens', $credentials);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/access_tokens$/');
$I->seeResponseJsonPathRegex('$.data.attributes.access_token', '/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/');

// ====================================================
// access_tokens.store (via email)
// ====================================================

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $user->email;
$credentials['data']['attributes']['password'] = $password;

$I->comment("when we create an access token");
$I->sendPOST('/api/access_tokens', $credentials);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/access_tokens$/');
$I->seeResponseJsonPathRegex('$.data.attributes.access_token', '/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/');
