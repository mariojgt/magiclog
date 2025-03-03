<?php

namespace MagicLog\RequestLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BannedIp extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'banned_ips';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip_address',
        'reason',
        'banned_until',
        'attack_details',
        'request_count',
        'ban_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'banned_until' => 'datetime',
        'attack_details' => 'array',
        'request_count' => 'integer',
        'ban_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for currently active bans
     */
    public function scopeActive($query)
    {
        return $query->where('banned_until', '>', now());
    }

    /**
     * Scope for expired bans
     */
    public function scopeExpired($query)
    {
        return $query->where('banned_until', '<=', now());
    }

    /**
     * Scope for repeat offenders (banned multiple times)
     */
    public function scopeRepeatOffenders($query, int $minCount = 3)
    {
        return $query->where('ban_count', '>=', $minCount);
    }

    /**
     * Get related request logs for this IP
     */
    public function requestLogs()
    {
        return $this->hasMany(RequestLog::class, 'ip_address', 'ip_address');
    }

    /**
     * Ban an IP address
     */
    public static function banIp(string $ip, string $reason = 'Suspicious activity', int $hours = 24, array $details = [])
    {
        $bannedIp = self::firstOrNew(['ip_address' => $ip]);

        // If this IP has been banned before, increment the counter
        if ($bannedIp->exists) {
            $bannedIp->ban_count = ($bannedIp->ban_count ?? 0) + 1;
        } else {
            $bannedIp->ban_count = 1;
        }

        // Update ban details
        $bannedIp->reason = $reason;
        $bannedIp->banned_until = now()->addHours($hours);
        $bannedIp->attack_details = $details;

        $bannedIp->save();

        return $bannedIp;
    }

    /**
     * Check if an IP is currently banned
     */
    public static function isBanned(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->where('banned_until', '>', now())
            ->exists();
    }

    /**
     * Get details of all active bans
     */
    public static function getActiveBans()
    {
        return self::active()->get();
    }

    /**
     * Remove ban for an IP
     */
    public static function unbanIp(string $ip): bool
    {
        $ban = self::where('ip_address', $ip)->first();

        if ($ban) {
            $ban->banned_until = now()->subMinute(); // Set to the past
            $ban->save();
            return true;
        }

        return false;
    }
}
