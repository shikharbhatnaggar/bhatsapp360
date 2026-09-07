<?php

return [
    // Where top-up payments land. Shown in the QR code customers scan.
    'upi' => [
        'vpa' => env('UPI_VPA', 'yourbusiness@upi'),
        'payee_name' => env('UPI_PAYEE_NAME', 'Bhatsapp'),
    ],

    'currency' => env('WALLET_CURRENCY', 'INR'),

    // Smallest accepted top-up, and the quick-pick amounts on the form.
    'minimum_topup' => (float) env('WALLET_MIN_TOPUP', 500),
    'suggested_amounts' => [500, 1000, 2500, 5000],

    // Warn the customer once the balance drops below this.
    'low_balance_threshold' => (float) env('WALLET_LOW_BALANCE', 100),
];
