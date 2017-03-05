<?php

use Codeception\Util\Fixtures;
use Codeception\Util\HttpCode;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
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

// tasks

$I->comment("given 2 tasks for each project owned by admin user");
$user_admin_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $user_admin->id, 'project_id' => $user_admin_projects[0]->id]);
$user_admin_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $user_admin->id, 'project_id' => $user_admin_projects[1]->id]);

// ----------------------------------------------------
// demo
// ----------------------------------------------------

$I->comment("given 1 demo user");
$user_demo = factory(User::class)->create();
$user_demo->roles()->attach([ $demo_role->id ]);

// projects

$I->comment("given 2 projects owned by demo user");
$user_demo_projects = factory(Project::class, 2)->create(['user_id' => $user_demo->id]);

// tasks

$I->comment("given 2 tasks for each project owned by demo user");
$user_demo_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $user_demo->id, 'project_id' => $user_demo_projects[0]->id]);
$user_demo_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $user_demo->id, 'project_id' => $user_demo_projects[1]->id]);

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

// tasks

$I->comment("given 2 tasks for each project owned by each subscriber user");
$user_subscriber_1_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[0]->id, 'project_id' => $user_subscriber_1_projects[0]->id]);
$user_subscriber_1_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[0]->id, 'project_id' => $user_subscriber_1_projects[1]->id]);
$user_subscriber_2_project_1_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[1]->id, 'project_id' => $user_subscriber_2_projects[0]->id]);
$user_subscriber_2_project_2_tasks = factory(Task::class, 2)->create(['user_id' => $subscriber_users[1]->id, 'project_id' => $user_subscriber_2_projects[1]->id]);

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

$I->expect("should be 16 tasks");
$I->assertSame(16, Task::all()->count());

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
// * task project as subscriber
//
// Endpoints
//
// * tasks.project.show
// * tasks.relationships.project.show
// * tasks.relationships.project.update
// * tasks.relationships.project.destroy
//
///////////////////////////////////////////////////////

// ====================================================
// tasks.project
// tasks.relationships.project.show
// ====================================================

$I->comment("when we view the project of any task we own");
$requests = [
    [ 'GET', "/api/tasks/{$user_subscriber_1_project_1_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_subscriber_1_project_1_tasks[1]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_subscriber_1_project_2_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_subscriber_1_project_2_tasks[1]->id}/project" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 200 HTTP code");
    $I->seeResponseCodeIs(HttpCode::OK);

    $I->expect("should return task");
    $I->seeResponseJsonPathSame('$.data.type', 'projects');

});

// ----------------------------------------------------

$I->comment("when we view the task of a task that we don't own");
$requests = [
    [ 'GET', "/api/tasks/{$user_admin_project_1_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_admin_project_1_tasks[1]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_admin_project_2_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_admin_project_2_tasks[1]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_demo_project_1_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_demo_project_1_tasks[1]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_demo_project_2_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_demo_project_2_tasks[1]->id}/project" ],
//    [ 'GET', "/api/tasks/{$user_subscriber_1_project_1_tasks[0]->id}/project" ],
//    [ 'GET', "/api/tasks/{$user_subscriber_1_project_1_tasks[1]->id}/project" ],
//    [ 'GET', "/api/tasks/{$user_subscriber_1_project_2_tasks[0]->id}/project" ],
//    [ 'GET', "/api/tasks/{$user_subscriber_1_project_2_tasks[1]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_project_1_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_project_1_tasks[1]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_project_2_tasks[0]->id}/project" ],
    [ 'GET', "/api/tasks/{$user_subscriber_2_project_2_tasks[1]->id}/project" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});

// ====================================================
// tasks.relationships.project.update
// ====================================================

$I->comment("when we update the project of any task we own (switch project 1 tasks to project 2)");
$project = [
    'data' => [
        'type' => 'projects',
        'id' => $user_subscriber_1_projects[1]->id,
    ]
];
$requests = [
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_1_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_1_tasks[1]->id}/relationships/project", $project ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    $I->expect("should not return content");
    $I->seeResponseEquals(null);;

});

$I->expect("should have changed project 1 tasks to project 2");
$I->assertSame(Task::find($user_subscriber_1_project_1_tasks[0]->id)->project->id, $user_subscriber_1_projects[1]->id);
$I->assertSame(Task::find($user_subscriber_1_project_1_tasks[1]->id)->project->id, $user_subscriber_1_projects[1]->id);

// ----------------------------------------------------

$I->comment("when we update the project of any task we own (switch project 2 tasks to project 1)");
$project = [
    'data' => [
        'type' => 'projects',
        'id' => $user_subscriber_1_projects[0]->id,
    ]
];
$requests = [
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_2_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_2_tasks[1]->id}/relationships/project", $project ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    $I->expect("should not return content");
    $I->seeResponseEquals(null);

});

$I->expect("should have changed project 2 tasks to project 1");
$I->assertSame(Task::find($user_subscriber_1_project_2_tasks[0]->id)->project->id, $user_subscriber_1_projects[0]->id);
$I->assertSame(Task::find($user_subscriber_1_project_2_tasks[1]->id)->project->id, $user_subscriber_1_projects[0]->id);

// ----------------------------------------------------

$I->comment("when we update the project of any task we own, but we attempt to set it to a project we don't own");
$project = [
    'data' => [
        'type' => 'projects',
        'id' => $user_demo_projects[0]->id,
    ]
];
$requests = [
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_1_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_1_tasks[1]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_2_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_2_tasks[1]->id}/relationships/project", $project ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});

$I->expect("should not have changed the project of any tasks we don't own");
// TODO: test

// ----------------------------------------------------

$I->comment("when we update the project of a task that we don't own");
$project = [
    'data' => [
        'type' => 'users',
        'id' => $user_demo->id,
    ]
];
$requests = [
    [ 'PATCH', "/api/tasks/{$user_admin_project_1_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_admin_project_1_tasks[1]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_admin_project_2_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_admin_project_2_tasks[1]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_demo_project_1_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_demo_project_1_tasks[1]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_demo_project_2_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_demo_project_2_tasks[1]->id}/relationships/project", $project ],
//    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_1_tasks[0]->id}/relationships/project", $project ],
//    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_1_tasks[1]->id}/relationships/project", $project ],
//    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_2_tasks[0]->id}/relationships/project", $project ],
//    [ 'PATCH', "/api/tasks/{$user_subscriber_1_project_2_tasks[1]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_project_1_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_project_1_tasks[1]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_project_2_tasks[0]->id}/relationships/project", $project ],
    [ 'PATCH', "/api/tasks/{$user_subscriber_2_project_2_tasks[1]->id}/relationships/project", $project ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');

});

$I->expect("should not have changed the project of any tasks we don't own");
// TODO: test

// ====================================================
// tasks.relationships.owner.destroy
// ====================================================

$I->comment("when we delete the project of any task we own");
$requests = [
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_1_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_1_tasks[1]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_2_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_2_tasks[1]->id}/relationships/project" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 204 HTTP code");
    $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

    $I->expect("should not return content");
    $I->seeResponseEquals(null);;

});

$I->expect("should have dissociated the project from all tasks we own");
$I->assertNull(Task::find($user_subscriber_1_project_1_tasks[0]->id)->project);
$I->assertNull(Task::find($user_subscriber_1_project_1_tasks[1]->id)->project);
$I->assertNull(Task::find($user_subscriber_1_project_2_tasks[0]->id)->project);
$I->assertNull(Task::find($user_subscriber_1_project_2_tasks[1]->id)->project);

$I->expect("should not have deleted any project records");
$I->assertSame(8, Project::all()->count());

// ----------------------------------------------------

$I->comment("when we delete the project of any task we don't own");
$requests = [
    [ 'DELETE', "/api/tasks/{$user_admin_project_1_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_admin_project_1_tasks[1]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_admin_project_2_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_admin_project_2_tasks[1]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_demo_project_1_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_demo_project_1_tasks[1]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_demo_project_2_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_demo_project_2_tasks[1]->id}/relationships/project" ],
//    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_1_tasks[0]->id}/relationships/project" ],
//    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_1_tasks[1]->id}/relationships/project" ],
//    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_2_tasks[0]->id}/relationships/project" ],
//    [ 'DELETE', "/api/tasks/{$user_subscriber_1_project_2_tasks[1]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_project_1_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_project_1_tasks[1]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_project_2_tasks[0]->id}/relationships/project" ],
    [ 'DELETE', "/api/tasks/{$user_subscriber_2_project_2_tasks[1]->id}/relationships/project" ],
];

$I->sendMultiple($requests, function($request) use ($I) {

    $I->comment("given we make a {$request[0]} request to {$request[1]}");

    $I->expect("should return 403 HTTP code");
    $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

    $I->expect("should return an errors array");
    $I->seeResponseJsonPathType('$.errors', 'array:!empty');
});

$I->expect("should not update the project of any tasks we don't own");
// TODO: test

$I->expect("should not have deleted any project records");
$I->assertSame(8, Project::all()->count());
