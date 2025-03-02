<?php

namespace MagicLog\RequestLogger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use MagicLog\RequestLogger\Models\RequestLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class RequestLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Quick path check first before other checks
        if ($this->isIgnoredPath($request)) {
            return $next($request);
        }

        // Start timing the request
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        // Determine if we should capture the response body
        $captureResponseBody = config('request-logger.capture_response_body', false);

        // Log the request
        if (config('request-logger.async_logging', true) && class_exists('\Illuminate\Contracts\Queue\ShouldQueue')) {
            // Use queue for async logging if available
            dispatch(function () use ($request, $response, $startTime, $captureResponseBody) {
                $this->logRequest($request, $response, $startTime, $captureResponseBody);
            })->afterResponse();
        } else {
            $this->logRequest($request, $response, $startTime, $captureResponseBody);
        }

        return $response;
    }

    /**
     * Quick check for ignored paths
     */
    protected function isIgnoredPath(Request $request): bool
    {
        // Check global configuration first
        if (!config('request-logger.enabled', true)) {
            return true;
        }

        // Skip logging for assets, images and static files
        $staticPatterns = [
            '/_debugbar/',
            '/js/',
            '/css/',
            '/images/',
            '/fonts/',
            '/favicon.ico',
            'request-logger',
            'request-logger/stats',
            'request-logger/logs'
        ];
        $path = $request->path();
        foreach ($staticPatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        // Skip logging for ignored paths
        $ignoredPaths = config('request-logger.ignored_paths', [
            'telescope*',
            'horizon*',
            'debugbar*',
            'admin/telescope*',
            '_ignition*',
            'livewire*',
        ]);

        foreach ($ignoredPaths as $ignoredPath) {
            if ($request->is($ignoredPath)) {
                return true;
            }
        }

        // Skip logging for certain request types
        $ignoredMethods = config('request-logger.ignored_methods', []);
        if (in_array($request->method(), $ignoredMethods)) {
            return true;
        }

        // Skip logging for specific routes
        $ignoredRoutes = config('request-logger.ignored_routes', []);
        $currentRouteName = Route::currentRouteName();
        if ($currentRouteName && in_array($currentRouteName, $ignoredRoutes)) {
            return true;
        }

        return false;
    }

    /**
     * Log the request details
     */
    protected function logRequest(Request $request, Response $response, float $startTime, bool $captureResponseBody = false): void
    {
        try {
            // Calculate response time
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            // Prepare headers for logging
            $headers = $this->prepareHeaders($request->headers->all());

            // Prepare input parameters (with sensitive data filtered)
            $inputParams = $this->prepareInputParams($request->except(
                config('request-logger.hidden_parameters', [
                    'password',
                    'password_confirmation',
                    'credit_card',
                    'token'
                ])
            ));

            // Prepare response body (if enabled)
            $responseBody = null;
            if ($captureResponseBody) {
                $responseBody = $this->captureResponseBody($response);
            }

            // Encrypt sensitive data before storing
            $encryptedInputParams = Crypt::encrypt(json_encode($inputParams));
            $encryptedHeaders = Crypt::encrypt(json_encode($headers));
            $encryptedResponseBody = $responseBody ? Crypt::encrypt(json_encode($responseBody)) : null;

            // Create log entry
            RequestLog::create([
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $encryptedHeaders,
                'input_params' => $encryptedInputParams,
                'response_status' => $response->getStatusCode(),
                'response_time' => $responseTime,
                'response_body' => $encryptedResponseBody,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't disrupt the application
            Log::error('Request logging failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Prepare headers for logging
     */
    protected function prepareHeaders(array $headers): array
    {
        // Remove sensitive headers
        $sensitiveHeaders = config('request-logger.hidden_headers', [
            'authorization',
            'cookie',
            'set-cookie'
        ]);

        return collect($headers)
            ->filter(function ($value, $key) use ($sensitiveHeaders) {
                return !in_array(strtolower($key), $sensitiveHeaders);
            })
            ->toArray();
    }

    /**
     * Prepare input parameters for logging
     */
    protected function prepareInputParams(array $params): array
    {
        // Truncate long parameter values
        return collect($params)
            ->map(function ($value) {
                // Handle arrays and objects recursively
                if (is_array($value)) {
                    return $this->prepareInputParams($value);
                }

                // Limit string length
                if (is_string($value)) {
                    return strlen($value) > 500 ? substr($value, 0, 500) . '...[truncated]' : $value;
                }

                return $value;
            })
            ->toArray();
    }

    /**
     * Capture response body for logging
     */
    protected function captureResponseBody(Response $response)
    {
        // Skip for file downloads and large responses
        if ($this->isFileResponse($response) || $response->headers->has('Content-Disposition')) {
            return '[File Download]';
        }

        // Get response content
        $content = $response->getContent();

        // Skip if content is too large (> 100KB)
        if (strlen($content) > 100 * 1024) {
            return '[Response too large to capture]';
        }

        // Check content type
        $contentType = $response->headers->get('Content-Type');

        // Handle JSON responses
        if (strpos($contentType, 'application/json') !== false) {
            try {
                $json = json_decode($content, true);

                // Filter sensitive data from JSON
                return $this->filterSensitiveData($json);
            } catch (\Exception $e) {
                return '[Invalid JSON]';
            }
        }

        // Handle HTML responses
        if (strpos($contentType, 'text/html') !== false) {
            // Only store the first 1000 characters of HTML responses
            return '[HTML Content] ' . substr(strip_tags($content), 0, 1000) . '...';
        }

        // Other response types
        return '[' . ($contentType ?? 'Unknown Content Type') . ']';
    }

    /**
     * Check if response is a file download
     */
    protected function isFileResponse(Response $response): bool
    {
        // Check common file content types
        $contentType = $response->headers->get('Content-Type');

        $fileTypes = [
            'application/pdf',
            'application/zip',
            'application/octet-stream',
            'image/',
            'audio/',
            'video/',
            'application/vnd.ms-',
            'application/vnd.openxmlformats-'
        ];

        foreach ($fileTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter sensitive data from response
     */
    protected function filterSensitiveData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitiveKeys = config('request-logger.hidden_response_fields', [
            'password',
            'token',
            'secret',
            'key',
            'credit_card',
            'card',
            'ssn',
            'social_security',
            'auth',
            'authentication'
        ]);

        foreach ($data as $key => $value) {
            // Check for sensitive keys
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $data[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            // Recursively check nested arrays
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            }
        }

        return $data;
    }
}
