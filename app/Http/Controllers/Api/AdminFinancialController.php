<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminFinancialController extends Controller
{
    /**
     * Return financial overview stats for admins.
     */
    public function overview(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $stats = [
            'total_fees_this_month' => $this->toMajor(
                FinancialTransaction::where('transaction_type', 'fee')
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount')
            ),
            'total_fees_year' => $this->toMajor(
                FinancialTransaction::where('transaction_type', 'fee')
                    ->where('status', 'completed')
                    ->whereYear('created_at', now()->year)
                    ->sum('amount')
            ),
            'total_volume_this_month' => $this->toMajor(
                FinancialTransaction::where('transaction_type', 'payment')
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount')
            ),
            'pending_payouts' => $this->toMajor(
                FinancialTransaction::where('status', 'pending')->sum('amount')
            ),
            'failed_volume' => $this->toMajor(
                FinancialTransaction::where('status', 'failed')->sum('amount')
            ),
        ];

        $feeRevenueOverTime = FinancialTransaction::where('transaction_type', 'fee')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(45))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => $this->toMajor($row->total),
            ]);

        $topPaymentMethods = FinancialTransaction::select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as volume')
            )
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'count' => (int) $row->count,
                'volume' => $this->toMajor($row->volume),
            ]);

        $recentHighValue = FinancialTransaction::with('user:id,name,email')
            ->where('status', 'completed')
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'user' => $transaction->user?->only(['id', 'name', 'email']),
                'amount' => $this->toMajor($transaction->amount),
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'status' => $transaction->status,
                'created_at' => optional($transaction->created_at)->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'fee_revenue_over_time' => $feeRevenueOverTime,
                'top_payment_methods' => $topPaymentMethods,
                'recent_transactions' => $recentHighValue,
            ],
        ]);
    }

    /**
     * Return paginated financial transactions (admin only).
     */
    public function transactions(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $query = FinancialTransaction::with(['user:id,name,email', 'campaign:id,title']);

        if ($type = $request->query('type')) {
            $query->where('transaction_type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($paymentMethod = $request->query('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($provider = $request->query('payment_provider')) {
            $query->where('payment_provider', $provider);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('external_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min(max((int) $request->query('per_page', 25), 5), 100);
        $transactions = $query->orderByDesc('created_at')->paginate($perPage);

        $transactions->getCollection()->transform(function ($transaction) {
            return [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'transaction_type' => $transaction->transaction_type,
                'status' => $transaction->status,
                'amount' => $this->toMajor($transaction->amount),
                'fee_amount' => $this->toMajor($transaction->fee_amount),
                'net_amount' => $this->toMajor($transaction->net_amount),
                'currency' => $transaction->currency,
                'payment_method' => $transaction->payment_method,
                'payment_provider' => $transaction->payment_provider,
                'description' => $transaction->description,
                'user' => $transaction->user?->only(['id', 'name', 'email']),
                'campaign' => $transaction->campaign?->only(['id', 'title']),
                'created_at' => optional($transaction->created_at)->toIso8601String(),
                'processed_at' => optional($transaction->processed_at)->toIso8601String(),
            ];
        });

        $metrics = [
            'total_count' => FinancialTransaction::count(),
            'completed_today' => FinancialTransaction::whereDate('created_at', now()->toDateString())
                ->where('status', 'completed')
                ->count(),
            'volume_today' => $this->toMajor(
                FinancialTransaction::whereDate('created_at', now()->toDateString())
                    ->where('status', 'completed')
                    ->sum('amount')
            ),
            'pending_count' => FinancialTransaction::where('status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => [
                    'data' => $transactions->items(),
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
                'metrics' => $metrics,
            ],
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            abort(403, 'Only administrators can perform this action.');
        }
    }

    private function toMajor(float|int|string $value): float
    {
        return round(((float) $value) / 100, 2);
    }
}

