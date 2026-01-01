<?php

return [

    'defaults' => [
        'guard' => 'customer',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    */
    'guards' => [
        // 👤 Customer login (frontend app)
        'customer' => [
            'driver' => 'sanctum',
            'provider' => 'customers',
        ],

        // 🏢 Partner login (B2B)
        'partner' => [
            'driver' => 'sanctum',
            'provider' => 'partners',
        ],

        // 🧑‍💼 Employee / Admin login (Backend)
        'employee' => [
            'driver' => 'sanctum',
            'provider' => 'employees',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        // All are users table, but filtered by user_type
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'query' => fn ($q) => $q->where('user_type', 'customer'),
        ],

        'partners' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'query' => fn ($q) => $q->where('user_type', 'partner'),
        ],

        'employees' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'query' => fn ($q) => $q->whereIn('user_type', ['employee', 'admin']),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Config
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'provider' => 'customers',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
