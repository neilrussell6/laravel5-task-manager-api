<?php

use \Mockery as m;
use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
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
// * access_tokens.store (exceptions)
//
///////////////////////////////////////////////////////

// ====================================================
// access_tokens.store (JWT Exception)
// ====================================================

\Tymon\JWTAuth\Facades\JWTAuth::shouldReceive('attempt')->andThrow(new \Tymon\JWTAuth\Exceptions\JWTException());

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $user->email;
$credentials['data']['attributes']['password'] = $password;

$I->comment("when we create an access token, and there is an error creating the access token");
$I->sendPOST('/api/access_tokens', $credentials);

$I->expect("should return 500 HTTP code");
$I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');
$I->seeResponseJsonPathSame('$.errors[*].status', 500);
$I->seeResponseJsonPathType('$.errors[*].detail', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[*].title', 'string:!empty');

$I->expect("should not return an access token");
$I->seeNotResponseJsonPath('$.data.attributes.access_token');
