<?php

return [
    'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),
    'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v21.0'),

    /*
     | Sandbox mode short-circuits every Graph call with a realistic fake
     | response so templates, sends and delivery receipts can be demoed
     | end-to-end before a WABA is connected.
     */
    'sandbox' => (bool) env('WHATSAPP_SANDBOX', true),
    'sandbox_approval_delay' => (int) env('WHATSAPP_SANDBOX_APPROVAL_DELAY', 30),

    'timeout' => 20,

    'categories' => [
        'MARKETING' => 'Marketing',
        'UTILITY' => 'Utility',
        'AUTHENTICATION' => 'Authentication',
    ],

    'statuses' => ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'PAUSED', 'DISABLED'],

    // Fallback per-message rates (INR) used when no rate row matches.
    'fallback_rates' => [
        'MARKETING' => 0.7846,
        'UTILITY' => 0.1146,
        'AUTHENTICATION' => 0.1250,
        'SERVICE' => 0.0,
    ],
];
