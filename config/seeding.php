<?php

return [
    'admin' => [
        'name' => env('SEED_ADMIN_NAME', 'System Administrator'),
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

    'demo_user' => [
        'name' => env('SEED_DEMO_USER_NAME', 'John Doe'),
        'email' => env('SEED_DEMO_USER_EMAIL'),
        'password' => env('SEED_DEMO_USER_PASSWORD'),
    ],
];
