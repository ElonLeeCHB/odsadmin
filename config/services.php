<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Accounts Center OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | 這裡是帳號管理中心 (Accounts Center) 的 OAuth 認證設定
    | 用於 POS 系統與帳號中心的統一身份驗證整合
    |
    */
    'accounts' => [
        // Accounts 中心的 API 基礎網址
        'url' => env('ACCOUNTS_CENTER_URL', 'https://accounts.huabing.tw'),

        // Client Code - 識別呼叫的客戶端系統
        'client_code' => env('ACCOUNTS_CLIENT_CODE', 'pos-system'),

        // System Code - 識別目標系統
        'system_code' => env('ACCOUNTS_SYSTEM_CODE', 'pos'),

        // API Debug Key - 用於 API 除錯
        'api_debug_key' => env('ACCOUNTS_API_DEBUG_KEY'),

        // API 請求逾時時間（秒）
        'timeout' => env('ACCOUNTS_TIMEOUT', 10),

        // 是否啟用 Fallback 機制（當 Accounts 中心無法連線時回退到本地驗證）
        'enable_fallback' => env('ACCOUNTS_ENABLE_FALLBACK', true),
    ],
];
