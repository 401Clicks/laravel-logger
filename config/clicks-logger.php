<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 401 Clicks API URL
    |--------------------------------------------------------------------------
    |
    | The URL endpoint where logs will be sent. You can leave this as the
    | default unless you're using a self-hosted 401 Clicks instance.
    |
    */

    'url' => env('CLICKS_LOG_URL', 'https://logs.401clicks.com/api/v1/logs'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Your 401 Clicks API token. You can find this in your project settings
    | at https://401clicks.com/projects/{project}/settings
    |
    */

    'token' => env('CLICKS_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Log Level
    |--------------------------------------------------------------------------
    |
    | The minimum log level to send to 401 Clicks. Available levels:
    | debug, info, notice, warning, error, critical, alert, emergency
    |
    */

    'level' => env('CLICKS_LOG_LEVEL', 'debug'),

    /*
    |--------------------------------------------------------------------------
    | Batch Settings
    |--------------------------------------------------------------------------
    |
    | Configure how logs are batched before being sent to the API.
    | This helps reduce the number of HTTP requests.
    |
    */

    'batch_size' => env('CLICKS_BATCH_SIZE', 10),

    'flush_interval' => env('CLICKS_FLUSH_INTERVAL', 5), // seconds

    /*
    |--------------------------------------------------------------------------
    | Data Masking
    |--------------------------------------------------------------------------
    |
    | Automatically mask sensitive data (PII, credentials, etc.) before
    | sending logs to 401 Clicks. This is the MOST SECURE option as data
    | is masked before it ever leaves your application.
    |
    | Note: 401 Clicks also provides server-side masking as a fallback
    | for logs sent via cURL or other non-package clients.
    |
    */

    'masking' => [

        /*
        |----------------------------------------------------------------------
        | Enable/Disable Masking
        |----------------------------------------------------------------------
        |
        | Set to false to disable client-side masking entirely.
        | Default: true (recommended)
        |
        */

        'enabled' => env('CLICKS_MASKING_ENABLED', true),

        /*
        |----------------------------------------------------------------------
        | Masking Style
        |----------------------------------------------------------------------
        |
        | How sensitive data should be masked:
        |
        | - 'full': Replace with placeholder (e.g., [CREDIT_CARD])
        | - 'partial': Keep last 4 characters (e.g., ****1234)
        | - 'hash': Replace with truncated SHA256 hash (e.g., [a3f8b2c1])
        |
        */

        'style' => env('CLICKS_MASKING_STYLE', 'full'),

        /*
        |----------------------------------------------------------------------
        | Enabled Patterns
        |----------------------------------------------------------------------
        |
        | Which types of sensitive data to detect and mask.
        | Available patterns:
        |
        | - 'credit_cards': Visa, MasterCard, Amex, Discover, JCB
        | - 'ssn': US Social Security Numbers (XXX-XX-XXXX)
        | - 'api_keys': Common API key patterns (sk_, pk_, Bearer tokens)
        | - 'passwords': Passwords in key-value contexts
        | - 'emails': Email addresses
        | - 'phone_numbers': US phone numbers in various formats
        | - 'ip_addresses': IPv4 addresses
        |
        */

        'patterns' => [
            'credit_cards',
            'ssn',
            'api_keys',
            'passwords',
            'phone_numbers',
            // 'emails',        // Uncomment to mask email addresses
            // 'ip_addresses',  // Uncomment to mask IP addresses
        ],

        /*
        |----------------------------------------------------------------------
        | Custom Patterns
        |----------------------------------------------------------------------
        |
        | Add your own regex patterns to mask custom sensitive data.
        | Each pattern should have: name, pattern (regex), replacement.
        |
        | Example:
        | [
        |     ['name' => 'Customer IDs', 'pattern' => '/CUST-[A-Z0-9]{8}/i', 'replacement' => '[CUSTOMER_ID]'],
        |     ['name' => 'Order Numbers', 'pattern' => '/ORD-\d{10}/', 'replacement' => '[ORDER_ID]'],
        | ]
        |
        */

        'custom_patterns' => [
            // Add your custom patterns here
        ],

        /*
        |----------------------------------------------------------------------
        | Maximum Recursion Depth
        |----------------------------------------------------------------------
        |
        | How deep to recurse into nested arrays when masking context data.
        | Default: 10 (should be sufficient for most use cases)
        |
        */

        'max_depth' => 10,

    ],

];
