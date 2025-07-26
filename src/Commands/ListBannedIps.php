<?php

namespace MagicLog\RequestLogger\Commands;

use Illuminate\Console\Command;
use MagicLog\RequestLogger\Models\BannedIp;

class ListBannedIps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-logger:banned-ips
                            {--all : Include expired bans}
                            {--repeat : Show only repeat offenders (banned multiple times)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all currently banned IP addresses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $showAll = $this->option('all');
        $showRepeat = $this->option('repeat');

        // Build query based on options
        $query = BannedIp::query();

        if (! $showAll) {
            $query->where('banned_until', '>', now());
        }

        if ($showRepeat) {
            $query->where('ban_count', '>', 1);
        }

        $bannedIps = $query->get();

        if ($bannedIps->isEmpty()) {
            $this->info('No banned IPs found.');

            return 0;
        }

        // Format data for table
        $tableData = $bannedIps->map(function ($ban) {
            return [
                'IP' => $ban->ip_address,
                'Reason' => $ban->reason,
                'Banned Until' => $ban->banned_until->format('Y-m-d H:i:s'),
                'Ban Count' => $ban->ban_count,
                'Status' => $ban->banned_until->isFuture() ? 'Active' : 'Expired',
            ];
        });

        // Display as table
        $this->table(
            ['IP Address', 'Reason', 'Banned Until', 'Ban Count', 'Status'],
            $tableData
        );

        // Summary
        $activeCount = $bannedIps->filter(function ($ban) {
            return $ban->banned_until->isFuture();
        })->count();

        $this->info('Total banned IPs: '.$bannedIps->count());
        $this->info('Active bans: '.$activeCount);

        return 0;
    }
}
