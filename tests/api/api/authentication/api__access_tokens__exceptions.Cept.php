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
// * access tokens (exceptions)
//
///////////////////////////////////////////////////////

// ====================================================
// create access token (JWT Exception)
// ====================================================

\Tymon\JWTAuth\Facades\JWTAuth::shouldReceive('attempt')->andThrow(new \Tymon\JWTAuth\Exceptions\JWTException());

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $email;
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
