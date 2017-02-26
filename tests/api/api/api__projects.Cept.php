<?php

use App\Models\Project;
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

$I->comment("given 1 auth user");
$email = "aaa@bbb.ccc";
$password = "abcABC123!";
factory(User::class, 1)->create([
    'email' => $email,
    'password' => \Illuminate\Support\Facades\Hash::make($password),
]);
$I->assertCount(1, User::all());

$I->comment("given 2 projects");
factory(Project::class, 2)->create();
$I->assertSame(2, Project::all()->count());

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
// * projects
//
///////////////////////////////////////////////////////

// ====================================================
// index projects
// ====================================================

$I->comment("when we index all projects");
$project = Fixtures::get('project');
$I->sendGET('/api/projects');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/projects$/');
$I->seeResponseJsonPathRegex('$.data[*].id', '/\d+/');
$I->seeResponseJsonPathSame('$.data[*].type', "projects");
$I->seeResponseJsonPathType('$.data[*].attributes', 'array:!empty');

// ====================================================
// view project
// ====================================================

$I->comment("when we view a project");
$project = Fixtures::get('project');
$I->sendGET('/api/projects/1');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/projects\/1$/');
$I->seeResponseJsonPathSame('$.data.id', "1");
$I->seeResponseJsonPathSame('$.data.type', "projects");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

// ====================================================
// create project
// ====================================================

$I->comment("when we create a project");
$project = Fixtures::get('project');
$I->sendPOST('/api/projects', $project);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/projects\/3$/');
$I->seeResponseJsonPathSame('$.data.id', "3");
$I->seeResponseJsonPathSame('$.data.type', "projects");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

$I->expect("should create 1 new record");
$I->assertSame(3, Project::all()->count());

// ====================================================
// update project
// ====================================================

$I->comment("when we update a project");
$project = [
    'data' => [
        'id' => 3,
        'type' => 'projects',
        'attributes' => [
            'name' => "AAABBBCCC",
        ]
    ]
];

$I->sendPATCH('/api/projects/3', $project);

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should respond with the following structure");
$I->seeResponseJsonPathRegex('$.links.self', '/^http\:\/\/[^\/]+\/api\/projects\/3$/');
$I->seeResponseJsonPathSame('$.data.id', "3");
$I->seeResponseJsonPathSame('$.data.type', "projects");
$I->seeResponseJsonPathType('$.data.attributes', 'array:!empty');

$I->expect("should not create any new records");
$I->assertSame(3, Project::all()->count());

$I->expect("should update project one's name");
$I->assertSame("AAABBBCCC", Project::find(3)->name);

// ====================================================
// delete project
// ====================================================

$I->comment("when we create a project");
$I->sendDELETE('/api/projects/3', Fixtures::get('project'));

$I->expect("should return 204 HTTP code");
$I->seeResponseCodeIs(HttpCode::NO_CONTENT);

$I->expect("should return no content");
$I->seeResponseEquals(null);
