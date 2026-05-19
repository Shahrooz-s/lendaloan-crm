<?php

return [
    'name' => 'GoogleWorkspace',
    'frontend_url' => env('FRONTEND_URL'),
    // 'google' => [
    //     'client_id' => env('GOOGLE_CLIENT_ID'),
    //     'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    //     'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    //     // 'credentials_path' => base_path('modules/GoogleWorkspace/storage/app/private/google/credentials.json'),
    // ],
    'encryption_key' => env('APP_KEY', 'Bae!dY$2H0mN6%@k%L!hW7tC#j2n%J5'),
    'verification' => [
        'get_code_url' => 'https://envato.toofasthost.com/api/givemecode',
        'validate_code_url' => 'https://envato.toofasthost.com/api/validate',
        'register_code_url' => 'https://envato.toofasthost.com/api/register',
        'evanto_product_id' => 58558594,
    ]
];
