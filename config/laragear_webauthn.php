<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebAuthn Relaying Party
    |--------------------------------------------------------------------------
    |
    | This is the information of the application that will be used in the
    | WebAuthn protocol. The name will be shown in the browser when
    | authenticating with WebAuthn. The ID must be the domain name.
    */

    'relying_party' => [
        'name' => env('APP_NAME', 'Laravel'),
        'id' => env('WEBAUTHN_RELYING_PARTY_ID', 'localhost'),
        'icon' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Challenge
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the WebAuthn challenge. The challenge
    | is used to prevent replay attacks. The timeout is in seconds.
    */

    'challenge' => [
        'bytes' => 32,
        'timeout' => 60,
        'cache' => [
            'prefix' => 'webauthn',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Credentials
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the WebAuthn credentials. The credentials
    | are stored in the database. The table name can be customized here.
    */

    'credentials' => [
        'model' => \App\Models\WebAuthnCredential::class,
        'table' => 'webauthn_credentials',
        'id' => 'id',
        'user' => [
            'id' => 'authenticatable_id',
            'display_name' => 'name',
            'name' => 'email',
            'handle' => 'user_handle',
        ],
        'timestamps' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Authenticators
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the WebAuthn authenticators. The
    | authenticators are used to verify the user's identity.
    */

    'authenticators' => [
        'default' => [
            'attachment' => 'platform',
            'user_verification' => 'preferred',
            'require_resident_key' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Session
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the WebAuthn session. The session is used
    | to store the WebAuthn challenge.
    */

    'session' => [
        'key' => 'webauthn',
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Routes
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the WebAuthn routes. The routes are used
    | to handle the WebAuthn protocol.
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'webauthn',
        'middleware' => ['web'],
        'name' => 'webauthn.',
    ],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn User Verification
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the WebAuthn user verification. The user
    | verification is used to verify the user's identity.
    */

    'user_verification' => 'preferred',
];
