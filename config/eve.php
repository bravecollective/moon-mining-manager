<?php

return [
    'rent_corporation_id'            => env('RENT_CORPORATION_ID'),
    'tax_corporation_id'             => env('TAX_CORPORATION_ID'),
    'admin_user_id'                  => env('ADMIN_USER_ID', 0),
    'mail_user_id'                   => env('MAIL_USER_ID', 0),
    'rent_corporation_prime_user_id' => env('RENT_CORPORATION_PRIME_USER_ID'),
    'tax_corporation_prime_user_id'  => env('TAX_CORPORATION_PRIME_USER_ID'),
    'alliances_whitelist'            => env('EVE_ALLIANCES_WHITELIST'),
    'corporations_whitelist'         => env('EVE_CORPORATIONS_WHITELIST'),
    'alliances_login'                => env('EVE_ALLIANCES_LOGIN'),
    'corporations_login'             => env('EVE_CORPORATIONS_LOGIN'),
    'admin_email'                    => env('ADMIN_EMAIL'),
    'slack_webhook_url'              => env('SLACK_WEBHOOK_URL', ''),
];
