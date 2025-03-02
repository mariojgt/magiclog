<?php

namespace MagicLog\RequestLogger\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LogViewerController extends Controller
{
    /**
     * Show the log viewer dashboard
     */
    public function index(Request $request)
    {
        // Get list of log files with additional metadata
        $logFiles = $this->getLogFilesWithMetadata();

        // Get the selected log file (or use the latest by default)
        $selectedFile = $request->input('file', $logFiles[0]['name'] ?? null);

        // Parse log data from the selected file
        $logData = $selectedFile ? $this->parseLogFile($selectedFile) : [];

        // Get log stats for the dashboard
        $logStats = $this->getLogStats($logData);

        // Filter logs if needed
        $logData = $this->filterLogs($logData, $request);

        // Paginate results
        $perPage = 50;
        $page = $request->input('page', 1);
        $total = count($logData);

        // Simple manual pagination
        $logData = array_slice($logData, ($page - 1) * $perPage, $perPage);

        // Return view
        return view('request-logger::logs', [
            'logFiles' => $logFiles,
            'selectedFile' => $selectedFile,
            'logs' => $logData,
            'stats' => $logStats,
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => ceil($total / $perPage)
        ]);
    }

    /**
     * Get array of available log files with metadata
     */
    protected function getLogFilesWithMetadata()
    {
        // Get path to storage logs directory
        $logPath = storage_path('logs');

        // Get all .log files in the directory
        $files = File::glob($logPath . '/*.log');

        // Process each file to get metadata
        $fileData = [];
        foreach ($files as $file) {
            $fileName = basename($file);
            $size = File::size($file);
            $modified = File::lastModified($file);

            // Calculate file size in human-readable format
            $sizeFormatted = $this->formatFileSize($size);

            // Format last modified date
            $modifiedFormatted = Carbon::createFromTimestamp($modified)->diffForHumans();

            // Get basic file stats
            $lineCount = $this->countFileLines($file);
            $errorCount = $this->countErrorsInFile($file);

            // Check if this is today's log
            $isToday = str_contains($fileName, date('Y-m-d'));

            // Check if this is the Laravel default log
            $isDefault = ($fileName === 'laravel.log');

            // Add to files array
            $fileData[] = [
                'name' => $fileName,
                'path' => $file,
                'size' => $size,
                'size_formatted' => $sizeFormatted,
                'modified' => $modified,
                'modified_formatted' => $modifiedFormatted,
                'line_count' => $lineCount,
                'error_count' => $errorCount,
                'is_today' => $isToday,
                'is_default' => $isDefault
            ];
        }

        // Sort files by modified time (newest first)
        usort($fileData, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $fileData;
    }

    /**
     * Format file size to human-readable format
     */
    protected function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Count lines in a file
     */
    protected function countFileLines($file)
    {
        // For large files, we'll use a more efficient approach
        if (File::size($file) > 5 * 1024 * 1024) { // 5MB
            // Sample the first 500KB to estimate total lines
            $sample = File::get($file, 0, 500 * 1024);
            $sampleLines = substr_count($sample, PHP_EOL);
            $totalSize = File::size($file);
            $sampleSize = strlen($sample);

            // Estimate total lines based on sample
            return (int) ($sampleLines * ($totalSize / $sampleSize));
        }

        // For smaller files, count all lines
        return substr_count(File::get($file), PHP_EOL) + 1;
    }

    /**
     * Count errors in a file
     */
    protected function countErrorsInFile($file)
    {
        $content = File::get($file);
        return substr_count(strtolower($content), '.error:') +
               substr_count(strtolower($content), '.critical:') +
               substr_count(strtolower($content), '.alert:') +
               substr_count(strtolower($content), '.emergency:');
    }

    /**
     * Get statistics for log data
     */
    protected function getLogStats($logs)
    {
        $stats = [
            'total' => count($logs),
            'levels' => [
                'emergency' => 0,
                'alert' => 0,
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
                'notice' => 0,
                'info' => 0,
                'debug' => 0
            ],
            'today' => 0,
            'yesterday' => 0,
            'this_week' => 0
        ];

        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $weekStart = Carbon::now()->startOfWeek()->format('Y-m-d');

        foreach ($logs as $log) {
            // Count by level
            if (isset($stats['levels'][$log['level']])) {
                $stats['levels'][$log['level']]++;
            }

            // Count by date
            $logDate = substr($log['datetime'], 0, 10);
            if ($logDate === $today) {
                $stats['today']++;
            }
            if ($logDate === $yesterday) {
                $stats['yesterday']++;
            }
            if ($logDate >= $weekStart) {
                $stats['this_week']++;
            }
        }

        // Calculate percentages
        $stats['error_percentage'] = $stats['total'] > 0 ?
            round((($stats['levels']['emergency'] + $stats['levels']['alert'] +
                   $stats['levels']['critical'] + $stats['levels']['error']) / $stats['total']) * 100, 1) : 0;

        return $stats;
    }

    /**
     * Parse log file into structured data
     */
    protected function parseLogFile($fileName)
    {
        $logs = [];
        $filePath = storage_path('logs/' . $fileName);

        if (!File::exists($filePath)) {
            return $logs;
        }

        // Check if the file is too large for direct reading
        if (File::size($filePath) > 10 * 1024 * 1024) { // 10MB
            // For large files, read in chunks or limit to the latest entries
            $content = $this->readLargeFileTail($filePath, 2000); // Read the last ~2000 lines
        } else {
            // Read the whole file
            $content = File::get($filePath);
        }

        // Split by log entries (Laravel log entries start with [YYYY-MM-DD HH:MM:SS])
        $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?\]/';
        $entries = preg_split($pattern, $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        // Get datetime matches
        preg_match_all($pattern, $content, $dates);

        // Process each log entry
        foreach ($entries as $i => $entry) {
            // Skip if no datetime found for this entry
            if (!isset($dates[0][$i])) {
                continue;
            }

            // Parse log level
            $levelPattern = '/\.(emergency|alert|critical|error|warning|notice|info|debug)\:/i';
            preg_match($levelPattern, $entry, $levelMatch);
            $level = $levelMatch[1] ?? 'info';

            // Clean up the log entry
            $entry = trim($entry);

            // Extract stack trace if available
            $stackTrace = null;
            if (strpos($entry, 'Stack trace:') !== false) {
                $parts = explode('Stack trace:', $entry, 2);
                $entry = trim($parts[0]);
                $stackTrace = trim($parts[1]);
            }

            // Try to extract more context
            $context = $this->extractLogContext($entry);

            // Add log entry to results
            $logs[] = [
                'datetime' => trim($dates[0][$i], '[]'),
                'level' => strtolower($level),
                'message' => $entry,
                'stack_trace' => $stackTrace,
                'context' => $context
            ];
        }

        // Sort logs by datetime (newest first)
        usort($logs, function($a, $b) {
            return strtotime($b['datetime']) - strtotime($a['datetime']);
        });

        return $logs;
    }

    /**
     * Read the tail of a large file
     */
    protected function readLargeFileTail($filePath, $lines = 1000)
    {
        // Command to get the last X lines
        $command = "tail -n {$lines} " . escapeshellarg($filePath);

        // Execute the command
        $output = shell_exec($command);

        return $output ?: '';
    }

    /**
     * Extract context information from log message
     */
    protected function extractLogContext($message)
    {
        $context = [];

        // Try to extract JSON context
        if (preg_match('/\{.*\}/s', $message, $matches)) {
            $jsonStr = $matches[0];
            try {
                $jsonContext = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonContext)) {
                    $context = $jsonContext;
                }
            } catch (\Exception $e) {
                // Failed to parse JSON, ignore
            }
        }

        // Try to extract URL if available
        if (preg_match('/\b(https?:\/\/\S+)\b/i', $message, $matches)) {
            $context['url'] = $matches[1];
        }

        // Try to extract request ID if available
        if (preg_match('/Request ID: ([a-zA-Z0-9-]+)/', $message, $matches)) {
            $context['request_id'] = $matches[1];
        }

        return $context;
    }

    /**
     * Filter logs based on request parameters
     */
    protected function filterLogs($logs, Request $request)
    {
        // Filter by log level
        if ($level = $request->input('level')) {
            $logs = array_filter($logs, function($log) use ($level) {
                if ($level === 'error' && in_array($log['level'], ['emergency', 'alert', 'critical', 'error'])) {
                    return true;
                }
                return $log['level'] === $level;
            });
        }

        // Filter by search term
        if ($search = $request->input('search')) {
            $logs = array_filter($logs, function($log) use ($search) {
                return stripos($log['message'], $search) !== false ||
                       ($log['stack_trace'] && stripos($log['stack_trace'], $search) !== false);
            });
        }

        // Filter by date
        if ($date = $request->input('date')) {
            $logs = array_filter($logs, function($log) use ($date) {
                return strpos($log['datetime'], $date) === 0;
            });
        }

        return $logs;
    }

    /**
     * Clear a log file
     */
    public function clear(Request $request)
    {
        $file = $request->input('file');

        if ($file) {
            $filePath = storage_path('logs/' . $file);

            if (File::exists($filePath)) {
                // Write an empty string to the file to clear it
                File::put($filePath, '');

                return redirect()->route('request-logger.logs.index')
                    ->with('success', "Log file '{$file}' has been cleared.");
            }
        }

        return redirect()->route('request-logger.logs.index')
            ->with('error', 'Failed to clear log file.');
    }

    /**
     * Download a log file
     */
    public function download(Request $request)
    {
        $file = $request->input('file');

        if ($file) {
            $filePath = storage_path('logs/' . $file);

            if (File::exists($filePath)) {
                return response()->download($filePath);
            }
        }

        return redirect()->route('request-logger.logs.index')
            ->with('error', 'Log file not found.');
    }

    /**
     * Delete a log file
     */
    public function delete(Request $request)
    {
        $file = $request->input('file');

        if ($file) {
            $filePath = storage_path('logs/' . $file);

            if (File::exists($filePath)) {
                File::delete($filePath);

                return redirect()->route('request-logger.logs.index')
                    ->with('success', "Log file '{$file}' has been deleted.");
            }
        }

        return redirect()->route('request-logger.logs.index')
            ->with('error', 'Failed to delete log file.');
    }
}
