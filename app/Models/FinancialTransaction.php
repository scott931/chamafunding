<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'reference',
        'user_id',
        'campaign_id',
        'savings_account_id',
        'amount',
        'fee_amount',
        'net_amount',
        'currency',
        'payment_method',
        'payment_provider',
        'external_transaction_id',
        'status',
        'description',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function savingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    public function isPayment(): bool
    {
        return $this->transaction_type === 'payment';
    }

    public function isRefund(): bool
    {
        return $this->transaction_type === 'refund';
    }

    public function isFee(): bool
    {
        return $this->transaction_type === 'fee';
    }

    public function isInterest(): bool
    {
        return $this->transaction_type === 'interest';
    }

    public function isTransfer(): bool
    {
        return $this->transaction_type === 'transfer';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
        ]);
    }

    public function calculateFee(float $amount, string $paymentMethod = null): float
    {
        $feeRate = 0.029; // 2.9% default fee rate

        // Different fee rates for different payment methods
        switch ($paymentMethod) {
            case 'card':
                $feeRate = 0.029;
                break;
            case 'bank_transfer':
                $feeRate = 0.008;
                break;
            case 'mobile_money':
                $feeRate = 0.015;
                break;
            default:
                $feeRate = 0.029;
        }

        return $amount * $feeRate;
    }
}
