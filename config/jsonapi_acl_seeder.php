<?php

return [
    'role_hierarchy' => [ // higher overrides lower
        'administrator' => 3,
        'demo' => 2,
        'subscriber' => 2,
    ],
    'hierarchical_roles' => [
        'administrator'
    ],
];