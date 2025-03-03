# Laravel Request Logger

A lightweight, feature-rich HTTP request logging and dashboard package for Laravel, with advanced security features, like a auto ban firewall, and performance monitoring, this can protect your application from malicious like androxgh0st, sql injection, and more.

Images

![Dashboard](https://github.com/mariojgt/magiclog/blob/main/art/image02.png)
![Dashboard](https://github.com/mariojgt/magiclog/blob/main/art/image01.png)

## Features

- 🚀 Lightweight and performant request logging
- 📊 Beautiful, responsive dashboard
- 🔒 Configurable logging options
- 📈 Detailed request insights
- 🛡️ Sensitive data protection

## Installation

Install the package via Composer:

```bash
composer require magiclog/request-logger
```

Run the migrations:

```bash
php artisan migrate
```

## Dashboard Access

Access the dashboard at `/request-logger` (requires authentication)

## Advanced Features

- Detailed request logging
- Performance tracking
- Error rate monitoring
- Exportable logs

## Performance Considerations

- Configurable log rotation
- Lightweight logging mechanism
- Minimal performance overhead

## Security

- Authentication required
- Sensitive data filtering
- IP and user tracking

## Configuration Options
- Add this REQUEST_LOGGER_AUTH_GUARD to your .env file to specify the guard to use for authentication to avoid the public access to the dashboard

## Troubleshooting

- Check configuration file
- Ensure proper middleware registration
- Verify database connection

## Contributing

Contributions are welcome! Please submit pull requests.

## Performance

This package is designed to be lightweight but we store the request data in the database so it can have an impact on the performance of your application. We recommend using this package for simple applications or simple api's.

## Auto spawn ban

# How the IP Ban Middleware Works

## Overview

The IP Ban Middleware is a security layer that automatically detects and blocks potentially malicious IPs attempting to access your application. It works by monitoring incoming requests for suspicious patterns, implementing rate limiting, and serving misleading responses to banned IPs.

## Flow of Operation

Here's how the middleware processes each request:

1. **Initial Checks**
   - Verifies if IP banning is enabled in the configuration
   - Checks if the current IP is in the whitelist (skips all checks if true)

2. **Ban Status Check**
   - Checks if the IP is already banned (first in cache, then in database)
   - If banned, returns a misleading response

3. **Safe Path Check**
   - Verifies if the requested path is in the safe paths list
   - Skips attack detection for safe paths

4. **Attack Pattern Detection**
   - Examines the request path for known attack patterns
   - Checks for suspicious file extensions
   - Analyzes query parameters for SQL injection attempts
   - Looks for suspicious parameter names
   - If an attack is detected, bans the IP and returns a misleading response

5. **Rate Limiting**
   - Increments a counter for the IP in the cache
   - Tracks unique paths accessed by the IP
   - If the request count exceeds the threshold or too many unique paths are accessed in a short time, bans the IP

6. **Normal Processing**
   - If no issues are detected, passes the request to the next middleware

## Key Components

### 1. Ban Detection

```php
protected function isBanned(string $ip): bool
{
    // Check cache first for performance
    if (Cache::has($this->banCachePrefix . $ip)) {
        return true;
    }

    // Then check database for persistence
    $banned = BannedIp::isBanned($ip);

    // If banned in database but not in cache, add to cache
    if ($banned) {
        $banData = BannedIp::where('ip_address', $ip)
            ->where('banned_until', '>', now())
            ->first();

        if ($banData) {
            Cache::put(
                $this->banCachePrefix . $ip,
                true,
                $banData->banned_until
            );
        }
    }

    return $banned;
}
```

This method uses a two-layer approach:
- **Cache** - For fast lookups on each request
- **Database** - For persistence across server restarts

### 2. Attack Pattern Detection

```php
protected function isAttackPattern(Request $request): bool
{
    $path = $request->path();

    // Check for suspicious paths
    foreach ($suspiciousPatterns as $pattern) {
        if (strpos($path, $pattern) !== false) {
            return true;
        }
    }

    // Check for suspicious file extensions
    // Check for SQL injection patterns
    // Check for suspicious query parameters
    // ...
}
```

This method examines the request for various signs of malicious activity:
- Attempts to access sensitive files (like `.git` repositories, `.env` files)
- Common vulnerability scanning patterns
- SQL injection attempts
- And more

### 3. Rate Limiting

```php
protected function shouldRateLimit(string $ip): bool
{
    $key = $this->requestCountPrefix . $ip;
    $threshold = config('request-logger.rate_limit_threshold', 20);
    $pathsKey = $this->requestCountPrefix . $ip . ':paths';

    // Check if request count exceeds threshold
    if (Cache::has($key) && Cache::get($key) > $threshold) {
        return true;
    }

    // Also check if accessing many different paths in short time (scanning behavior)
    if (Cache::has($pathsKey)) {
        $paths = Cache::get($pathsKey);
        if (count($paths) >= 5) { // If accessing 5+ different paths in short time
            return true;
        }
    }

    return false;
}
```

This implements two types of rate limiting:
- **Request volume** - Too many requests in a short time window
- **Path diversity** - Accessing many different paths quickly (typical of scanning)

### 4. IP Banning

```php
protected function banIp(string $ip, ?int $hours = null): void
{
    // Use default ban duration from config if not specified
    $hours = $hours ?? config('request-logger.ban_duration', 24);

    // Store in cache
    Cache::put($this->banCachePrefix . $ip, $banData, now()->addHours($hours));

    // Store in database for permanent record
    try {
        BannedIp::banIp($ip, 'Suspicious activity detected', $hours, [
            'time' => now()->toIso8601String(),
            'request_count' => Cache::get($this->requestCountPrefix . $ip, 0),
            'paths' => Cache::get($this->requestCountPrefix . $ip . ':paths', []),
        ]);
    } catch (\Exception $e) {
        Log::error("Failed to log banned IP to database: {$e->getMessage()}");
    }
}
```

When an IP is banned:
1. It's added to the cache for immediate effect
2. It's recorded in the database for persistence
3. Ban details are logged for later analysis

### 5. Misleading Responses

```php
protected function handleBannedRequest(Request $request): Response
{
    // Random response type for variety
    $responseType = rand(1, 6);

    switch ($responseType) {
        case 1: // Fake success with misleading data
        case 2: // Fake server overload
        case 3: // Fake access denied with security hints
        case 4: // Corrupted JSON response
        case 5: // Extremely slow response
        case 6: // Security challenge page
        // ...
    }
}
```

Rather than simply blocking banned IPs with a 403 error, the middleware serves a variety of misleading responses to:
1. Waste the attacker's time and resources
2. Provide false information to automated tools
3. Make it harder to determine if the attack was detected

## Configuration Options

The middleware's behavior can be customized through several configuration options:

```php
// Enable or disable IP banning
'ip_ban_enabled' => env('REQUEST_LOGGER_IP_BAN_ENABLED', true),

// Number of requests allowed in the rate limit window
'rate_limit_threshold' => env('REQUEST_LOGGER_RATE_LIMIT', 20),

// Rate limit window in seconds
'rate_limit_window' => env('REQUEST_LOGGER_RATE_WINDOW', 30),

// Default ban duration in hours
'ban_duration' => env('REQUEST_LOGGER_BAN_DURATION', 24),

// IPs that should never be banned
'whitelist' => ['127.0.0.1'],

// Paths that shouldn't trigger attack detection
'safe_paths' => ['api/webhook/github'],

// Enable or disable misleading responses
'enable_misleading_responses' => env('REQUEST_LOGGER_MISLEAD', true),
```

## Database Storage

All banned IPs are stored in the database with:
- IP address
- Reason for ban
- Ban duration
- Attack details
- Request count
- Ban history (number of times banned)

This allows for later analysis and reporting on attack patterns.

## Performance Considerations

The middleware is designed to be efficient by:
1. Using cache for fast lookups of ban status
2. Checking simple conditions first before more complex ones
3. Only performing database operations when necessary

This ensures minimal impact on legitimate requests while still providing robust protection.

## License

MIT License
