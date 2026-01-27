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

];
