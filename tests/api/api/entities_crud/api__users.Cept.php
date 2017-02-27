<?php

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

$I->comment("given 1 auth user");
$email = "aaa@bbb.ccc";
$password = "abcABC123!";
factory(User::class, 1)->create([
    'email' => $email,
    'password' => Hash::make($password),
]);

$I->comment("given 1 other user");
factory(User::class, 1)->create();

$I->assertSame(2, User::all()->count());

// ====================================================
// authenticate user and set headers
// ====================================================

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes']['email'] = $email;
$credentials['data']['attributes']['password'] = $password;

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

$I->haveHttpHeader('Authorization', "Bearer {$access_token}");

///////////////////////////////////////////////////////
//
// Test
//
// * users
//
///////////////////////////////////////////////////////

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

$I->expect("should return 405 HTTP code");
$I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');
$I->seeResponseJsonPathSame('$.errors[*].status', 405);
$I->seeResponseJsonPathType('$.errors[*].detail', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[*].title', 'string:!empty');

$I->expect("should not create any records");
$I->assertSame(2, User::all()->count());

// ====================================================
// update user
// ====================================================

$I->comment("when we update a user");
$user = [
    'data' => [
        'id' => 2,
        'type' => 'users',
        'attributes' => [
            'first_name' => "AAABBBCCC",
        ]
    ]
];
$I->sendPATCH('/api/users/2', $user);

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/users\/2$/');
$I->seeResponseJsonPathSame('$.data.id', "2");
$I->seeResponseJsonPathSame('$.data.type', "users");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

$I->expect("should not create any new records");
$I->assertSame(2, User::all()->count());

$I->expect("should update user's first name");
$I->assertSame("AAABBBCCC", User::find(2)->first_name);

// ====================================================
// delete user
// ====================================================

$I->comment("when we delete a user");
$I->sendDELETE('/api/users/2');

$I->expect("should return 405 HTTP code");
$I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathType('$.errors', 'array:!empty');
$I->seeResponseJsonPathSame('$.errors[*].status', 405);
$I->seeResponseJsonPathType('$.errors[*].detail', 'string:!empty');
$I->seeResponseJsonPathType('$.errors[*].title', 'string:!empty');

$I->expect("should not delete any records");
$I->assertSame(2, User::all()->count());
