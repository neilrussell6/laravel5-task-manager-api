<?php

use App\Models\Project;
use App\Models\Role as RoleModel;
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

$password = "abcABC123!";

// ----------------------------------------------------
// admin
// ----------------------------------------------------

$I->comment("given 1 admin user");
factory(User::class, 1)->create([
    'email' => 'admin@bbb.ccc',
    'password' => Hash::make($password),
]);
$user_admin_id = 1;
$user_admin = User::find($user_admin_id);
$user_admin->assignRole(RoleModel::ROLE_ADMINISTRATOR);

// projects

$I->comment("given 2 projects owned by admin user");
factory(Project::class, 2)->create(['user_id' => $user_admin_id]);
$user_admin_project_1_id = 1;
$user_admin_project_2_id = 2;

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
factory(User::class, 1)->create([
    'email' => "demo@abc.def",
    'password' => Hash::make($password),
]);
$user_demo_id = 2;
$user_demo = User::find($user_demo_id);
$user_demo->assignRole(RoleModel::ROLE_DEMO);

// projects

$I->comment("given 2 projects owned by demo user");
factory(Project::class, 2)->create(['user_id' => $user_demo_id]);
$user_demo_project_1_id = 3;
$user_demo_project_2_id = 4;

// ----------------------------------------------------
// subscriber
// ----------------------------------------------------

$I->comment("given 2 subscriber users");
factory(User::class, 2)->create()->each(function($user) {
    $user->assignRole(RoleModel::ROLE_SUBSCRIBER);
});

$user_subscriber_1_id = 3;
$user_subscriber_2_id = 4;

// projects

$I->comment("given 2 projects owned by each subscriber user");
factory(Project::class, 2)->create(['user_id' => $user_subscriber_1_id]);
$user_subscriber_1_project_1_id = 5;
$user_subscriber_1_project_2_id = 6;
factory(Project::class, 2)->create(['user_id' => $user_subscriber_2_id]);
$user_subscriber_2_project_1_id = 7;
$user_subscriber_2_project_2_id = 8;

// ----------------------------------------------------
// public
// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
factory(User::class, 2)->create();

$user_public_1_id = 5;
$user_public_2_id = 6;

// projects

$I->comment("given no projects owned by public users");

// ----------------------------------------------------

$I->expect("should be 6 users");
$I->assertSame(6, User::all()->count());

$I->expect("should be 8 projects");
$I->assertSame(8, Project::all()->count());

// ====================================================
// authenticate user and set headers
// ====================================================

$I->haveHttpHeader('Content-Type', 'application/vnd.api+json');
$I->haveHttpHeader('Accept', 'application/vnd.api+json');

$credentials = Fixtures::get('credentials');
$credentials['data']['attributes'] = [
    'email' => 'admin@bbb.ccc',
    'password' => $password,
];

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

$I->haveHttpHeader('Authorization', "Bearer {$access_token}");

///////////////////////////////////////////////////////
//
// Test
//
// * projects as administrator
//
// Endpoints
//
// * projects.index
// * projects.show
// * projects.store
// * projects.update
// * projects.destroy
//
///////////////////////////////////////////////////////

// ====================================================
// projects.index
// ====================================================

$I->comment("when we index all projects");
$I->sendGET('/api/projects');

$I->expect("should return 200 HTTP code");
$I->seeResponseCodeIs(HttpCode::OK);

$I->expect("should return all 8 projects");
$I->assertCount(8, $I->grabResponseJsonPath('$.data[*]'));
$I->seeResponseJsonPathSame('$.data[*].type', 'projects');

// ====================================================
// projects.show
// ====================================================

$I->comment("when we view any user's project");
$requests = [
    [ 'GET', "/api/projects/{$user_admin_project_1_id}" ],
    [ 'GET', "/api/projects/{$user_admin_project_2_id}" ],
    [ 'GET', "/api/projects/{$user_demo_project_1_id}" ],
    [ 'GET', "/api/projects/{$user_demo_project_2_id}" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_project_1_id}" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_project_2_id}" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_project_1_id}" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_project_2_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return requested project");
    $I->seeResponseJsonPathRegex('$.data.id', '/\d+/');

});

// ====================================================
// projects.store
// ====================================================

$I->comment("when we store a project");
$project = Fixtures::get('project');
$I->sendPOST('/api/projects', $project);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should return new project's id");
$I->seeResponseJsonPathSame('$.data.type', 'projects');
$I->seeResponseJsonPathSame('$.data.id', '9');

$I->expect("should create 1 new record");
$I->assertSame(9, Project::all()->count());

$I->expect("should return relationships, including owner");
$I->seeResponseJsonPathType('$.data.relationships.owner', 'array:!empty');

$I->expect("new project should belong to admin user");
$new_project_1_id = $I->grabResponseJsonPath('$.data.id')[0];
$new_project_1 = Project::find($new_project_1_id);
$I->assertSame($user_admin_id, $new_project_1->owner->id);

// ====================================================
// projects.store (with owner relationship)
// ====================================================

$I->comment("when we store a project and set the owner to demo user");
$project = Fixtures::get('project');
$project['data']['relationships'] = [
    'owner' => [
        'data' => [
            'type' => 'users',
            'id' => $user_demo_id
        ]
    ]
];
$I->sendPOST('/api/projects', $project);

$I->expect("should return 201 HTTP code");
$I->seeResponseCodeIs(HttpCode::CREATED);

$I->expect("should return new project's id");
$I->seeResponseJsonPathSame('$.data.type', 'projects');
$I->seeResponseJsonPathSame('$.data.id', '10');

$I->expect("should return relationships, including owner");
$I->seeResponseJsonPathType('$.data.relationships.owner', 'array:!empty');

$I->expect("should create 1 new record");
$I->assertSame(10, Project::all()->count());

$new_project_2_id = $I->grabResponseJsonPath('$.data.id')[0];
$new_project_2 = Project::find($new_project_2_id);

$I->expect("new project should belong to demo user");
$I->assertSame($user_demo_id, $new_project_2->owner->id);

// ====================================================
// projects.update
// ====================================================

$I->comment("when we update any user's project (excluding public who can't have projects)");
$project = [
    'data' => [
        'type' => 'projects',
        'attributes' => [
            'name' => "AAABBBCCC",
        ]
    ]
];
$requests = [
    [ 'PATCH', "/api/projects/{$user_admin_project_1_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_admin_project_1_id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_admin_project_2_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_admin_project_2_id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_demo_project_1_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_demo_project_1_id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_demo_project_2_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_demo_project_2_id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_subscriber_1_project_1_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_1_project_1_id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_subscriber_1_project_2_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_1_project_2_id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_subscriber_2_project_1_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_2_project_1_id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_subscriber_2_project_2_id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_2_project_2_id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

});

// ====================================================
// projects.destroy
// ====================================================

$I->comment("when we delete any user's project");
$requests = [
    [ 'DELETE', "/api/projects/{$user_admin_project_1_id}" ],
    [ 'DELETE', "/api/projects/{$user_admin_project_2_id}" ],
    [ 'DELETE', "/api/projects/{$user_demo_project_1_id}" ],
    [ 'DELETE', "/api/projects/{$user_demo_project_2_id}" ],
    [ 'DELETE', "/api/projects/{$user_subscriber_1_project_1_id}" ],
    [ 'DELETE', "/api/projects/{$user_subscriber_1_project_2_id}" ],
    [ 'DELETE', "/api/projects/{$user_subscriber_2_project_1_id}" ],
    [ 'DELETE', "/api/projects/{$user_subscriber_2_project_2_id}" ],
    [ 'DELETE', "/api/projects/{$new_project_1_id}" ],
    [ 'DELETE', "/api/projects/{$new_project_2_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    // ----------------------------------------------------

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    $I->expect("should also delete all that project's tasks");
    // TODO: test

});

$I->expect("should have deleted all projects");
$I->assertSame(0, Project::all()->count());
