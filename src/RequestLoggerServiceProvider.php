<?php
namespace MagicLog\RequestLogger;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use MagicLog\RequestLogger\Middleware\RequestLoggerMiddleware;
use Illuminate\Contracts\Http\Kernel;

class RequestLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router, Kernel $kernel)
    {
        // Load package configurations
        $this->loadConfigurations();

        // Load package routes
        $this->loadRoutes();

        // Load package views
        $this->loadViews();

        // Load package migrations
        $this->loadMigrations();

        // Register middleware
        $router->aliasMiddleware('request.logger', RequestLoggerMiddleware::class);

        // Register base layout
        $this->registerBaseLayout();

        // Register global middleware
        $this->registerGlobalMiddleware($kernel);
    }

    /**
     * Register global middleware
     */
    protected function registerGlobalMiddleware(Kernel $kernel)
    {
        // Add middleware to global middleware stack
        $kernel->pushMiddleware(RequestLoggerMiddleware::class);
    }

    /**
     * Load package configurations
     */
    protected function loadConfigurations()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/request-logger.php',
            'request-logger'
        );
    }

    /**
     * Load package routes
     */
    protected function loadRoutes()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    /**
     * Load package views
     */
    protected function loadViews()
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'request-logger');
    }

    /**
     * Load package migrations
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }

    /**
     * Register base layout
     */
    protected function registerBaseLayout()
    {
        // Create a base layout if it doesn't exist in the main application
        if (!View::exists('layouts.app')) {
            View::addLocation(__DIR__.'/Resources/views');
        }
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Optional additional registrations
    }
}
