<?php

namespace MagicLog\RequestLogger\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MagicLog\RequestLogger\Models\RequestLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RequestLoggerController extends Controller
{
    /**
     * Advanced index view with grouped results
     */
    public function index(Request $request)
    {
        // Check if grouping is requested
        $groupBy = $request->input('group_by');

        // Apply filters using query builder
        $query = $this->applyFilters($request);

        // Cache statistics for 5 minutes with a unique key based on day and filters
        $filterKey = md5(json_encode($request->all()));
        $cacheKey = 'request_logger_stats_' . date('Y-m-d') . '_' . $filterKey;

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            return $this->getStats($request);
        });

        // Handle grouped results differently
        if ($groupBy) {
            $groupedLogs = $query->orderByDesc('count')->paginate(25);

            // Return view or JSON for AJAX
            if ($request->ajax() && !$request->wantsJson()) {
                return response()->json([
                    'logs' => $groupedLogs,
                    'stats' => $stats
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'logs' => $groupedLogs,
                    'stats' => $stats
                ]);
            }

            return view('request-logger::grouped', compact('groupedLogs', 'stats', 'groupBy'));
        }

        // Improved pagination with eager loading if any relationships are used
        $logs = $query->latest()->paginate(25);

        // Return view or JSON for AJAX
        if ($request->ajax() && !$request->wantsJson()) {
            return response()->json([
                'logs' => $logs,
                'stats' => $stats
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'logs' => $logs,
                'stats' => $stats
            ]);
        }

        return view('request-logger::dashboard', compact('logs', 'stats'));
    }

    /**
     * Apply enhanced filters to query
     */
    protected function applyFilters(Request $request)
    {
        // Check if grouping is requested
        $groupBy = $request->input('group_by');

        // For grouped queries, we need a completely different approach
        if ($groupBy) {
            // Start with a basic query without specific columns
            $query = RequestLog::query();

            // Apply all the filter conditions
            $this->applyFilterConditions($query, $request);

            // Now add the appropriate select and group by clauses based on grouping type
            switch ($groupBy) {
                case 'endpoint':
                    $query->select('path')
                        ->selectRaw('COUNT(*) as count')
                        ->selectRaw('AVG(response_time) as avg_time')
                        ->groupBy('path');
                    break;
                case 'method':
                    $query->select('method')
                        ->selectRaw('COUNT(*) as count')
                        ->selectRaw('AVG(response_time) as avg_time')
                        ->groupBy('method');
                    break;
                case 'status':
                    $query->select('response_status')
                        ->selectRaw('COUNT(*) as count')
                        ->selectRaw('AVG(response_time) as avg_time')
                        ->groupBy('response_status');
                    break;
                case 'ip':
                    $query->select('ip_address')
                        ->selectRaw('COUNT(*) as count')
                        ->selectRaw('AVG(response_time) as avg_time')
                        ->groupBy('ip_address');
                    break;
                case 'user':
                    $query->select('user_id')
                        ->selectRaw('COUNT(*) as count')
                        ->selectRaw('AVG(response_time) as avg_time')
                        ->whereNotNull('user_id')
                        ->groupBy('user_id');
                    break;
                default:
                    // Default to non-grouped query if invalid group selected
                    $query = $this->getNonGroupedQuery($request);
            }

            return $query;
        }

        // For non-grouped queries, use the standard approach
        return $this->getNonGroupedQuery($request);
    }

    /**
     * Get a non-grouped query with appropriate columns
     */
    protected function getNonGroupedQuery(Request $request)
    {
        // Base query with select to improve performance
        $query = RequestLog::select([
            'id', 'method', 'path', 'full_url', 'ip_address',
            'response_status', 'response_time', 'user_id', 'created_at'
        ]);

        // Apply filter conditions
        $this->applyFilterConditions($query, $request);

        return $query;
    }

    /**
     * Apply filter conditions without selecting columns
     */
    protected function applyFilterConditions($query, Request $request)
    {
        // Apply method filter
        if ($method = $request->input('method')) {
            $query->where('method', $method);
        }

        // Apply status filter
        if ($status = $request->input('status')) {
            if ($status === 'error') {
                // Show all error status codes (4xx and 5xx)
                $query->where('response_status', '>=', 400);
            } else {
                $query->where('response_status', $status);
            }
        }

        // Path filter
        if ($path = $request->input('path')) {
            $query->where('path', 'like', "%{$path}%");
        }

        // Date range filter
        if ($dateRange = $request->input('date_range')) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay()
                ]);
            }
        }

        // IP filter
        if ($ip = $request->input('ip_address')) {
            $query->where('ip_address', $ip);
        }

        // User filter
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // Response time filters
        if ($responseTime = $request->input('response_time')) {
            switch ($responseTime) {
                case 'fast':
                    $query->where('response_time', '<', 100);
                    break;
                case 'medium':
                    $query->whereBetween('response_time', [100, 500]);
                    break;
                case 'slow':
                    $query->whereBetween('response_time', [500, 1000]);
                    break;
                case 'very_slow':
                    $query->where('response_time', '>', 1000);
                    break;
            }
        } elseif ($request->has('slow')) {
            $query->where('response_time', '>', 1000);
        }

        // Has errors filter
        if ($request->has('has_errors')) {
            $query->where('response_status', '>=', 400);
        }

        return $query;
    }

    /**
     * Fetch detailed log information
     */
    public function show($id)
    {
        // Find the log with a more performant select
        $log = RequestLog::findOrFail($id);

        // Check if response_body is captured/available
        $responseBody = null;
        if (isset($log->response_body)) {
            $responseBody = $log->response_body;
        }

        // Return optimized response format
        return response()->json([
            'id' => $log->id,
            'method' => $log->method,
            'path' => $log->path,
            'full_url' => $log->full_url,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'headers' => $log->headers,
            'input_params' => $log->input_params,
            'response_status' => $log->response_status,
            'response_time' => $log->response_time,
            'response_body' => $responseBody,
            'user_id' => $log->user_id,
            'created_at' => $log->created_at->toDateTimeString()
        ]);
    }

    /**
     * Get dashboard statistics with new grouping options
     */
    public function stats(Request $request)
    {
        $groupBy = $request->input('group_by', null);

        // Use a specific cache key based on grouping
        $filterKey = md5(json_encode($request->all()));
        $cacheKey = 'request_logger_stats_' . date('Y-m-d') . '_' . $filterKey . '_' . $groupBy;

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request, $groupBy) {
            // Get base stats
            $baseStats = $this->getStats($request);

            // Add additional stats based on grouping
            if ($groupBy == 'time') {
                $baseStats['time_series'] = $this->getTimeSeriesStats($request);
            } elseif ($groupBy == 'endpoint') {
                $baseStats['endpoint_stats'] = $this->getEndpointStats($request);
            } elseif ($groupBy == 'method') {
                $baseStats['method_stats'] = $this->getMethodStats($request);
            } elseif ($groupBy == 'status') {
                $baseStats['status_stats'] = $this->getStatusStats($request);
            }

            return $baseStats;
        });

        return response()->json($stats);
    }

    /**
     * Get time series statistics
     */
    protected function getTimeSeriesStats(Request $request = null)
    {
        // Base query for filtered stats
        $query = RequestLog::query();

        // Apply filters if request is provided
        if ($request) {
            $query = $this->applyFilters($request);
        }

        // Determine the interval based on the date range
        $interval = $this->determineTimeInterval($request);

        // Format SQL based on the interval
        switch ($interval) {
            case 'hour':
                $timeSql = "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')";
                $timeFormat = 'Y-m-d H:i';
                $timeStep = '1 hour';
                $timeLabel = 'H:i';
                break;
            case 'day':
                $timeSql = "DATE(created_at)";
                $timeFormat = 'Y-m-d';
                $timeStep = '1 day';
                $timeLabel = 'M d';
                break;
            case 'week':
                $timeSql = "DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY))";
                $timeFormat = 'Y-m-d';
                $timeStep = '1 week';
                $timeLabel = 'M d';
                break;
            case 'month':
                $timeSql = "DATE_FORMAT(created_at, '%Y-%m-01')";
                $timeFormat = 'Y-m-d';
                $timeStep = '1 month';
                $timeLabel = 'M Y';
                break;
            default:
                $timeSql = "DATE(created_at)";
                $timeFormat = 'Y-m-d';
                $timeStep = '1 day';
                $timeLabel = 'M d';
        }

        // Get the time series data
        $timeSeriesData = $query->select(
            DB::raw("{$timeSql} as time_bucket"),
            DB::raw('COUNT(*) as request_count'),
            DB::raw('AVG(response_time) as avg_response_time'),
            DB::raw('MAX(response_time) as max_response_time'),
            DB::raw('MIN(response_time) as min_response_time')
        )
            ->groupBy('time_bucket')
            ->orderBy('time_bucket')
            ->get();

        // Format the results for the chart
        $formattedResults = $timeSeriesData->map(function ($item) use ($timeLabel) {
            $date = Carbon::parse($item->time_bucket);
            return [
                'time_bucket' => $item->time_bucket,
                'time_label' => $date->format($timeLabel),
                'request_count' => $item->request_count,
                'avg_response_time' => round($item->avg_response_time, 2),
                'max_response_time' => round($item->max_response_time, 2),
                'min_response_time' => round($item->min_response_time, 2)
            ];
        });

        return $formattedResults;
    }

    /**
     * Get statistics grouped by endpoint
     */
    protected function getEndpointStats(Request $request = null)
    {
        // Base query for filtered stats
        $query = RequestLog::query();

        // Apply filters if request is provided
        if ($request) {
            $query = $this->applyFilters($request);
        }

        $endpointStats = $query->select(
            'path',
            DB::raw('COUNT(*) as request_count'),
            DB::raw('AVG(response_time) as avg_response_time'),
            DB::raw('MAX(response_time) as max_response_time'),
            DB::raw('MIN(response_time) as min_response_time'),
            DB::raw('SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as error_count')
        )
            ->groupBy('path')
            ->orderBy('request_count', 'desc')
            ->get();

        // Format the results and calculate error rates
        $formattedResults = $endpointStats->map(function ($item) {
            return [
                'path' => $item->path,
                'request_count' => $item->request_count,
                'avg_response_time' => round($item->avg_response_time, 2),
                'max_response_time' => round($item->max_response_time, 2),
                'min_response_time' => round($item->min_response_time, 2),
                'error_count' => $item->error_count,
                'error_rate' => $item->request_count > 0 ? round(($item->error_count / $item->request_count) * 100, 2) : 0
            ];
        });

        return $formattedResults;
    }

    /**
     * Get statistics grouped by HTTP method
     */
    protected function getMethodStats(Request $request = null)
    {
        // Base query for filtered stats
        $query = RequestLog::query();

        // Apply filters if request is provided
        if ($request) {
            $query = $this->applyFilters($request);
        }

        $methodStats = $query->select(
            'method',
            DB::raw('COUNT(*) as request_count'),
            DB::raw('AVG(response_time) as avg_response_time'),
            DB::raw('SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as error_count')
        )
            ->groupBy('method')
            ->orderBy('method')
            ->get();

        // Format the results
        $formattedResults = $methodStats->map(function ($item) {
            return [
                'method' => $item->method,
                'request_count' => $item->request_count,
                'avg_response_time' => round($item->avg_response_time, 2),
                'error_count' => $item->error_count,
                'error_rate' => $item->request_count > 0 ? round(($item->error_count / $item->request_count) * 100, 2) : 0
            ];
        });

        return $formattedResults;
    }

    /**
     * Get statistics grouped by status code
     */
    protected function getStatusStats(Request $request = null)
    {
        // Base query for filtered stats
        $query = RequestLog::query();

        // Apply filters if request is provided
        if ($request) {
            $query = $this->applyFilters($request);
        }

        $statusStats = $query->select(
            'response_status',
            DB::raw('COUNT(*) as request_count'),
            DB::raw('AVG(response_time) as avg_response_time')
        )
            ->groupBy('response_status')
            ->orderBy('response_status')
            ->get();

        // Format the results
        $formattedResults = $statusStats->map(function ($item) {
            // Group status codes into categories
            $statusCategory = $this->getStatusCategory($item->response_status);

            return [
                'status' => $item->response_status,
                'status_category' => $statusCategory,
                'request_count' => $item->request_count,
                'avg_response_time' => round($item->avg_response_time, 2)
            ];
        });

        return $formattedResults;
    }

    /**
     * Determine the appropriate time interval based on the date range
     */
    protected function determineTimeInterval(Request $request = null)
    {
        if (!$request || !$request->input('date_range')) {
            // Default to daily for the last 7 days
            return 'day';
        }

        // Parse the date range
        $dateRange = $request->input('date_range');
        $dates = explode(' to ', $dateRange);

        if (count($dates) != 2) {
            return 'day';
        }

        $startDate = Carbon::parse($dates[0]);
        $endDate = Carbon::parse($dates[1]);
        $diffInDays = $endDate->diffInDays($startDate);

        // Determine interval based on date range
        if ($diffInDays <= 2) {
            return 'hour'; // Hourly for 1-2 days
        } elseif ($diffInDays <= 31) {
            return 'day';  // Daily for up to a month
        } elseif ($diffInDays <= 90) {
            return 'week'; // Weekly for up to 3 months
        } else {
            return 'month'; // Monthly for longer periods
        }
    }

    /**
     * Get status category from status code
     */
    protected function getStatusCategory($statusCode)
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return 'success';
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            return 'redirect';
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            return 'client-error';
        } elseif ($statusCode >= 500) {
            return 'server-error';
        } else {
            return 'unknown';
        }
    }

    /**
     * Generate aggregate statistics
     */
    protected function getStats(Request $request = null)
    {
        // Base query for filtered stats
        $query = RequestLog::query();

        // Apply filters if request is provided
        if ($request) {
            $query = $this->applyFilters($request);
        }

        // Use DB facade for more efficient aggregations
        return [
            'total_requests' => $query->count(),
            'requests_today' => $query->clone()->whereDate('created_at', today())->count(),
            'avg_response_time' => round($query->clone()->avg('response_time') ?? 0, 2),
            'method_distribution' => $query->clone()
                ->select('method', DB::raw('COUNT(*) as count'))
                ->groupBy('method')
                ->get(),
            'status_distribution' => $query->clone()
                ->select('response_status', DB::raw('COUNT(*) as count'))
                ->groupBy('response_status')
                ->get(),
            'slow_requests_count' => $query->clone()
                ->where('response_time', '>', 1000) // > 1 second
                ->count(),
            'error_count' => $query->clone()
                ->where('response_status', '>=', 400)
                ->count(),
        ];
    }

    /**
     * Purge old logs to maintain performance
     */
    public function purgeOldLogs(Request $request)
    {
        // Validate retention period
        $daysToKeep = $request->input('days', 30);
        $daysToKeep = max(1, min(365, intval($daysToKeep))); // Between 1 and 365

        // Calculate the cutoff date
        $cutoffDate = now()->subDays($daysToKeep)->format('Y-m-d H:i:s');

        // Log the cutoff date for debugging (optional)
        \Log::info("Purging logs older than: " . $cutoffDate);

        // Count records that will be deleted
        $recordsToDelete = RequestLog::where('created_at', '<', $cutoffDate)->count();

        // Use chunked deletion for large datasets to avoid memory issues
        $count = 0;
        RequestLog::where('created_at', '<', $cutoffDate)
            ->chunkById(1000, function ($logs) use (&$count) {
                foreach ($logs as $log) {
                    $log->delete();
                    $count++;
                }
            });

        // Clear all stats caches
        Cache::forget('request_logger_stats');
        Cache::forget('request_logger_daily_stats');
        Cache::forget('request_logger_weekly_stats');

        // Also try a more general flush if needed
        try {
            Cache::flush();
        } catch (\Exception $e) {
            \Log::warning("Could not flush entire cache: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} logs older than {$daysToKeep} days have been purged.",
            'details' => [
                'expected_count' => $recordsToDelete,
                'actual_count' => $count,
                'cutoff_date' => $cutoffDate
            ]
        ]);
    }

    /**
     * Export logs to CSV
     */
    protected function exportToCSV($logs)
    {
        $filename = 'request_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID', 'Method', 'Path', 'Full URL', 'IP Address',
                'User Agent', 'Response Status', 'Response Time (ms)',
                'Created At', 'Input Params', 'Headers'
            ]);

            // Write log rows
            $logs->each(function ($log) use ($file) {
                fputcsv($file, [
                    $log->id,
                    $log->method,
                    $log->path,
                    $log->full_url,
                    $log->ip_address,
                    $log->user_agent,
                    $log->response_status,
                    $log->response_time,
                    $log->created_at,
                    json_encode($log->input_params),
                    json_encode($log->headers)
                ]);
            });

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename"
        ]);
    }

    /**
     * Export logs to JSON
     */
    protected function exportToJSON($logs)
    {
        $filename = 'request_logs_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($logs)->withHeaders([
            'Content-Disposition' => "attachment; filename=$filename"
        ]);
    }

    /**
     * Export logs to various formats
     */
    public function export(Request $request)
    {
        // Validate export format
        $format = $request->input('format', 'csv');
        $validFormats = ['csv', 'json'];

        if (!in_array($format, $validFormats)) {
            return response()->json(['error' => 'Invalid export format'], 400);
        }

        // Apply the same filters as index
        $query = $this->applyFilters($request);

        // Limit export
        $limit = min($request->input('limit', 5000), 10000);
        $logs = $query->limit($limit)->get();

        // Export based on format
        switch ($format) {
            case 'csv':
                return $this->exportToCSV($logs);
            case 'json':
                return $this->exportToJSON($logs);
            default:
                return response()->json(['error' => 'Unsupported export format'], 400);
        }
    }
}
