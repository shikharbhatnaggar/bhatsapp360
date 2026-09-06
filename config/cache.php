<?php

return [
    'default' => env('CACHE_STORE', 'database'),
    'stores' => [
        'array' => ['driver' => 'array'],
        'database' => ['driver' => 'database', 'table' => 'cache', 'connection' => null],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
    ],
    'prefix' => 'bhatsapp_cache_',
];
