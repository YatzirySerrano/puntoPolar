<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'openpay'),

    'gateways' => [
        'openpay' => [
            'merchant_id' => env('OPENPAY_MERCHANT_ID'),
            'public_key' => env('OPENPAY_PUBLIC_KEY'),
            'private_key' => env('OPENPAY_PRIVATE_KEY'),
            'sandbox' => env('OPENPAY_SANDBOX', true),

            'sandbox_base_url' => env('OPENPAY_SANDBOX_BASE_URL', 'https://sand-api.ecommercebbva.com'),
            'production_base_url' => env('OPENPAY_PRODUCTION_BASE_URL', 'https://api.ecommercebbva.com'),

            'webhook_secret' => env('OPENPAY_WEBHOOK_SECRET'),
        ],

        'stripe' => [
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],
];
