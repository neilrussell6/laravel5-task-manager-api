<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
use App\Models\Role;
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

$administrator_role = Role::where('name', '=', 'administrator')->first();
$demo_role = Role::where('name', '=', 'demo')->first();
$subscriber_role = Role::where('name', '=', 'subscriber')->first();

$password = "abcABC123!";

// ----------------------------------------------------
// admin
// ----------------------------------------------------

$I->comment("given 1 admin user");
$user_admin = factory(User::class)->create();
$user_admin->roles()->attach([ $user_admin->id ]);

// projects

$I->comment("given 2 projects owned by admin user");
$user_admin_projects = factory(Project::class, 2)->create(['user_id' => $user_admin->id]);

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
$user_demo = factory(User::class)->create();
$user_demo->roles()->attach([ $demo_role->id ]);

// projects

$I->comment("given 2 projects owned by demo user");
$user_demo_projects = factory(Project::class, 2)->create(['user_id' => $user_demo->id]);

// ----------------------------------------------------
// subscriber
// ----------------------------------------------------

$I->comment("given 2 subscriber users");
$subscriber_users = factory(User::class, 2)->create()->map(function($user) use ($subscriber_role) {
    $user->roles()->attach([ $subscriber_role->id ]);
    return $user;
});

// projects

$I->comment("given 2 projects owned by each subscriber user");
$user_subscriber_1_projects = factory(Project::class, 2)->create(['user_id' => $subscriber_users[0]->id]);
$user_subscriber_2_projects = factory(Project::class, 2)->create(['user_id' => $subscriber_users[1]->id]);

// ----------------------------------------------------
// public
// ----------------------------------------------------

$I->comment("given 2 public users (no role)");
$public_users = factory(User::class, 2)->create();

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
    'email' => $subscriber_users[0]->email,
    'password' => $password,
];

$I->sendPOST('/api/access_tokens', $credentials);
$access_token = $I->grabResponseJsonPath('$.data.attributes.access_token')[0];

$I->haveHttpHeader('Authorization', "Bearer {$access_token}");

///////////////////////////////////////////////////////
//
// Test
//
// * projects as subscriber
//
// Endpoints
//
// * projects.index
// * projects.show
// * projects.store
// * projects.store (with owner relationship)
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

$I->expect("should only return projects we own");
$I->assertCount(2, $I->grabResponseJsonPath('$.data[*]'));
$I->seeResponseJsonPathSame('$.data[*].type', 'projects');
$I->seeResponseJsonPathSame('$.data[0].id', "{$user_subscriber_1_projects[0]->id}");
$I->seeResponseJsonPathSame('$.data[1].id', "{$user_subscriber_1_projects[1]->id}");

// ====================================================
// projects.show
// ====================================================

$I->comment("when we view any project we own");
$requests = [
    [ 'GET', "/api/projects/{$user_subscriber_1_projects[0]->id}" ],
    [ 'GET', "/api/projects/{$user_subscriber_1_projects[1]->id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return requested project");
    $I->seeResponseJsonPathRegex('$.data.id', '/\d+/');

});

// ----------------------------------------------------

$I->comment("when we view any project that we don't own");
$requests = [
    [ 'GET', "/api/projects/{$user_admin_projects[0]->id}" ],
    [ 'GET', "/api/projects/{$user_admin_projects[1]->id}" ],
    [ 'GET', "/api/projects/{$user_demo_projects[0]->id}" ],
    [ 'GET', "/api/projects/{$user_demo_projects[1]->id}" ],
//    [ 'GET', "/api/projects/{$user_subscriber_1_projects[0]->id}" ],
//    [ 'GET', "/api/projects/{$user_subscriber_1_projects[1]->id}" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_projects[0]->id}" ],
    [ 'GET', "/api/projects/{$user_subscriber_2_projects[1]->id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

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
$new_project_1_id = intval($I->grabResponseJsonPath('$.data.id')[0]);
$new_project_1 = Project::find($new_project_1_id);
$I->assertSame($subscriber_users[0]->id, $new_project_1->owner->id);

//// ====================================================
//// projects.store (with owner relationship)
//// TODO: test once implemented
//// ====================================================
//
//$I->comment("when we store a project and set the owner to demo user");
//$project = Fixtures::get('project');
//$project['data']['relationships'] = [
//    'owner' => [
//        'data' => [
//            'type' => 'users',
//            'id' => $user_demo->id
//        ]
//    ]
//];
//$I->sendPOST('/api/projects', $project);
//
//$I->assertSame(9, Project::all()->count());
//dd($I->grabResponseAsJson());;
//
//$I->expect("should return 403 HTTP code");
//$I->seeResponseCodeIs(HttpCode::FORBIDDEN);
//
//$I->expect("should return an errors array");
//$I->seeResponseJsonPathType('$.errors', 'array:!empty');
//
//$I->expect("should not create a new record");
//$I->assertSame(9, Project::all()->count());

// ====================================================
// projects.update
// ====================================================

$I->comment("when we update any project we own");
$project = [
    'data' => [
        'type' => 'projects',
        'attributes' => [
            'name' => "AAABBBCCC",
        ]
    ]
];
$requests = [
    [ 'PATCH', "/api/projects/{$user_subscriber_1_projects[0]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_1_projects[0]->id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_subscriber_1_projects[1]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_1_projects[1]->id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

});

// ----------------------------------------------------

$I->comment("when we update any project that we don't own");
$project = [
    'data' => [
        'type' => 'projects',
        'attributes' => [
            'name' => "AAABBBCCC",
        ]
    ]
];
$requests = [
    [ 'PATCH', "/api/projects/{$user_admin_projects[0]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_admin_projects[0]->id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_admin_projects[1]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_admin_projects[1]->id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_demo_projects[0]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_demo_projects[0]->id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_demo_projects[1]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_demo_projects[1]->id ] ]) ],
//    [ 'PATCH', "/api/projects/{$user_subscriber_1_projects[0]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_1_projects[0]->id ] ]) ],
//    [ 'PATCH', "/api/projects/{$user_subscriber_1_projects[1]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_1_projects[1]->id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_subscriber_2_projects[0]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_2_projects[0]->id ] ]) ],
    [ 'PATCH', "/api/projects/{$user_subscriber_2_projects[1]->id}", array_merge_recursive($project, [ 'data' => [ 'id' => $user_subscriber_2_projects[1]->id ] ]) ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});

// ====================================================
// projects.destroy
// ====================================================

$I->comment("when we delete any project we own");
$requests = [
    [ 'DELETE', "/api/projects/{$user_subscriber_1_projects[0]->id}" ],
    [ 'DELETE', "/api/projects/{$user_subscriber_1_projects[1]->id}" ],
    [ 'DELETE', "/api/projects/{$new_project_1_id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    $I->expect("should not return content");
    $I->seeResponseEquals(null);

});

$I->expect("should have deleted 3 projects, leaving 6");
$I->assertSame(6, Project::all()->count());
$project_ids = array_column(Project::all()->toArray(), 'id');
$I->assertNotContains($user_subscriber_1_projects[0]->id, $project_ids, "should have deleted subscriber 1 project 1");
$I->assertNotContains($user_subscriber_1_projects[1]->id, $project_ids, "should have deleted subscriber 1 project 2");
$I->assertNotContains($new_project_1_id, $project_ids, "should have deleted new project 1");

$I->expect("should also delete all that project's tasks");
// TODO: test

// ----------------------------------------------------

$I->comment("when we delete any project we do not own");
$requests = [
    [ 'DELETE', "/api/projects/{$user_admin_projects[0]->id}" ],
    [ 'DELETE', "/api/projects/{$user_admin_projects[1]->id}" ],
    [ 'DELETE', "/api/projects/{$user_demo_projects[0]->id}" ],
    [ 'DELETE', "/api/projects/{$user_demo_projects[1]->id}" ],
//    [ 'DELETE', "/api/projects/{$user_subscriber_1_projects[0]->id}" ],
//    [ 'DELETE', "/api/projects/{$user_subscriber_1_projects[1]->id}" ],
    [ 'DELETE', "/api/projects/{$user_subscriber_2_projects[0]->id}" ],
    [ 'DELETE', "/api/projects/{$user_subscriber_2_projects[1]->id}" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});
