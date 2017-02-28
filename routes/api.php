<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$public_api_middleware = [
    'api',
    'jsonapi'
];

$private_api_middleware = [
    'jsonapi.jwt',
    'jwt.auth'
];

// ====================================================
// public API
// ====================================================

Route::group(['middleware' => [ 'api', 'jsonapi' ], 'namespace' => 'Api'], function () {

    // access tokens

    Route::post('access_tokens', [ 'as' => 'access_tokens.create', 'uses' => 'AccessTokensController@create' ]);

    // api root

    Route::get('', [ 'as' => 'api.view', 'uses' => 'ApiController@view' ]);

    // ====================================================
    // private API
    // ====================================================

    Route::group(['middleware' => [ 'jsonapi.jwt', 'jwt.auth', 'jsonapi.acl' ]], function () {

        // projects
        Route::get('projects', [ 'as' => 'projects.index', 'uses' => 'ProjectsController@index', 'middleware' => [ 'acl' ], 'can' => 'index.projects' ]);
        Route::get('projects/{id}', [ 'as' => 'projects.view', 'uses' => 'ProjectsController@show', 'middleware' => [ 'acl' ], 'can' => 'view.projects' ]);
        Route::post('projects', [ 'as' => 'projects.store', 'uses' => 'ProjectsController@store', 'middleware' => [ 'acl' ], 'can' => 'store.projects' ]);
        Route::patch('projects/{id}', [ 'as' => 'projects.update', 'uses' => 'ProjectsController@update', 'middleware' => [ 'acl' ], 'can' => 'update.projects' ]);
        Route::delete('projects/{id}', [ 'as' => 'projects.destroy', 'uses' => 'ProjectsController@destroy', 'middleware' => [ 'acl' ], 'can' => 'destroy.projects' ]);

        // ... owner
        Route::get('projects/{id}/owner', [ 'as' => 'projects.owner.show', 'uses' => 'ProjectsController@showRelated', 'middleware' => [ 'acl' ], 'can' => 'owner.show.projects' ]);
        Route::get('projects/{id}/relationships/owner', [ 'as' => 'projects.relationships.owner.show', 'uses' => 'ProjectsController@showRelated', 'is_minimal' => true, 'middleware' => [ 'acl' ], 'can' => 'relationships.owner.show.projects' ]);

        // ... tasks
        Route::get('projects/{id}/tasks', [ 'as' => 'projects.tasks.index', 'uses' => 'ProjectsController@indexRelated', 'middleware' => [ 'acl' ], 'can' => 'tasks.index.projects' ]);
        Route::get('projects/{id}/relationships/tasks', [ 'as' => 'projects.relationships.tasks.index', 'uses' => 'ProjectsController@indexRelated', 'is_minimal' => true, 'middleware' => [ 'acl' ], 'can' => 'relationships.tasks.index.projects' ]);

        // tasks
        Route::get('tasks', [ 'as' => 'tasks.index', 'uses' => 'TasksController@index', 'middleware' => [ 'acl' ], 'can' => 'index.tasks' ]);
        Route::get('tasks/{id}', [ 'as' => 'tasks.view', 'uses' => 'TasksController@show', 'middleware' => [ 'acl' ], 'can' => 'view.tasks' ]);
        Route::post('tasks', [ 'as' => 'tasks.store', 'uses' => 'TasksController@store', 'middleware' => [ 'acl' ], 'can' => 'store.tasks' ]);
        Route::patch('tasks/{id}', [ 'as' => 'tasks.update', 'uses' => 'TasksController@update', 'middleware' => [ 'acl' ], 'can' => 'update.tasks' ]);
        Route::delete('tasks/{id}', [ 'as' => 'tasks.destroy', 'uses' => 'TasksController@destroy', 'middleware' => [ 'acl' ], 'can' => 'destroy.tasks' ]);

        // ... owner
        Route::get('tasks/{id}/owner', [ 'as' => 'tasks.owner.show', 'uses' => 'TasksController@showRelated', 'middleware' => [ 'acl' ], 'can' => 'owner.show.tasks' ]);
        Route::get('tasks/{id}/relationships/owner', [ 'as' => 'tasks.relationships.owner.show', 'uses' => 'TasksController@showRelated', 'is_minimal' => true, 'middleware' => [ 'acl' ], 'can' => 'relationships.owner.show.tasks' ]);

        // ... project
        Route::get('tasks/{id}/project', [ 'as' => 'tasks.project.show', 'uses' => 'TasksController@showRelated', 'middleware' => [ 'acl' ], 'can' => 'project.show.tasks' ]);
        Route::get('tasks/{id}/relationships/project', [ 'as' => 'tasks.relationships.project.show', 'uses' => 'TasksController@showRelated', 'is_minimal' => true, 'middleware' => [ 'acl' ], 'can' => 'relationships.project.show.tasks' ]);

        // users
        Route::get('users', [ 'as' => 'users.index', 'uses' => 'UsersController@index', 'middleware' => [ 'acl' ], 'can' => 'index.users' ]);
        Route::get('users/{id}', [ 'as' => 'users.view', 'uses' => 'UsersController@show', 'middleware' => [ 'acl' ], 'can' => 'view.users' ]);
        Route::post('users', [ 'as' => 'users.store', 'uses' => 'UsersController@store', 'middleware' => [ 'acl' ], 'can' => 'store.users' ]);
        Route::patch('users/{id}', [ 'as' => 'users.update', 'uses' => 'UsersController@update', 'middleware' => [ 'acl' ], 'can' => 'update.users' ]);
        Route::delete('users/{id}', [ 'as' => 'users.destroy', 'uses' => 'UsersController@destroy', 'middleware' => [ 'acl' ], 'can' => 'destroy.users' ]);

        // ... projects
        Route::get('users/{id}/projects', [ 'as' => 'users.projects.index', 'uses' => 'UsersController@indexRelated', 'middleware' => [ 'acl' ], 'can' => 'projects.index.users' ]);
        Route::get('users/{id}/relationships/projects', [ 'as' => 'users.relationships.projects.index', 'uses' => 'UsersController@indexRelated', 'is_minimal' => true, 'middleware' => [ 'acl' ], 'can' => 'relationships.projects.index.users' ]);

        // ... tasks
        Route::get('users/{id}/tasks', [ 'as' => 'users.tasks.index', 'uses' => 'UsersController@indexRelated', 'middleware' => [ 'acl' ], 'can' => 'tasks.index.users' ]);
        Route::get('users/{id}/relationships/tasks', [ 'as' => 'users.relationships.tasks.index', 'uses' => 'UsersController@indexRelated', 'is_minimal' => true, 'middleware' => [ 'acl' ], 'can' => 'relationships.tasks.index.users' ]);

    });
});
