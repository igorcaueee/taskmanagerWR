<?php

return [
    'client_id'     => env('CONTA_AZUL_CLIENT_ID'),
    'client_secret' => env('CONTA_AZUL_CLIENT_SECRET'),
    'redirect_uri'  => env('CONTA_AZUL_REDIRECT_URI'),
    'base_url'      => env('CONTA_AZUL_BASE_URL', 'https://api-v2.contaazul.com'),

    'auth_url'  => 'https://auth.contaazul.com/auth/realms/prod/protocol/openid-connect/auth',
    'token_url' => 'https://auth.contaazul.com/auth/realms/prod/protocol/openid-connect/token',

    'scopes' => ['openid', 'profile', 'email', 'offline_access'],
];
