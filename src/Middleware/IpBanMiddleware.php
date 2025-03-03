<?php

namespace MagicLog\RequestLogger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use MagicLog\RequestLogger\Models\BannedIp;

class IpBanMiddleware
{
    /**
     * The cache key prefix for IP bans
     */
    protected string $banCachePrefix = 'ip_ban:';

    /**
     * The cache key prefix for request counts
     */
    protected string $requestCountPrefix = 'ip_requests:';

    /**
     * Attack pattern cache time (minutes)
     */
    protected int $patternCacheTime = 60;

    /**
     * Cached suspicious patterns
     */
    protected ?array $suspiciousPatterns = null;

    /**
     * Cached suspicious extensions
     */
    protected ?array $suspiciousExtensions = null;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if IP banning is disabled (quick check first)
        if (!config('request-logger.ip_ban_enabled', true)) {
            return $next($request);
        }

        $ip = $request->ip();

        // Skip for whitelisted IPs (quick check second)
        if ($this->isWhitelisted($ip)) {
            return $next($request);
        }

        // Check if IP is already banned (cache check is fast)
        if (Cache::has($this->banCachePrefix . $ip)) {
            return $this->handleBannedRequest($request);
        }

        // Database check for ban (only if not in cache)
        if (BannedIp::isBanned($ip)) {
            // Add to cache for future requests
            $banData = BannedIp::where('ip_address', $ip)
                ->where('banned_until', '>', now())
                ->first();

            if ($banData) {
                Cache::put(
                    $this->banCachePrefix . $ip,
                    [
                        'banned_at' => $banData->created_at->toIso8601String(),
                        'expires_at' => $banData->banned_until->toIso8601String(),
                        'reason' => $banData->reason
                    ],
                    $banData->banned_until
                );
            }

            return $this->handleBannedRequest($request);
        }

        // Skip attack detection for safe paths (performance optimization)
        if ($this->isSafePath($request)) {
            return $next($request);
        }

        // Quick check for obvious attack patterns before deeper analysis
        $path = $request->path();
        if (str_contains($path, '.git') || str_contains($path, '.env') || str_contains($path, 'wp-')) {
            $this->banIp($ip);
            Log::warning("IP banned due to obvious attack pattern: {$ip}", [
                'path' => $path,
                'method' => $request->method(),
                'user_agent' => $request->userAgent()
            ]);
            return $this->handleBannedRequest($request);
        }

        // Full attack pattern check
        if ($this->isAttackPattern($request)) {
            $this->banIp($ip);
            Log::warning("IP banned due to attack pattern: {$ip}", [
                'path' => $path,
                'method' => $request->method(),
                'user_agent' => $request->userAgent()
            ]);
            return $this->handleBannedRequest($request);
        }

        // Increment request count for rate limiting
        $this->incrementRequestCount($ip);

        // Track paths (only if not already at limit)
        $key = $this->requestCountPrefix . $ip;
        $pathsKey = $this->requestCountPrefix . $ip . ':paths';
        $threshold = config('request-logger.rate_limit_threshold', 20);

        if (!Cache::has($key) || Cache::get($key) < $threshold) {
            $this->trackPaths($ip, $path);
        }

        // Check for rate limiting (too many requests in a short time)
        if ($this->shouldRateLimit($ip)) {
            $this->banIp($ip, 1, true); // Ban for 1 minute
            Log::warning("IP banned due to rate limiting: {$ip}", [
                'request_count' => Cache::get($key),
                'window' => config('request-logger.rate_limit_window', 30) . 's'
            ]);
            return $this->handleBannedRequest($request);
        }

        return $next($request);
    }

    /**
     * Check if an IP is whitelisted
     */
    protected function isWhitelisted(string $ip): bool
    {
        // Cache whitelist for performance
        $whitelist = Cache::remember('ip_ban_whitelist', 60, function () {
            return config('request-logger.whitelist', []);
        });

        return in_array($ip, $whitelist);
    }

    /**
     * Check if the request path is in the safe paths list
     */
    protected function isSafePath(Request $request): bool
    {
        // Cache safe paths for performance
        $safePaths = Cache::remember('ip_ban_safe_paths', 60, function () {
            return config('request-logger.safe_paths', []);
        });

        foreach ($safePaths as $safePath) {
            if ($request->is($safePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle a banned request with misleading responses designed to discourage further scanning
     */
    protected function handleBannedRequest(Request $request): Response
    {
        // Check if misleading responses are enabled
        if (!config('request-logger.enable_misleading_responses', true)) {
            // Standard block response
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied',
            ], 403);
        }

        // Add SC prefix to track bots/scanners
        $scPrefix = "SC" . rand(1000, 9999) . ": ";

        // Get IP and request info for tracking
        $ip = $request->ip();
        $path = $request->path();

        // Track hits from this banned IP for escalating response strategy
        $banHitKey = "banned_hit:{$ip}";
        $hitCount = 1;

        if (Cache::has($banHitKey)) {
            $hitCount = Cache::increment($banHitKey);
        } else {
            Cache::put($banHitKey, 1, now()->addHours(24));
        }

        // Escalating strategy based on hit counts from this IP
        // The more they hit, the less appealing/useful the responses become
        if ($hitCount <= 2) {
            // First couple hits - return errors that might discourage further scanning
            $errorTypes = [
                // 403 - Blocked
                [
                    'code' => 403,
                    'body' => [
                        'status' => 'error',
                        'message' => $scPrefix . 'Access denied. Your IP has been logged.',
                        'request_id' => $this->generateRandomString(12),
                    ]
                ],
                // 429 - Too Many Requests
                [
                    'code' => 429,
                    'body' => [
                        'status' => 'error',
                        'message' => $scPrefix . 'Rate limit exceeded',
                        'retry_after' => rand(3600, 86400), // 1-24 hours in seconds
                    ]
                ],
                // 503 - Service Unavailable
                [
                    'code' => 503,
                    'body' => [
                        'status' => 'error',
                        'message' => $scPrefix . 'Service temporarily unavailable',
                        'retry_after' => rand(300, 1800), // 5-30 minutes
                    ]
                ]
            ];

            $randIndex = array_rand($errorTypes);
            return response()->json($errorTypes[$randIndex]['body'], $errorTypes[$randIndex]['code']);
        } elseif ($hitCount <= 5) {
            // Next few hits - make them wait with slow responses
            sleep(rand(3, 8)); // Slow down significantly

            return response()->json([
                'status' => 'error',
                'message' => $scPrefix . 'Request timed out',
                'request_id' => $this->generateRandomString(16),
            ], 504); // Gateway Timeout
        } elseif ($hitCount <= 10) {
            // Next set - broken/partial responses to frustrate parsers
            $responses = [
                // Corrupted JSON
                function () use ($scPrefix) {
                    $corruptJson = '{
                        "status": "error",
                        "message": "' . $scPrefix . 'Internal server error",
                        "debug';

                    return new Response($corruptJson, 500, [
                        'Content-Type' => 'application/json'
                    ]);
                },

                // Incomplete headers
                function () {
                    // Just send back an empty response with a 200 status
                    return new Response('', 200);
                },

                // HTML in JSON response
                function () use ($scPrefix) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $scPrefix . '<div class="error">Server configuration error</div>',
                        'trace' => '<a href="#">View stack trace</a>'
                    ], 500);
                }
            ];

            $randFunction = array_rand($responses);
            return $responses[$randFunction]();
        } else {
            // Many hits - increasingly useless/discouraging responses

            // For high hit counts: primarily return empty data or nonsense
            // to make scanning completely pointless
            $uselessResponses = [
                // Empty arrays
                function () {
                    return response()->json([], 200);
                },

                // 404 - Not Found
                function () use ($scPrefix) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $scPrefix . 'Resource not found'
                    ], 404);
                },

                // Random letters
                function () {
                    return new Response(
                        $this->generateRandomString(rand(20, 50)),
                        200,
                        ['Content-Type' => 'text/plain']
                    );
                },

                // Require authentication
                function () use ($scPrefix) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $scPrefix . 'Authentication required',
                    ], 401);
                },

                // Tell them we're onto them
                function () use ($scPrefix, $ip) {
                    return response()->json([
                        'status' => 'warning',
                        'message' => $scPrefix . 'Scanning behavior detected',
                        'source_ip' => $ip,
                        'actions_taken' => 'IP reported to admin'
                    ], 403);
                }
            ];

            $randFunction = array_rand($uselessResponses);
            return $uselessResponses[$randFunction]();
        }
    }

    /**
     * Generate a random string
     */
    protected function generateRandomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characterCount = strlen($characters);
        $randomString = '';

        // Using a more efficient method
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $characterCount - 1)];
        }

        return $randomString;
    }

    /**
     * Obfuscate IP address for display (hide last octet)
     */
    protected function obfuscateIp(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) { // IPv4
            $parts[3] = 'xxx';
            return implode('.', $parts);
        }

        // For IPv6, just return first half
        if (strpos($ip, ':') !== false) {
            $parts = explode(':', $ip);
            $half = ceil(count($parts) / 2);
            return implode(':', array_slice($parts, 0, $half)) . ':xxxx:xxxx';
        }

        return $ip;
    }

    /**
     * Simple SQL injection detection
     */
    protected function containsSqlInjection(string $value): bool
    {
        // Cache patterns for performance
        static $patterns = null;

        if ($patterns === null) {
            $patterns = [
                '/\s+OR\s+[\'"]\s*[^\'"]+\s*[\'"]/',
                '/\s+AND\s+[\'"]\s*[^\'"]+\s*[\'"]/',
                '/UNION\s+SELECT/i',
                '/SELECT\s.*FROM/i',
                '/INSERT\s+INTO/i',
                '/UPDATE\s.*SET/i',
                '/DELETE\s+FROM/i',
                '/DROP\s+TABLE/i',
                '/SLEEP\s*\(\s*[0-9]+\s*\)/i',
                '/BENCHMARK\s*\(/i',
            ];
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Increment the request count for an IP
     */
    protected function incrementRequestCount(string $ip): void
    {
        $key = $this->requestCountPrefix . $ip;
        $window = config('request-logger.rate_limit_window', 30); // Window in seconds

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, now()->addSeconds($window));
        }
    }

    /**
     * Track unique paths accessed by an IP
     */
    protected function trackPaths(string $ip, string $path): void
    {
        $pathsKey = $this->requestCountPrefix . $ip . ':paths';
        $window = config('request-logger.rate_limit_window', 30);

        $paths = Cache::get($pathsKey, []);

        if (!in_array($path, $paths)) {
            $paths[] = $path;
            Cache::put($pathsKey, $paths, now()->addSeconds($window));
        }
    }

    /**
     * Check if the IP should be rate limited
     */
    protected function shouldRateLimit(string $ip): bool
    {
        $key = $this->requestCountPrefix . $ip;
        $threshold = config('request-logger.rate_limit_threshold', 50);
        $pathsKey = $this->requestCountPrefix . $ip . ':paths';

        // Check if request count exceeds threshold
        if (Cache::has($key) && Cache::get($key) > $threshold) {
            return true;
        }

        // Also check if accessing many different paths in short time (scanning behavior)
        if (Cache::has($pathsKey)) {
            $paths = Cache::get($pathsKey);
            if (count($paths) >= 12) { // If accessing 5+ different paths in short time
                return true;
            }
        }

        return false;
    }

    /**
     * Ban an IP for a specified duration
     */
    protected function banIp(string $ip, ?int $hours = null, ?bool $useMinutes = false): void
    {
        // Use default ban duration from config if not specified
        $hours = $hours ?? config('request-logger.ban_duration', 24);

        if ($useMinutes) {
            $hours = $hours / 60; // Convert to minutes
        }
        // Store the timestamp when the ban was applied
        $banData = [
            'banned_at' => now()->toIso8601String(),
            'expires_at' => now()->addHours($hours)->toIso8601String(),
            'reason' => 'Suspicious activity detected'
        ];

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
            Log::error("Failed to log banned IP to database: {$e->getMessage()}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if the request matches known attack patterns
     */
    protected function isAttackPattern(Request $request): bool
    {
        $path = $request->path();

        // Get suspicious patterns (with caching)
        if ($this->suspiciousPatterns === null) {
            $this->suspiciousPatterns = Cache::remember('ip_ban_patterns', $this->patternCacheTime, function () {
                return [
                    // Git config exposure attempts
                    '/.git/config',
                    '/.git/HEAD',
                    '/.git/index',
                    '/.git/objects',
                    '/.git/logs',

                    // Environment file probing
                    '/.env',
                    '/.env.local',
                    '/.env.dev',
                    '/.env.development',
                    '/.env.prod',
                    '/.env.production',
                    '/.env.testing',
                    '/.env.backup',
                    '/.env.example',
                    '/.env.save',
                    '/.env.bak',
                    '/.env.old',

                    // Common vulnerability scanners
                    '/wp-login.php',
                    '/wp-admin',
                    '/wp-content',
                    '/wp-includes',
                    '/phpMyAdmin',
                    '/phpmyadmin',
                    '/mysql',
                    '/myadmin',
                    '/admin/config',
                    '/admin/login',
                    '/admin/db',
                    '/administrator',
                    '/admin.php',
                    '/config.php',

                    // Common CMS paths
                    '/joomla',
                    '/drupal',
                    '/magento',
                    '/wordpress',

                    // Common exploitation paths
                    '/cgi-bin/',
                    '/vendor/',
                    '/composer.json',
                    '/composer.lock',
                    '/debug/default/view',
                    '/console/',
                    '/shell',
                    '/cmd',
                    '/config',
                    '/backup',
                    '/bak',
                    '/old',
                    '/temp',
                    '/tmp',

                    // Web shell attempts
                    '/shell.php',
                    '/cmd.php',
                    '/c99.php',
                    '/r57.php',
                    '/webshell.php',
                    '/backdoor.php',

                    // Log files
                    '/logs/',
                    '/log/',
                    '/.log',
                    '/error_log',
                    '/access_log',

                    // Known vulnerabilities
                    '/solr/',
                    '/jenkins/',
                    '/struts',
                    '/xmlrpc.php',
                    '/server-status',
                    '/webdav/',
                ];
            });
        }

        // Get suspicious extensions (with caching)
        if ($this->suspiciousExtensions === null) {
            $this->suspiciousExtensions = Cache::remember('ip_ban_extensions', $this->patternCacheTime, function () {
                return [
                    '.bak', '.backup', '.swp', '.old', '.save', '.~ ', '.env',
                    '.conf', '.config', '.cfg', '.ini', '.log', '.sql', '.sh', '.bash'
                ];
            });
        }

        // Check for suspicious paths (optimized to use strpos once per iteration)
        foreach ($this->suspiciousPatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }

        // Check for suspicious file extensions in the URL
        foreach ($this->suspiciousExtensions as $ext) {
            if (strpos($path, $ext) !== false) {
                return true;
            }
        }

        // Check for SQL injection patterns in query params
        $params = $request->query();
        if (!empty($params)) {
            foreach ($params as $param) {
                if (is_string($param) && $this->containsSqlInjection($param)) {
                    return true;
                }
            }
        }

        // Check for suspicious query parameters (faster check for common attack params)
        $suspiciousParams = ['cmd', 'exec', 'command', 'shell', 'passthru', 'eval', 'system'];
        foreach ($suspiciousParams as $param) {
            if ($request->has($param)) {
                return true;
            }
        }

        return false;
    }
}
