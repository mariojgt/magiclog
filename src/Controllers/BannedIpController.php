<?php

namespace MagicLog\RequestLogger\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use MagicLog\RequestLogger\Models\BannedIp;

class BannedIpController extends Controller
{
    /**
     * Display a listing of banned IPs.
     */
    public function index(Request $request)
    {
        // Apply filters
        $query = BannedIp::query();

        if ($request->has('status') && $request->status === 'active') {
            $query->where('banned_until', '>', now());
        } elseif ($request->has('status') && $request->status === 'expired') {
            $query->where('banned_until', '<=', now());
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('ip_address', 'like', "%{$search}%")
                ->orWhere('reason', 'like', "%{$search}%");
        }

        // Get stats for displaying in view
        $bannedStats = [
            'total' => BannedIp::count(),
            'active' => BannedIp::where('banned_until', '>', now())->count(),
            'repeat' => BannedIp::where('ban_count', '>', 1)->count(),
        ];

        // Paginate results
        $bannedIps = $query->orderBy('banned_until', 'desc')
            ->paginate(15);

        return view('request-logger::bannedIps.index', [
            'bannedIps' => $bannedIps,
            'bannedStats' => $bannedStats,
        ]);
    }

    /**
     * Store a newly created banned IP.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip',
            'reason' => 'nullable|string|max:255',
            'duration' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ip = $request->input('ip_address');
        $reason = $request->input('reason', 'Manually banned');
        $hours = $request->input('duration', 24);

        // Add to database
        $bannedIp = BannedIp::banIp($ip, $reason, $hours);

        // Add to cache
        Cache::put('ip_ban:'.$ip, [
            'banned_at' => now()->toIso8601String(),
            'expires_at' => now()->addHours($hours)->toIso8601String(),
            'reason' => $reason,
        ], now()->addHours($hours));

        return response()->json([
            'success' => true,
            'message' => "IP {$ip} has been banned successfully",
            'bannedIp' => $bannedIp,
        ]);
    }

    /**
     * Remove the specified banned IP.
     */
    public function destroy($ip)
    {
        $success = BannedIp::unbanIp($ip);

        // Also clear from cache
        Cache::forget('ip_ban:'.$ip);

        return response()->json([
            'success' => $success,
            'message' => $success
                ? "IP {$ip} has been unbanned successfully"
                : "IP {$ip} was not found or already unbanned",
        ]);
    }

    /**
     * API endpoint to list banned IPs
     */
    public function list()
    {
        return response()->json([
            'active' => BannedIp::where('banned_until', '>', now())->get(),
            'total' => BannedIp::count(),
            'active_count' => BannedIp::where('banned_until', '>', now())->count(),
            'repeat_offenders' => BannedIp::where('ban_count', '>', 1)->count(),
        ]);
    }

    /**
     * API endpoint to unban an IP
     */
    public function unban(Request $request)
    {
        $ip = $request->input('ip');

        if (! $ip) {
            return response()->json(['success' => false, 'message' => 'IP is required'], 400);
        }

        $success = BannedIp::unbanIp($ip);

        if ($success) {
            // Also clear from cache
            Cache::forget('ip_ban:'.$ip);

            return response()->json([
                'success' => true,
                'message' => "IP {$ip} has been unbanned successfully",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "IP {$ip} was not found or already unbanned",
        ]);
    }
}
