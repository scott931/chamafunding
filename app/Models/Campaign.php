<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'created_by',
        'goal_amount',
        'raised_amount',
        'currency',
        'deadline',
        'status',
        'starts_at',
        'ends_at',
        'featured_image',
        'images',
    ];

    protected $casts = [
        'deadline' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'goal_amount' => 'integer',
        'raised_amount' => 'integer',
        'images' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(CampaignContribution::class);
    }

    public function rewardTiers(): HasMany
    {
        return $this->hasMany(RewardTier::class)->orderBy('sort_order');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(CampaignUpdate::class)->orderBy('published_at', 'desc');
    }

    public function savedByUsers(): HasMany
    {
        return $this->hasMany(SavedCampaign::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->goal_amount == 0) {
            return 0;
        }

        return min(100, ($this->raised_amount / $this->goal_amount) * 100);
    }

    public function getFormattedGoalAmountAttribute(): string
    {
        return number_format($this->goal_amount / 100, 2);
    }

    public function getFormattedRaisedAmountAttribute(): string
    {
        return number_format($this->raised_amount / 100, 2);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->starts_at === null || $this->starts_at <= now()) &&
               ($this->ends_at === null || $this->ends_at >= now());
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'successful' ||
               ($this->status === 'active' && $this->raised_amount >= $this->goal_amount);
    }

    public function isExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at < now();
    }
}
