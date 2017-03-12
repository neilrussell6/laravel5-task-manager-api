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

    Route::group(['middleware' => [ 'jsonapi.jwt', 'jwt.auth' ]], function () {

        // access tokens
    
        Route::get('access_tokens/owner', [ 'as' => 'access_tokens.owner.show', 'uses' => 'AccessTokensController@showOwner' ]);
        Route::get('access_tokens/relationships/owner', [ 'as' => 'access_tokens.relationships.owner.show', 'uses' => 'AccessTokensController@showOwner', 'is_minimal' => true ]);

        // projects
        Route::resource('projects', 'ProjectsController', ['except' => ['edit', 'create']]);

        // ... owner
        Route::get('projects/{id}/owner', [ 'as' => 'projects.owner.show', 'uses' => 'ProjectsController@showRelated' ]);
        Route::get('projects/{id}/relationships/owner', [ 'as' => 'projects.relationships.owner.show', 'uses' => 'ProjectsController@showRelated', 'is_minimal' => true ]);
        Route::patch('projects/{id}/relationships/owner', [ 'as' => 'projects.relationships.owner.update', 'uses' => 'ProjectsController@updateRelated', 'is_minimal' => true ]);
        Route::delete('projects/{id}/relationships/owner', [ 'as' => 'projects.relationships.owner.destroy', 'uses' => 'ProjectsController@destroyRelated', 'is_minimal' => true ]);

        // ... tasks
        Route::get('projects/{id}/tasks', [ 'as' => 'projects.tasks.index', 'uses' => 'ProjectsController@indexRelated' ]);
        Route::get('projects/{id}/relationships/tasks', [ 'as' => 'projects.relationships.tasks.index', 'uses' => 'ProjectsController@indexRelated', 'is_minimal' => true ]);

        // tasks
        Route::resource('tasks', 'TasksController', ['except' => ['edit', 'create']]);

        // ... owner
        Route::get('tasks/{id}/owner', [ 'as' => 'tasks.owner.show', 'uses' => 'TasksController@showRelated' ]);
        Route::get('tasks/{id}/relationships/owner', [ 'as' => 'tasks.relationships.owner.show', 'uses' => 'TasksController@showRelated', 'is_minimal' => true ]);
        Route::patch('tasks/{id}/relationships/owner', [ 'as' => 'tasks.relationships.owner.update', 'uses' => 'TasksController@updateRelated', 'is_minimal' => true ]);
        Route::delete('tasks/{id}/relationships/owner', [ 'as' => 'tasks.relationships.owner.update', 'uses' => 'TasksController@destroyRelated', 'is_minimal' => true ]);

        // ... project
        Route::get('tasks/{id}/project', [ 'as' => 'tasks.project.show', 'uses' => 'TasksController@showRelated' ]);
        Route::get('tasks/{id}/relationships/project', [ 'as' => 'tasks.relationships.project.show', 'uses' => 'TasksController@showRelated', 'is_minimal' => true ]);
        Route::patch('tasks/{id}/relationships/project', [ 'as' => 'tasks.relationships.project.update', 'uses' => 'TasksController@updateRelated', 'is_minimal' => true ]);
        Route::delete('tasks/{id}/relationships/project', [ 'as' => 'tasks.relationships.project.update', 'uses' => 'TasksController@destroyRelated', 'is_minimal' => true ]);

        // users
        Route::resource('users', 'UsersController', ['except' => ['edit', 'create']]);

        // ... projects
        Route::get('users/{id}/projects', [ 'as' => 'users.projects.index', 'uses' => 'UsersController@indexRelated' ]);
        Route::get('users/{id}/relationships/projects', [ 'as' => 'users.relationships.projects.index', 'uses' => 'UsersController@indexRelated', 'is_minimal' => true ]);

        // ... tasks
        Route::get('users/{id}/tasks', [ 'as' => 'users.tasks.index', 'uses' => 'UsersController@indexRelated' ]);
        Route::get('users/{id}/relationships/tasks', [ 'as' => 'users.relationships.tasks.index', 'uses' => 'UsersController@indexRelated', 'is_minimal' => true ]);

    });
});
