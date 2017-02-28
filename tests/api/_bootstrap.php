<?php

use Codeception\Util\Fixtures;

Fixtures::add('unknown', [
    'data' => [
        'type' => 'unknown',
        'attributes' => []
    ]
]);

Fixtures::add('credentials', [
    'data' => [
        'type' => 'access_tokens',
        'attributes' => []
    ]
]);

Fixtures::add('user', [
    'data' => [
        'type' => 'users',
        'attributes' => [
            'username' => "AAA",
            'first_name' => "BBB",
            'last_name' => "CCC",
            'email' => "AAA@BBB.CCC",
            'password' => "abcABC123!",
            'password_confirmation' => "abcABC123!"
        ]
    ]
]);

Fixtures::add('project', [
    'data' => [
        'type' => 'projects',
        'attributes' => [
            'name' => "AAA"
        ]
    ]
]);

Fixtures::add('task', [
    'data' => [
        'type' => 'tasks',
        'attributes' => [
            'name' => "AAA"
        ]
    ]
]);
