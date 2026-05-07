<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default SSH Connection Settings
    |--------------------------------------------------------------------------
    |
    | These settings will be used as defaults when creating SSH connections.
    |
    */
    'default' => [
        'timeout' => 60,
        'port' => 22,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure whether to enable SSH action and pipeline logging.
    |
    */
    'logging' => [
        'enabled' => true,
        'connection' => env('DB_CONNECTION', 'mysql'),
    ],
];
