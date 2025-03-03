<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Request Logger Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the request logger package
    |
    */

    // Global enable/disable logging
    'enabled' => env('REQUEST_LOGGER_ENABLED', true),

    // Paths to ignore logging
    'ignored_paths' => [
        'telescope*',
        'horizon*',
        'debugbar*',
        'admin/telescope*',
        '_ignition*',
    ],

    // HTTP methods to ignore
    'ignored_methods' => [
        // 'OPTIONS',
    ],

    // Routes to ignore by name
    'ignored_routes' => [
        // 'route.name.to.ignore',
    ],

    // Parameters to hide from logging (sensitive information)
    'hidden_parameters' => [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'secret',
        'token',
        'api_key',
        'refresh_token',
        'authorization',
    ],

    // Headers to hide from logging
    'hidden_headers' => [
        'authorization',
        'cookie',
        'set-cookie',
    ],

    // Maximum number of logs to keep
    'max_logs' => env('REQUEST_LOGGER_MAX_LOGS', 10000),

    // Log rotation settings
    'log_rotation' => [
        'enabled' => true,
        'delete_older_than_days' => 30,
    ],

    // Performance tracking
    'performance' => [
        // Log requests slower than X milliseconds
        'slow_request_threshold' => 500,

        // Enable detailed performance logging
        'detailed_logging' => true,
    ],

    // Notification settings for performance issues
    'notifications' => [
        'enabled' => false,
        'threshold' => [
            'error_rate' => 5,  // Percentage of error requests
            'slow_requests' => 10,  // Number of slow requests
        ],
        'channels' => ['mail', 'slack'],
    ],
    'capture_response_body' => true,  // Enable response body capture
    'async_logging' => true,          // Use queue for logging
    'hidden_response_fields' => [     // Fields to redact from responses
        'password', 'token', 'secret'
    ],
    'auth_guard' => env('REQUEST_LOGGER_AUTH_GUARD', 'web'),  // Auth guard to use for user identification

    /*
    |--------------------------------------------------------------------------
    | IP Ban Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the IP banning system
    |
    */

    // Enable or disable IP banning
    'ip_ban_enabled' => env('REQUEST_LOGGER_IP_BAN_ENABLED', true),

    // Number of requests allowed in the rate limit window
    'rate_limit_threshold' => env('REQUEST_LOGGER_RATE_LIMIT', 20),

    // Rate limit window in seconds
    'rate_limit_window' => env('REQUEST_LOGGER_RATE_WINDOW', 30),

    // Default ban duration in hours
    'ban_duration' => env('REQUEST_LOGGER_BAN_DURATION', 5),

    // IPs that should never be banned (e.g., your office IPs)
    'whitelist' => [
        '127.0.0.1',
        // Add your office/home IPs here
    ],

    // Paths that shouldn't trigger attack detection (e.g., your API endpoints)
    'safe_paths' => [
        'api/webhook/github', // GitHub webhook
        // Add other legitimate API endpoints that might receive high traffic
    ],

    // Enable or disable misleading responses for banned IPs
    'enable_misleading_responses' => env('REQUEST_LOGGER_MISLEAD', true),
];
