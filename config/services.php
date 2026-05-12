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

    'onecard' => [
        'base_url' => env('ONECARD_BASE_URL'),
        'merchants_endpoint' => env('ONECARD_MERCHANTS_ENDPOINT', '/integration/get-merchant-list'),
        'products_endpoint' => env('ONECARD_PRODUCTS_ENDPOINT', '/integration/detailed-products-list'),
        'purchase_product_endpoint' => env('ONECARD_PURCHASE_PRODUCT_ENDPOINT', '/integration/purchase-product'),
        'reseller_username' => env('ONECARD_RESELLER_USERNAME'),
        'merchants_password' => env('ONECARD_MERCHANTS_PASSWORD'),
        'log_requests' => env('ONECARD_LOG_REQUESTS', false),
        'secret_key' => env('ONECARD_SECRET_KEY'),
        'terminal_id' => env('ONECARD_TERMINAL_ID'),
        'sync_user_id' => env('ONECARD_SYNC_USER_ID', 1),
        'company_name' => env('ONECARD_COMPANY_NAME', 'one_card'),
        'timeout' => env('ONECARD_TIMEOUT', 30),
    ],

    'like4app' => [
        'base_url' => env('LIKE4APP_BASE_URL', 'https://taxes.like4app.com'),
        'categories_url' => env('LIKE4APP_CATEGORIES_URL', 'https://taxes.like4app.com/online/categories'),
        'products_url' => env('LIKE4APP_PRODUCTS_URL', 'https://taxes.like4app.com/online/products'),
        'create_order_endpoint' => env('LIKE4APP_CREATE_ORDER_ENDPOINT', '/online/order'),
        'device_id' => env('LIKE4APP_DEVICE_ID'),
        'email' => env('LIKE4APP_EMAIL'),
        'security_code' => env('LIKE4APP_SECURITY_CODE'),
        'lang_id' => env('LIKE4APP_LANG_ID', 1),
        'company_name' => env('LIKE4APP_COMPANY_NAME', 'like card'),
        'sync_user_id' => env('LIKE4APP_SYNC_USER_ID', 1),
        'connect_timeout' => env('LIKE4APP_CONNECT_TIMEOUT', 15),
        'timeout' => env('LIKE4APP_TIMEOUT', 30),
    ],

    'sadad' => [
        'base_url' => env('SADAD_BASE_URL'),
        'client_key' => env('SADAD_CLIENT_KEY'),
        'client_secret' => env('SADAD_CLIENT_SECRET'),
        'token' => env('SADAD_TOKEN'),
        'access_token' => env('SADAD_ACCESS_TOKEN'),
        'payment_base_url' => env('SADAD_PAYMENT_BASE_URL', 'https://sadadpay.net'),
        'token_endpoint' => env('SADAD_TOKEN_ENDPOINT', '/Token'),
        'create_invoice_endpoint' => env('SADAD_CREATE_INVOICE_ENDPOINT', '/Invoice/insert'),
        'get_invoice_endpoint' => env('SADAD_GET_INVOICE_ENDPOINT', '/Invoice/getbyid'),
        'refresh_token_endpoint' => env('SADAD_REFRESH_TOKEN_ENDPOINT', '/User/GenerateRefreshToken'),
        'access_token_endpoint' => env('SADAD_ACCESS_TOKEN_ENDPOINT', '/User/GenerateAccessToken'),
        'log_requests' => env('SADAD_LOG_REQUESTS', false),
        'driver' => env('SADAD_DRIVER', 'http'),
        'is_test' => env('SADAD_IS_TEST', true),
        'timeout' => env('SADAD_TIMEOUT', 30),
        'sdk_fallback_http' => env('SADAD_SDK_FALLBACK_HTTP', true),
    ],

    'sms' => [
        'iid' => env('SMS_IID'),
        'uid' => env('SMS_UID'),
        'password' => env('SMS_PASSWORD'),
        'sender' => env('SMS_SENDER'),
        'base_url' => env('SMS_BASE_URL'),
    ],

    'whatsapp' => [
        'message_url' => env('WHATSAPP_MESSAGE_URL'),
        'token' => env('WHATSAPP_API_TOKEN'),
    ],

    'tabby' => [
        'base_url' => env('TABBY_BASE_URL', 'https://api.tabby.ai'),
        'merchant_code' => env('TABBY_MERCHANT_CODE'),
        'public_key' => env('TABBY_PUBLIC_KEY'),
        'secret_key' => env('TABBY_SECRET_KEY'),
        'lang' => env('TABBY_LANG', 'en'),
        'timeout' => env('TABBY_TIMEOUT', 30),
        'webhook_url' => env('TABBY_WEBHOOK_URL'),
        'merchant_urls' => [
            'success' => env('TABBY_SUCCESS_URL', rtrim(env('APP_URL', ''), '/').'/tabby/success'),
            'cancel' => env('TABBY_CANCEL_URL', rtrim(env('APP_URL', ''), '/').'/tabby/cancel'),
            'failure' => env('TABBY_FAILURE_URL', rtrim(env('APP_URL', ''), '/').'/tabby/failure'),
        ],
        'webhook' => [
            'header_name' => env('TABBY_WEBHOOK_HEADER_NAME'),
            'header_value' => env('TABBY_WEBHOOK_HEADER_VALUE'),
        ],
    ],

    'eezee_pay' => [
        'base_url' => env('EEZEEP_BASE_URL', 'https://sandbox.eezee-pay.com/api/v1'),
        'company_name' => env('EEZEEP_COMPANY_NAME', 'eezee_pay'),
        'sync_user_id' => env('EEZEEP_SYNC_USER_ID', 1),
        'email' => env('EEZEEP_EMAIL'),
        'phone' => env('EEZEEP_PHONE'),
        'password' => env('EEZEEP_PASSWORD'),
        'login_username' => env('EEZEEP_LOGIN_USERNAME'),
        'login_username_key' => env('EEZEEP_LOGIN_USERNAME_KEY', 'username'),
        'login_password_key' => env('EEZEEP_LOGIN_PASSWORD_KEY', 'password'),
        'token' => env('EEZEEP_TOKEN'),
        'timeout' => env('EEZEEP_TIMEOUT', 30),
        'connect_timeout' => env('EEZEEP_CONNECT_TIMEOUT', 15),
        'user_agent' => env('EEZEEP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'),
        'auth_header' => env('EEZEEP_AUTH_HEADER', 'Authorization'),
        'auth_scheme' => env('EEZEEP_AUTH_SCHEME', 'Bearer'),
        'remember_token_in_cache' => env('EEZEEP_REMEMBER_TOKEN', true),
        'token_cache_ttl' => env('EEZEEP_TOKEN_CACHE_TTL', 3600),
        'token_cache_key' => env('EEZEEP_TOKEN_CACHE_KEY', 'eezee_pay.access_token'),
        'regenerate_token_path' => env('EEZEEP_REGENERATE_PATH', '/token/regenerate'),
        'auto_login_on_auth_error' => env('EEZEEP_AUTO_LOGIN_ON_AUTH_ERROR', true),
        'fresh_login_each_request' => env('EEZEEP_FRESH_LOGIN_EACH_REQUEST', false),
    ],
    'dema' => [
        // Deema Direct API (not Tap)
        'base_url' => env('DEMA_BASE_URL', 'https://sandbox-api.deema.me'),
        'api_key' => env('DEMA_API_KEY'),
        'api_prefix' => env('DEMA_API_PREFIX', '/api/merchant/v1'),
        'timeout' => env('DEMA_TIMEOUT', 30),
        'merchant_urls' => [
            'success' => env('DEMA_SUCCESS_URL', rtrim(env('APP_URL', ''), '/').'/dema/success'),
            'failure' => env('DEMA_FAILURE_URL', rtrim(env('APP_URL', ''), '/').'/dema/failure'),
        ],
        'webhook' => [
            'header_name' => env('DEMA_WEBHOOK_HEADER_NAME'),
            'header_value' => env('DEMA_WEBHOOK_HEADER_VALUE'),
        ],
    ],

];
