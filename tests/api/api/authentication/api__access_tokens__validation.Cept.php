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
$user = factory(User::class)->create([ 'username' => 'administrator' ]);
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
// access_tokens.store (only password)
// ====================================================

$credentials = [
    'data' => [
        'type' => 'access_tokens',
        'attributes' => [
            'password' => $password
        ]
    ]
];

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

// ====================================================
// access_tokens.store (only email)
// ====================================================

$credentials = [
    'data' => [
        'type' => 'access_tokens',
        'attributes' => [
            'email' => $user->email
        ]
    ]
];

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

// ====================================================
// access_tokens.store (only username)
// ====================================================

$credentials = [
    'data' => [
        'type' => 'access_tokens',
        'attributes' => [
            'email' => $user->username
        ]
    ]
];

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

// ====================================================
// access_tokens.store (wrong username)
// ====================================================

JWTAuth::shouldReceive('attempt')->andReturn(false);

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['username'] = "wrong username";
$credentials['data']['attributes']['password'] = $password;

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

// ====================================================
// access_tokens.store (wrong email)
// ====================================================

JWTAuth::shouldReceive('attempt')->andReturn(false);

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = "wrong email";
$credentials['data']['attributes']['password'] = $password;

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

// ====================================================
// access_tokens.store (wrong password)
// ====================================================

JWTAuth::shouldReceive('attempt')->andReturn(false);

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $user->email;
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
