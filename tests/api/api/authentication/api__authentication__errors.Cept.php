<?php

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

$I->comment("given 1 users");
factory(User::class, 1)->create();
$I->assertSame(1, User::all()->count());

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
// no Authorization header (token_not_provided)
// ====================================================

$I->comment("when we make a query without the Authorization header");
$I->sendGET('/api/users');

$I->expect("should return 401 HTTP code");
$I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');
$I->seeResponseJsonPathSame('$.errors[*].status', 401);
$I->seeResponseJsonPathType('$.errors[*].title', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[*].detail', 'string:!empty');

// ====================================================
// invalid Authorization header (token_expired)
// ====================================================

// TODO: test

// ====================================================
// invalid Authorization header (token_invalid)
// ====================================================

$I->haveHttpHeader('Authorization', 'Bearer 1234');

$I->comment("when we make a query with an invalid Authorization header");
$I->sendGET('/api/users');

$I->expect("should return 401 HTTP code");
$I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');
$I->seeResponseJsonPathSame('$.errors[*].status', 401);
$I->seeResponseJsonPathType('$.errors[*].title', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[*].detail', 'string:!empty');

// ====================================================
// no User for Authorization header (user_not_found)
// ====================================================

// TODO: test
