<?php

return [
    'acl' => [
        'check_access' => true, // overrides check_ownership & check_permission
        'check_ownership' => true, // overrides use_role_hierarchy
//        'check_ownership_method' => 'owns', // default: owns
        'check_permission' => true, // will check for permissions that correspond to the route name (eg. users.show or tasks.relationships.project.show)
//        'check_permission_method' => 'can', // default: can
        'error_messages' => [
            'status_code' => 403,
            'title' => "Insufficient Permissions",
            'detail' => [
                'check_ownership_fail' => "User does not own target resource",
                'check_permission_fail' => "User does not have permission to perform this action on the target resource",
            ]
        ],
        'acl_config' => 'laratrust',
        'seeder_config' => 'laratrust_seeder',
        'use_role_hierarchy' => true,
    ],
    'jwt' => [
        'error_messages' => [
            'status_code' => 401,
            'title' => "Unauthorised",
            'detail' => [
                'token_not_provided' => "Access token not provided.",
                'token_expired' => "Access token is expired.",
                'token_invalid' => "Access token is invalid.",
                'user_not_found' => "No user for given access token.",
            ]
        ]
    ]
];
