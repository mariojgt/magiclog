<?php

namespace MagicLog\RequestLogger\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class RequestLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'request_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'method',
        'path',
        'full_url',
        'ip_address',
        'user_agent',
        'headers',
        'input_params',
        'response_status',
        'response_time',
        'response_body',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'response_time' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the decrypted headers.
     *
     * @return array
     */
    public function getHeadersAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        try {
            return json_decode(Crypt::decrypt($value), true) ?? [];
        } catch (\Exception $e) {
            // Handle legacy non-encrypted data
            return json_decode($value, true) ?? [];
        }
    }

    /**
     * Get the decrypted input parameters.
     *
     * @return array
     */
    public function getInputParamsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        try {
            return json_decode(Crypt::decrypt($value), true) ?? [];
        } catch (\Exception $e) {
            // Handle legacy non-encrypted data
            return json_decode($value, true) ?? [];
        }
    }

    /**
     * Get the decrypted response body.
     *
     * @return mixed
     */
    public function getResponseBodyAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return json_decode(Crypt::decrypt($value), true);
        } catch (\Exception $e) {
            // Handle legacy non-encrypted data
            return json_decode($value, true);
        }
    }

    /**
     * Optional: Relationship with user model if needed
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Scope a query to filter by method
     */
    public function scopeMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope a query to filter by status code
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('response_status', $status);
    }

    /**
     * Scope to find slow requests
     */
    public function scopeSlow($query, $threshold = 1000)
    {
        return $query->where('response_time', '>', $threshold);
    }

    /**
     * Scope to find requests with errors
     */
    public function scopeErrors($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Get the response category (success, redirect, client error, server error)
     */
    public function getResponseCategoryAttribute()
    {
        $status = $this->response_status;

        if ($status >= 200 && $status < 300) {
            return 'success';
        }
        if ($status >= 300 && $status < 400) {
            return 'redirect';
        }
        if ($status >= 400 && $status < 500) {
            return 'client-error';
        }
        if ($status >= 500) {
            return 'server-error';
        }

        return 'unknown';
    }
}
