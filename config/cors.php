<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // 'allowed_methods' => ['*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],

    // 'allowed_origins' => ['*'],
    'allowed_origins' => [
        // 同站的請求不需要設定
        // 'https://ods.dtstw.com',
        // 以下測試
        'https://fake-origin-WrksphDX.test',
        'http://localhost:60501', // ods localhost
        'http://localhost:60502', // www localhost
        'http://localhost:60503', // hrc localhost
        // 以下官網
        'https://www.chinabing.net', // www
        ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 'supports_credentials' => false,
    'supports_credentials' => true,

];
