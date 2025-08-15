<?php

return [

    'default' => env('LOG_CHANNEL', 'daily'),

    'lua' => [
        'driver' => 'daily',
        'path' => storage_path('logs/lua.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],

];
