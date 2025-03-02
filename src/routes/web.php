<?php

use Illuminate\Support\Facades\Route;
use MagicLog\RequestLogger\Controllers\LogViewerController;
use MagicLog\RequestLogger\Controllers\RequestLoggerController;

Route::middleware(['web', config('request-logger.auth_guard')])
    ->prefix('request-logger')
    ->name('request-logger.')
    ->group(function () {
        // Dashboard route
        Route::get('/', [RequestLoggerController::class, 'index'])
            ->name('index');

        // Detailed log view
        Route::get('/log/{id}', [RequestLoggerController::class, 'show'])
            ->name('show');

        // Export logs
        Route::get('/export', [RequestLoggerController::class, 'export'])
            ->name('export');

        // Stats endpoint
        Route::get('/stats', [RequestLoggerController::class, 'stats'])
            ->name('stats');

        // Purge logs endpoint
        Route::post('/purge-logs', [RequestLoggerController::class, 'purgeOldLogs'])
            ->name('purge');

        // Purge logs endpoint
        Route::post('/purge-logs', [RequestLoggerController::class, 'purgeOldLogs'])
            ->name('purge');

        // Laravel Log Viewer routes
        Route::get('/logs', [LogViewerController::class, 'index'])
            ->name('logs.index');

        Route::post('/logs/clear', [LogViewerController::class, 'clear'])
            ->name('logs.clear');

        Route::get('/logs/download', [LogViewerController::class, 'download'])
            ->name('logs.download');

        Route::post('/logs/delete', [LogViewerController::class, 'delete'])
            ->name('logs.delete');
    });
