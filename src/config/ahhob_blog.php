<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AhHob Blog Configuration
    |--------------------------------------------------------------------------
    */

    'mode' => env('AHHOB_MODE', 'web'), // 'web', 'admin', 'api'

    'routes' => [
        'web_prefix' => '',
        'admin_prefix' => 'admin',
        'api_prefix' => 'api/v1',
    ],

    'auth' => [
        'admin_guard' => 'admin',
        'api_guard' => 'sanctum',
        'web_guard' => 'web',
    ],

    'upload' => [
        'profile_images' => [
            'path' => 'uploads/profiles',
            'max_size' => 2048, // KB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
        ],
        'post_images' => [
            'path' => 'uploads/posts',
            'max_size' => 5120, // KB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        ],
    ],

    'pagination' => [
        'per_page' => 10,
        'admin_per_page' => 20,
    ],

    'cache' => [
        'posts_ttl' => 3600, // 1 hour
        'categories_ttl' => 7200, // 2 hours
    ],
];

