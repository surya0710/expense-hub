<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default role for users who auto-join via email domain
    |--------------------------------------------------------------------------
    */
    'domain_join_role' => env('ORG_DOMAIN_JOIN_ROLE', 'employee'),

    /*
    |--------------------------------------------------------------------------
    | Personal / free email domains — never used for auto-join or org claiming
    |--------------------------------------------------------------------------
    */
    'generic_email_domains' => [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'yahoo.co.in',
        'hotmail.com',
        'outlook.com',
        'live.com',
        'msn.com',
        'icloud.com',
        'me.com',
        'mac.com',
        'aol.com',
        'proton.me',
        'protonmail.com',
        'zoho.com',
        'yandex.com',
        'mail.com',
        'gmx.com',
        'rediffmail.com',
    ],

];
