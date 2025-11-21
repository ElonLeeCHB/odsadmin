<?php

return [
    'app_fqdn' => env('APP_FQDN', 'localhost'),
    'default_country_code' => env('DEFAULT_COUNTRY_CODE', 'TW'),
    
    'admin_folder' => env('ADMIN_FOLDER', 'admin'),

    //keys
    'admin_access_key' => env('ADMIN_ACCESS_KEY', ''),
    'admin_api_key' => env('ADMIN_API_KEY', ''),
    'api_access_key' => env('API_ACCESS_KEY', ''),
    'api_api_key' => env('API_API_KEY', ''),
    'pos_access_key' => env('POS_ACCESS_KEY', ''),
    'pos_api_key' => env('POS_API_KEY', ''),
    'www_access_key' => env('WWW_ACCESS_KEY', ''),
    'www_api_key' => env('WWW_API_KEY', ''),
];
