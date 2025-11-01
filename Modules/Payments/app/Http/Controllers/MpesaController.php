<?php

namespace Modules\Payments\app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Payments\app\Services\MpesaService;
use App\Models\FinancialTransaction;
use App\Models\PaymentMethod;

class MpesaController extends \App\Http\Controllers\Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Initiate M-Pesa STK Push payment
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'account_reference' => 'required|string|max:255',
            'transaction_description' => 'required|string|max:255',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'savings_account_id' => 'nullable|exists:savings_accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate phone number
        if (!$this->mpesaService->validatePhoneNumber($request->phone_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format. Please use a valid Kenyan phone number.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $amount = (int) ($request->amount * 100); // Convert to cents
            $feeAmount = $this->calculateMpesaFee($amount);
            $netAmount = $amount - $feeAmount;

            // Create transaction record
            $transaction = FinancialTransaction::create([
                'transaction_type' => 'payment',
                'reference' => 'MPESA_' . time() . '_' . Str::random(8),
                'user_id' => Auth::id(),
                'campaign_id' => $request->campaign_id,
                'savings_account_id' => $request->savings_account_id,
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'currency' => 'KES',
                'payment_method' => 'mobile_money',
                'payment_provider' => 'mpesa',
                'status' => 'pending',
                'description' => $request->transaction_description,
                'metadata' => [
                    'phone_number' => $request->phone_number,
                    'account_reference' => $request->account_reference
                ]
            ]);

            // Initiate STK Push
            $stkResponse = $this->mpesaService->initiateSTKPush(
                $request->phone_number,
                $request->amount,
                $request->account_reference,
                $request->transaction_description
            );

            if ($stkResponse['success']) {
                // Update transaction with checkout request ID
                $transaction->update([
                    'external_transaction_id' => $stkResponse['data']['CheckoutRequestID']
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'transaction' => $transaction->load(['user', 'campaign', 'savingsAccount']),
                        'stk_push' => $stkResponse['data']
                    ],
                    'message' => 'M-Pesa payment initiated successfully. Please check your phone to complete the payment.'
                ], 201);
            } else {
                $transaction->update(['status' => 'failed']);
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $stkResponse['message'],
                    'data' => $stkResponse['data'] ?? null
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate M-Pesa payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Query transaction status
     */
    public function queryTransactionStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'checkout_request_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $response = $this->mpesaService->queryTransactionStatus($request->checkout_request_id);

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $response['data'],
                    'message' => 'Transaction status retrieved successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to query transaction status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle M-Pesa callback
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $callbackData = $request->all();

            // Log the callback for debugging
            \Log::info('M-Pesa callback received', $callbackData);

            $result = $this->mpesaService->processCallback($callbackData);

            if ($result['success']) {
                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Callback processed successfully'
                ]);
            } else {
                return response()->json([
                    'ResultCode' => 1,
                    'ResultDesc' => 'Callback processing failed'
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('M-Pesa webhook error', [
                'error' => $e->getMessage(),
                'callback_data' => $request->all()
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get M-Pesa payment methods for user
     */
    public function getPaymentMethods(): JsonResponse
    {
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())
            ->where('provider', 'mpesa')
            ->where('is_verified', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentMethods,
            'message' => 'M-Pesa payment methods retrieved successfully'
        ]);
    }

    /**
     * Add M-Pesa payment method
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate phone number
        if (!$this->mpesaService->validatePhoneNumber($request->phone_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format. Please use a valid Kenyan phone number.'
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
                'type' => 'mobile_money',
                'provider' => 'mpesa',
                'external_id' => $this->formatPhoneNumber($request->phone_number),
                'last_four' => substr($request->phone_number, -4),
                'brand' => 'M-Pesa',
                'country' => 'KE',
                'is_default' => $request->is_default ?? false,
                'is_verified' => true, // M-Pesa numbers are considered verified
                'metadata' => [
                    'phone_number' => $request->phone_number,
                    'formatted_phone' => $this->formatPhoneNumber($request->phone_number)
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => $paymentMethod,
                'message' => 'M-Pesa payment method added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add M-Pesa payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get supported countries
     */
    public function getSupportedCountries(): JsonResponse
    {
        $countries = $this->mpesaService->getSupportedCountries();

        return response()->json([
            'success' => true,
            'data' => $countries,
            'message' => 'Supported countries retrieved successfully'
        ]);
    }

    /**
     * Format phone number for M-Pesa
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Add country code if not present
        if (strpos($phoneNumber, '254') !== 0) {
            if (strpos($phoneNumber, '0') === 0) {
                $phoneNumber = '254' . substr($phoneNumber, 1);
            } else {
                $phoneNumber = '254' . $phoneNumber;
            }
        }

        return $phoneNumber;
    }

    /**
     * Calculate M-Pesa fee
     */
    private function calculateMpesaFee(int $amount): int
    {
        // M-Pesa fee structure (simplified)
        // This should be updated based on actual M-Pesa fee structure
        if ($amount <= 10000) { // Up to 100 KES
            return (int) round($amount * 0.01); // 1%
        } elseif ($amount <= 100000) { // Up to 1,000 KES
            return (int) round($amount * 0.008); // 0.8%
        } else {
            return (int) round($amount * 0.005); // 0.5%
        }
    }
}
