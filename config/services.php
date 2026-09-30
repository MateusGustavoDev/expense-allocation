<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Cotação PTAX do Banco Central, usada na conversão das despesas em moeda estrangeira
    'ptax' => [
        'base_url' => env('PTAX_BASE_URL', 'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/'),
        'timeout' => (int) env('PTAX_TIMEOUT', 10),
        // A PTAX é publicada no horário de Brasília: define o que é "hoje" para a cotação
        'timezone' => 'America/Sao_Paulo',
    ],

];
