<?php

return [

    'default' => env('LOG_CHANNEL', 'daily'),

    'channels' => [
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'lua' => [
            'driver' => 'daily',
            'path' => storage_path('logs/lua.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],
    ],

];
