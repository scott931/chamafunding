<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'contribution_id',
        'reward_tier_id',
        'shipping_name',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_postal_code',
        'shipping_phone',
        'survey_responses',
        'survey_completed',
        'survey_completed_at',
        'tracking_number',
        'tracking_carrier',
        'delivery_status',
        'shipped_at',
        'delivered_at',
        'digital_rewards',
        'metadata',
    ];

    protected $casts = [
        'survey_responses' => 'array',
        'survey_completed' => 'boolean',
        'survey_completed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'digital_rewards' => 'array',
        'metadata' => 'array',
    ];

    public function contribution(): BelongsTo
    {
        return $this->belongsTo(CampaignContribution::class);
    }

    public function rewardTier(): BelongsTo
    {
        return $this->belongsTo(RewardTier::class);
    }

    public function getFullShippingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->shipping_address,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_postal_code,
            $this->shipping_country,
        ]);

        return implode(', ', $parts);
    }

    public function hasShippingAddress(): bool
    {
        return !empty($this->shipping_address);
    }

    public function needsShippingAddress(): bool
    {
        return $this->rewardTier && $this->rewardTier->requires_shipping;
    }
}

