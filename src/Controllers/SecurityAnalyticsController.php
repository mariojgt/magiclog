<?php

namespace MagicLog\RequestLogger\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use MagicLog\RequestLogger\Models\BannedIp;
use MagicLog\RequestLogger\Models\RequestLog;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SecurityAnalyticsController extends Controller
{
    /**
     * Display the security analytics dashboard
     */
    public function index(Request $request)
    {
        // Time range for analytics
        $timeRange = $request->input('time_range', 'week');

        // Calculate start date based on time range
        $startDate = $this->getStartDate($timeRange);

        // Gather all analytics data
        $data = [
            'time_range' => $timeRange,
            'summary' => $this->getSummaryStats($startDate),
            'attack_trends' => $this->getAttackTrends($startDate),
            'top_attack_paths' => $this->getTopAttackPaths($startDate),
            'top_banned_ips' => $this->getTopBannedIps($startDate),
            'attack_heatmap' => $this->getAttackHeatmap($startDate),
            'geographic_data' => $this->getGeographicData($startDate),
            'recent_attacks' => $this->getRecentAttacks(),
        ];

        return view('request-logger::security.analytics', $data);
    }

    /**
     * Get the start date based on the time range selection
     */
    protected function getStartDate($timeRange)
    {
        return match($timeRange) {
            'day' => Carbon::now()->subDay(),
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->subQuarter(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subWeek(),
        };
    }

    /**
     * Get summary statistics
     */
    protected function getSummaryStats($startDate)
    {
        // Count total banned IPs (active)
        $activeBans = BannedIp::where('banned_until', '>', now())->count();

        // Count new bans since start date
        $newBans = BannedIp::where('created_at', '>=', $startDate)->count();

        // Count total attacks detected since start date
        $attacksDetected = RequestLog::where('created_at', '>=', $startDate)
            ->whereIn('ip_address', function($query) {
                $query->select('ip_address')->from('banned_ips');
            })
            ->count();

        // Count repeat offenders (IPs banned more than once)
        $repeatOffenders = BannedIp::where('ban_count', '>', 1)->count();

        // Get top attack methods
        $topMethods = RequestLog::where('created_at', '>=', $startDate)
            ->whereIn('ip_address', function($query) {
                $query->select('ip_address')->from('banned_ips');
            })
            ->select('method', DB::raw('count(*) as count'))
            ->groupBy('method')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get();

        // Success rate (% of attacks blocked)
        $successRate = 100; // Placeholder - would need data on successful vs. unsuccessful attacks

        return [
            'active_bans' => $activeBans,
            'new_bans' => $newBans,
            'attacks_detected' => $attacksDetected,
            'repeat_offenders' => $repeatOffenders,
            'top_methods' => $topMethods,
            'success_rate' => $successRate,
        ];
    }

    /**
     * Get attack trend data (attacks over time)
     */
    protected function getAttackTrends($startDate)
    {
        // Determine group format based on date range
        $dateFormat = $this->getDateFormat($startDate);
        $groupByFormat = $this->getGroupByFormat($startDate);

        // Get attacks per time period
        $attackData = RequestLog::where('created_at', '>=', $startDate)
            ->whereIn('ip_address', function($query) {
                $query->select('ip_address')->from('banned_ips');
            })
            ->select(DB::raw("DATE_FORMAT(created_at, '{$groupByFormat}') as date"), DB::raw('count(*) as attacks'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        // Get new bans per time period
        $banData = BannedIp::where('created_at', '>=', $startDate)
            ->select(DB::raw("DATE_FORMAT(created_at, '{$groupByFormat}') as date"), DB::raw('count(*) as bans'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        // Generate all periods in date range
        $periods = [];
        $period = CarbonPeriod::create($startDate, $this->getPeriodInterval($startDate), now());

        foreach ($period as $date) {
            $formattedDate = $date->format($dateFormat);

            $periods[] = [
                'date' => $formattedDate,
                'attacks' => $attackData[$formattedDate]['attacks'] ?? 0,
                'bans' => $banData[$formattedDate]['bans'] ?? 0,
            ];
        }

        return $periods;
    }

    /**
     * Get top attack paths (endpoints most frequently targeted)
     */
    protected function getTopAttackPaths($startDate)
    {
        return RequestLog::where('created_at', '>=', $startDate)
            ->whereIn('ip_address', function($query) {
                $query->select('ip_address')->from('banned_ips');
            })
            ->select('path', DB::raw('count(*) as count'))
            ->groupBy('path')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get top banned IPs (most frequent offenders)
     */
    protected function getTopBannedIps($startDate)
    {
        return BannedIp::where('created_at', '>=', $startDate)
            ->select('ip_address', 'ban_count', 'reason', 'banned_until')
            ->orderBy('ban_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get attack heatmap data (attacks by hour and day of week)
     */
    protected function getAttackHeatmap($startDate)
    {
        // Initialize empty heatmap data structure
        $heatmap = [];

        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $heatmap[$day][$hour] = 0;
            }
        }

        // Get attack counts by day of week and hour
        $attackData = RequestLog::where('created_at', '>=', $startDate)
            ->whereIn('ip_address', function($query) {
                $query->select('ip_address')->from('banned_ips');
            })
            ->select(
                DB::raw('DAYOFWEEK(created_at) - 1 as day_of_week'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->groupBy('day_of_week', 'hour')
            ->get();

        // Fill in the heatmap
        foreach ($attackData as $item) {
            $heatmap[$item->day_of_week][$item->hour] = $item->count;
        }

        return $heatmap;
    }

    /**
     * Get geographic data (attacks by country)
     */
    protected function getGeographicData($startDate)
    {
        // This would require GeoIP integration
        // Placeholder: randomly assign countries with counts
        $countries = ['US', 'CN', 'RU', 'IN', 'BR', 'GB', 'DE', 'FR', 'JP', 'KR'];
        $data = [];

        foreach ($countries as $country) {
            $data[] = [
                'country' => $country,
                'count' => rand(10, 100),
            ];
        }

        usort($data, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $data;
    }

    /**
     * Get recent attacks (last few banned requests)
     */
    protected function getRecentAttacks()
    {
        return RequestLog::whereIn('ip_address', function($query) {
                $query->select('ip_address')->from('banned_ips');
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();
    }

    /**
     * Get period interval based on date range
     */
    protected function getPeriodInterval($startDate)
    {
        $daysAgo = Carbon::now()->diffInDays($startDate);

        return match(true) {
            $daysAgo <= 2 => '1 hour',
            $daysAgo <= 14 => '1 day',
            $daysAgo <= 90 => '1 week',
            default => '1 month',
        };
    }

    /**
     * Get date format for display based on date range
     */
    protected function getDateFormat($startDate)
    {
        $daysAgo = Carbon::now()->diffInDays($startDate);

        return match(true) {
            $daysAgo <= 2 => 'Y-m-d H:00',
            $daysAgo <= 31 => 'Y-m-d',
            $daysAgo <= 365 => 'Y-m',
            default => 'Y-m',
        };
    }

    /**
     * Get SQL date format for grouping based on date range
     */
    protected function getGroupByFormat($startDate)
    {
        $daysAgo = Carbon::now()->diffInDays($startDate);

        return match(true) {
            $daysAgo <= 2 => '%Y-%m-%d %H:00',
            $daysAgo <= 31 => '%Y-%m-%d',
            $daysAgo <= 365 => '%Y-%m',
            default => '%Y-%m',
        };
    }
}
