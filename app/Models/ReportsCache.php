<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReportsCache extends Model
{
    use HasFactory;

    protected $table = 'reports_cache';

    protected $fillable = [
        'report_type',
        'cache_key',
        'data',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function get(string $reportType, string $cacheKey, $default = null)
    {
        $cache = static::where('report_type', $reportType)
            ->where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();

        return $cache ? $cache->data : $default;
    }

    public static function put(string $reportType, string $cacheKey, $data, int $ttlMinutes = 60): void
    {
        static::updateOrCreate(
            [
                'report_type' => $reportType,
                'cache_key' => $cacheKey,
            ],
            [
                'data' => $data,
                'generated_at' => now(),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]
        );
    }

    public static function forget(string $reportType, string $cacheKey = null): void
    {
        $query = static::where('report_type', $reportType);

        if ($cacheKey) {
            $query->where('cache_key', $cacheKey);
        }

        $query->delete();
    }

    public static function clearExpired(): void
    {
        static::where('expires_at', '<', now())->delete();
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public function getTtlAttribute(): int
    {
        return $this->expires_at->diffInMinutes(now());
    }
}
