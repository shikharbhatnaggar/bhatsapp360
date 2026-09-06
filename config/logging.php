<?php

use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'stack' => ['driver' => 'stack', 'channels' => ['single'], 'ignore_exceptions' => false],
        'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'debug')],
        'whatsapp' => ['driver' => 'daily', 'path' => storage_path('logs/whatsapp.log'), 'level' => 'debug', 'days' => 14],
        'stderr' => ['driver' => 'monolog', 'handler' => StreamHandler::class, 'with' => ['stream' => 'php://stderr']],
    ],
];
