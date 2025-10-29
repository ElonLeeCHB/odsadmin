<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Giveme 電子發票 API 設定
    |--------------------------------------------------------------------------
    |
    | 此設定用於串接 Giveme 電子發票加值中心的 API 服務
    |
    */

    'giveme' => [
        // API 基礎網址
        'api_url' => env('GIVEME_INVOICE_API_URL', 'https://www.giveme.com.tw/invoice.do'),

        // 正式環境帳號（從 .env 讀取）
        'tax_id' => env('GIVEME_INVOICE_PROD_TAX_ID'),
        'account' => env('GIVEME_INVOICE_PROD_ACCOUNT'),
        'password' => env('GIVEME_INVOICE_PROD_PASSWORD'),
    ],

    // 測試環境帳號
    'test' => [
        'tax_id' => env('GIVEME_INVOICE_TEST_TAX_ID', '53418005'),      // 測試統編
        'account' => env('GIVEME_INVOICE_TEST_ACCOUNT', 'Giveme09'),    // 測試證號
        'password' => env('GIVEME_INVOICE_TEST_PASSWORD', '9VHGCq'),    // 測試密碼
    ],

];
