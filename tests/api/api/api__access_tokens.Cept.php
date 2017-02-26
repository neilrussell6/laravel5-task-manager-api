<?php

use \Mockery as m;
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

$I->comment("given 1 user");
$email = "aaa@bbb.ccc";
$password = "abcABC123!";
factory(User::class, 1)->create([
    'email' => $email,
    'password' => \Illuminate\Support\Facades\Hash::make($password),
]);
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
// * access tokens
//
///////////////////////////////////////////////////////

// ====================================================
// create access token (success)
// ====================================================

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $email;
$credentials['data']['attributes']['password'] = $password;

$I->comment("when we create an access token");
$I->sendPOST('/api/access_tokens', $credentials);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/access_tokens$/');
$I->seeResponseJsonPathRegex('$.data.attributes.access_token', '/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/');

// ====================================================
// create access token (wrong password)
// ====================================================

\Tymon\JWTAuth\Facades\JWTAuth::shouldReceive('attempt')->andReturn(false);

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $email;
$credentials['data']['attributes']['password'] = "wrong password";

$I->comment("when we create an access token with invalid credentials");
$I->sendPOST('/api/access_tokens', $credentials);

$I->expect("should return 401 HTTP code");
$I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');
$I->seeResponseJsonPathSame('$.errors[*].status', 401);
$I->seeResponseJsonPathType('$.errors[*].detail', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[*].title', 'string:!empty');

$I->expect("should not return an access token");
$I->seeNotResponseJsonPath('$.data.attributes.access_token');
