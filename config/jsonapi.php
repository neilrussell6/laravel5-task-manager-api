<?php

return [
    'acl' => [
        'error_status_code' => [
            'insufficient_permissions' => 403,
        ],
        'error_status_title' => [
            'insufficient_permissions' => "Forbidden"
        ],
        'error_status_detail' => [
            'insufficient_permissions' => "You are not authorized to access this resource.",
        ],
    ],
    'jwt' => [
        'error_status_code' => [
            'token_not_provided' => 401,
            'token_expired' => 401,
            'token_invalid' => 401,
            'user_not_found' => 401,
        ],
        'error_title' => [
            'token_not_provided' => "Unauthorised",
            'token_expired' => "Unauthorised",
            'token_invalid' => "Unauthorised",
            'user_not_found' => "Unauthorised",
        ],
        'error_detail' => [
            'token_not_provided' => "Access token not provided",
            'token_expired' => "Access token is expired",
            'token_invalid' => "Access token is invalid",
            'user_not_found' => "No user for given access token",
        ]
    ]
];
