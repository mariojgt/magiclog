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
     * Show the request logger dashboard
     */
    public function index(Request $request)
    {
        // Apply filters using query builder
        $query = $this->applyFilters($request);

        // Cache statistics for 5 minutes with a unique key based on day and filters
        $filterKey = md5(json_encode($request->only(['method', 'status', 'path', 'date_range'])));
        $cacheKey = 'request_logger_stats_' . date('Y-m-d') . '_' . $filterKey;

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            return $this->getStats($request);
        });

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
     * Apply filters to query
     */
    protected function applyFilters(Request $request)
    {
        // Base query with select to improve performance
        $query = RequestLog::select([
            'id', 'method', 'path', 'full_url', 'ip_address',
            'response_status', 'response_time', 'user_id', 'created_at'
        ]);

        // Apply method filter
        if ($method = $request->input('method')) {
            $query->method($method);
        }

        // Apply status filter
        if ($status = $request->input('status')) {
            if ($status === 'error') {
                // Show all error status codes (4xx and 5xx)
                $query->errors();
            } else {
                $query->status($status);
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

        // Response time filter
        if ($request->has('slow')) {
            $query->slow();
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
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $filterKey = md5(json_encode($request->all()));
        $cacheKey = 'request_logger_stats_' . date('Y-m-d') . '_' . $filterKey;

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            return $this->getStats($request);
        });

        return response()->json($stats);
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
