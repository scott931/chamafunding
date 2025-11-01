<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_number',
        'account_type',
        'balance',
        'interest_rate',
        'currency',
        'status',
        'maturity_date',
        'minimum_balance',
        'maximum_balance',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'minimum_balance' => 'decimal:2',
        'maximum_balance' => 'decimal:2',
        'maturity_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isMatured(): bool
    {
        return $this->maturity_date !== null && $this->maturity_date <= now();
    }

    public function canWithdraw(float $amount): bool
    {
        return $this->isActive() &&
               $this->balance >= $amount &&
               ($this->balance - $amount) >= $this->minimum_balance;
    }

    public function canDeposit(float $amount): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->maximum_balance !== null) {
            return ($this->balance + $amount) <= $this->maximum_balance;
        }

        return true;
    }

    public function calculateInterest(): float
    {
        if ($this->interest_rate == 0) {
            return 0;
        }

        return $this->balance * ($this->interest_rate / 100);
    }
}
