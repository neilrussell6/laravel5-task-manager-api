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

$I->comment("given 2 users");
factory(User::class, 2)->create();
$I->assertSame(2, User::all()->count());

///////////////////////////////////////////////////////
//
// Test
//
// * users
//
///////////////////////////////////////////////////////

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

// ====================================================
// index users
// ====================================================

$I->comment("when we index all users");
$user = Fixtures::get('user');
$I->sendGET('/api/users');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users$/');
$I->seeResponseJsonPathRegex('$.data[*].id', '/\d+/');
$I->seeResponseJsonPathSame('$.data[*].type', "users");
$I->seeResponseJsonPathType('$.data[*].attributes', 'array:!empty');

// ====================================================
// view user
// ====================================================

$I->comment("when we view a user");
$user = Fixtures::get('user');
$I->sendGET('/api/users/1');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users\/1$/');
$I->seeResponseJsonPathSame('$.data.id', "1");
$I->seeResponseJsonPathSame('$.data.type', "users");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

// ====================================================
// create user
// ====================================================

$I->comment("when we create a user");
$user = Fixtures::get('user');
$I->sendPOST('/api/users', $user);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users\/3$/');
$I->seeResponseJsonPathSame('$.data.id', "3");
$I->seeResponseJsonPathSame('$.data.type', "users");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

$I->expect("should create 1 new record");
$I->assertSame(3, User::all()->count());

// ====================================================
// update user
// ====================================================

$I->comment("when we update a user");
$user = [
    'data' => [
        'id' => 3,
        'type' => 'users',
        'attributes' => [
            'name' => "AAABBBCCC",
        ]
    ]
];

$I->sendPATCH('/api/users/3', $user);

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users\/3$/');
$I->seeResponseJsonPathSame('$.data.id', "3");
$I->seeResponseJsonPathSame('$.data.type', "users");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

$I->expect("should not create any new records");
$I->assertSame(3, User::all()->count());

$I->expect("should update user one's name");
$I->assertSame("AAABBBCCC", User::find(3)->name);

// ====================================================
// delete user
// ====================================================

$I->comment("when we delete a user");
$I->sendDELETE('/api/users/3');

$I->expect("should return 405 HTTP code");
$I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');
$I->seeResponseJsonPathSame('$.errors[*].status', 405);
$I->seeResponseJsonPathType('$.errors[*].detail', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[*].title', 'string:!empty');

$I->expect("should not delete any records");
$I->assertSame(3, User::all()->count());
