<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentsController extends Controller
{
    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            $query = FinancialTransaction::with(['user', 'campaign', 'savingsAccount'])
                ->where('user_id', Auth::id());

            // Filter by transaction type
            if ($request->has('type')) {
                $query->where('transaction_type', $request->type);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $payments = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $payments,
                'message' => 'Payments retrieved successfully'
            ]);
        }

        // For web requests, return view
        $payments = FinancialTransaction::with(['user', 'campaign', 'savingsAccount'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('payments::index', compact('payments'));
    }

    /**
     * Process a payment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|string',
            'payment_provider' => 'required|string|in:stripe,paypal,mpesa,flutterwave',
            'description' => 'nullable|string|max:255',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'savings_account_id' => 'nullable|exists:savings_accounts,id',
            'external_transaction_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $amount = $request->amount * 100; // Convert to cents
            $feeAmount = $this->calculateFee($amount, $request->payment_method);
            $netAmount = $amount - $feeAmount;

            $transaction = FinancialTransaction::create([
                'transaction_type' => 'payment',
                'reference' => 'TXN_' . time() . '_' . Str::random(8),
                'user_id' => Auth::id(),
                'campaign_id' => $request->campaign_id,
                'savings_account_id' => $request->savings_account_id,
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'currency' => strtoupper($request->currency),
                'payment_method' => $request->payment_method,
                'payment_provider' => $request->payment_provider,
                'external_transaction_id' => $request->external_transaction_id,
                'status' => 'pending',
                'description' => $request->description,
            ]);

            // Here you would integrate with the actual payment provider
            // For now, we'll simulate a successful payment
            $transaction->markAsCompleted();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['user', 'campaign', 'savingsAccount']),
                'message' => 'Payment processed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payment
     */
    public function show($id): JsonResponse
    {
        $payment = FinancialTransaction::with(['user', 'campaign', 'savingsAccount'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Payment retrieved successfully'
        ]);
    }

    /**
     * Check payment status
     */
    public function status($id): JsonResponse
    {
        $payment = FinancialTransaction::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'reference' => $payment->reference,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at,
                'processed_at' => $payment->processed_at,
            ],
            'message' => 'Payment status retrieved successfully'
        ]);
    }

    /**
     * Refund a payment
     */
    public function refund(Request $request, $id): JsonResponse
    {
        $payment = FinancialTransaction::where('user_id', Auth::id())
            ->findOrFail($id);

        if (!$payment->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payments can be refunded'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0.01|max:' . ($payment->amount / 100),
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $refundAmount = $request->amount ? $request->amount * 100 : $payment->amount;
            $refundFee = $this->calculateFee($refundAmount, $payment->payment_method);
            $netRefundAmount = $refundAmount - $refundFee;

            $refundTransaction = FinancialTransaction::create([
                'transaction_type' => 'refund',
                'reference' => 'REF_' . time() . '_' . Str::random(8),
                'user_id' => Auth::id(),
                'campaign_id' => $payment->campaign_id,
                'savings_account_id' => $payment->savings_account_id,
                'amount' => $refundAmount,
                'fee_amount' => $refundFee,
                'net_amount' => $netRefundAmount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'payment_provider' => $payment->payment_provider,
                'status' => 'pending',
                'description' => $request->reason ?? 'Payment refund',
                'metadata' => ['original_transaction_id' => $payment->id],
            ]);

            // Here you would integrate with the actual payment provider for refund
            $refundTransaction->markAsCompleted();

            // Update original transaction status
            $payment->update(['status' => 'refunded']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $refundTransaction,
                'message' => 'Refund processed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history
     */
    public function history(Request $request): JsonResponse
    {
        $query = FinancialTransaction::with(['campaign', 'savingsAccount'])
            ->where('user_id', Auth::id());

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payments,
            'message' => 'Payment history retrieved successfully'
        ]);
    }

    /**
     * Get payment methods
     */
    public function paymentMethods(): JsonResponse
    {
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())
            ->where('is_verified', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentMethods,
            'message' => 'Payment methods retrieved successfully'
        ]);
    }

    /**
     * Add a payment method
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:card,bank_account,mobile_money,digital_wallet',
            'provider' => 'required|string|in:stripe,paypal,mpesa,flutterwave',
            'external_id' => 'nullable|string',
            'last_four' => 'nullable|string|size:4',
            'brand' => 'nullable|string',
            'exp_month' => 'nullable|string|size:2',
            'exp_year' => 'nullable|string|size:4',
            'country' => 'nullable|string|size:2',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // If setting as default, unset other defaults
            if ($request->is_default) {
                PaymentMethod::where('user_id', Auth::id())
                    ->update(['is_default' => false]);
            }

            $paymentMethod = PaymentMethod::create([
                'user_id' => Auth::id(),
                'type' => $request->type,
                'provider' => $request->provider,
                'external_id' => $request->external_id,
                'last_four' => $request->last_four,
                'brand' => $request->brand,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'country' => $request->country,
                'is_default' => $request->is_default ?? false,
                'is_verified' => false, // Will be verified through webhook
            ]);

            return response()->json([
                'success' => true,
                'data' => $paymentMethod,
                'message' => 'Payment method added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a payment method
     */
    public function removePaymentMethod($id): JsonResponse
    {
        $paymentMethod = PaymentMethod::where('user_id', Auth::id())
            ->findOrFail($id);

        try {
            $paymentMethod->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment method removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate payment fee
     */
    private function calculateFee(int $amount, string $paymentMethod): int
    {
        $feeRate = 0.029; // 2.9% default fee rate

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

        return (int) round($amount * $feeRate);
    }
}
