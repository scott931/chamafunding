<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'minimum_amount',
        'maximum_amount',
        'estimated_delivery_month',
        'estimated_delivery_year',
        'reward_type',
        'requires_shipping',
        'is_limited',
        'quantity_limit',
        'quantity_claimed',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'minimum_amount' => 'integer',
        'maximum_amount' => 'integer',
        'estimated_delivery_month' => 'integer',
        'estimated_delivery_year' => 'integer',
        'quantity_limit' => 'integer',
        'quantity_claimed' => 'integer',
        'sort_order' => 'integer',
        'requires_shipping' => 'boolean',
        'is_limited' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(CampaignContribution::class);
    }

    public function getFormattedMinimumAmountAttribute(): string
    {
        return number_format($this->minimum_amount / 100, 2);
    }

    public function getFormattedMaximumAmountAttribute(): string
    {
        if (!$this->maximum_amount) {
            return null;
        }
        return number_format($this->maximum_amount / 100, 2);
    }

    public function getEstimatedDeliveryDateAttribute(): ?string
    {
        if ($this->estimated_delivery_month && $this->estimated_delivery_year) {
            $monthName = date('M', mktime(0, 0, 0, $this->estimated_delivery_month, 1));
            return $monthName . ' ' . $this->estimated_delivery_year;
        }
        return null;
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->is_limited && $this->quantity_limit) {
            return $this->quantity_claimed < $this->quantity_limit;
        }

        return true;
    }

    public function getRemainingQuantityAttribute(): ?int
    {
        if (!$this->is_limited || !$this->quantity_limit) {
            return null;
        }

        return max(0, $this->quantity_limit - $this->quantity_claimed);
    }
}

