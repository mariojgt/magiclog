<?php

namespace MagicLog\RequestLogger\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use MagicLog\RequestLogger\Models\BannedIp;

class UnbanIp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-logger:unban-ip
                            {ip : The IP address to unban}
                            {--force : Force unban even if not found in database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unban an IP address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ip = $this->argument('ip');
        $force = $this->option('force');

        // Clear from cache first (for immediate effect)
        $banCacheKey = 'ip_ban:'.$ip;
        Cache::forget($banCacheKey);

        // Also clear request count to prevent immediate re-ban
        $requestCountKey = 'ip_requests:'.$ip;
        Cache::forget($requestCountKey);
        $pathsKey = 'ip_requests:'.$ip.':paths';
        Cache::forget($pathsKey);

        // Update database record
        $banned = BannedIp::where('ip_address', $ip)->first();

        if ($banned) {
            // Set expiry time to now (unbanned)
            $banned->banned_until = now();
            $banned->save();

            $this->info("IP address {$ip} has been unbanned successfully.");

            return 0;
        } elseif ($force) {
            $this->info("IP address {$ip} was not found in the database but cache has been cleared.");

            return 0;
        } else {
            $this->warn("IP address {$ip} was not found in the banned list.");

            return 1;
        }
    }
}
