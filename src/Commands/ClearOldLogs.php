<?php

namespace MagicLog\RequestLogger\Commands;

use Illuminate\Console\Command;
use MagicLog\RequestLogger\Models\RequestLog;
use MagicLog\RequestLogger\Models\BannedIp;

class ClearOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-logger:clear-old-logs
                            {--days=30 : Number of days to keep logs for}
                            {--bans : Also clear expired IP bans}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old request logs from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $clearBans = $this->option('bans');

        if ($days < 1) {
            $this->error('Days must be at least 1');
            return 1;
        }

        // Calculate cutoff date
        $cutoffDate = now()->subDays($days);

        // Delete old logs
        $count = RequestLog::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$count} request logs older than {$days} days.");

        // Also clear expired IP bans if requested
        if ($clearBans) {
            $banCount = BannedIp::where('banned_until', '<', now())->delete();
            $this->info("Deleted {$banCount} expired IP bans.");
        }

        return 0;
    }
}
