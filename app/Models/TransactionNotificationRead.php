<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionNotificationRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public $timestamps = true;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Mark a campaign notification as read for a user
     */
    public static function markAsRead(int $userId, int $campaignId): self
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'campaign_id' => $campaignId,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    /**
     * Check if a campaign notification is read for a user
     */
    public static function isRead(int $userId, int $campaignId): bool
    {
        return self::where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->exists();
    }

    /**
     * Get all read campaign IDs for a user
     */
    public static function getReadCampaignIds(int $userId): array
    {
        return self::where('user_id', $userId)
            ->pluck('campaign_id')
            ->toArray();
    }
}

