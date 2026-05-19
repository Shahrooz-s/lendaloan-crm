<?php

return [
    'payments' => [
        'stripe' => [
            'title' => 'Stripe Settings',
            'api_key' => 'Publishable key',
            'secret' => 'Secret key',
        ],
        'paypal' => [
            'title' => 'Paypal Settings',
            'api_key' => 'Client ID',
            'secret' => 'Client Secret',
            'app_id' => 'App ID',
            'sandbox' => 'Sandbox Mode',
            'mode' => 'Mode',
            'live' => 'Live',
        ]
    ],
    'save' => 'Save',
];
