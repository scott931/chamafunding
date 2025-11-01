<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'external_id',
        'last_four',
        'brand',
        'exp_month',
        'exp_year',
        'country',
        'is_default',
        'is_verified',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCard(): bool
    {
        return $this->type === 'card';
    }

    public function isBankAccount(): bool
    {
        return $this->type === 'bank_account';
    }

    public function isMobileMoney(): bool
    {
        return $this->type === 'mobile_money';
    }

    public function isExpired(): bool
    {
        if (!$this->isCard() || !$this->exp_year || !$this->exp_month) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::createFromDate($this->exp_year, $this->exp_month, 1)->endOfMonth();
        return $expiryDate->isPast();
    }

    public function getMaskedNumberAttribute(): string
    {
        if ($this->last_four) {
            return '**** **** **** ' . $this->last_four;
        }

        return '****';
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->isCard() && $this->brand) {
            return ucfirst($this->brand) . ' ' . $this->masked_number;
        }

        return ucfirst($this->type) . ' ' . $this->masked_number;
    }
}
