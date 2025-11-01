<?php

namespace Modules\Payments\app\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\FinancialTransaction;

class MpesaService
{
    private $consumerKey;
    private $consumerSecret;
    private $shortcode;
    private $passkey;
    private $environment;
    private $callbackUrl;
    private $baseUrl;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->shortcode = config('services.mpesa.shortcode');
        $this->passkey = config('services.mpesa.passkey');
        $this->environment = config('services.mpesa.environment', 'sandbox');
        $this->callbackUrl = config('services.mpesa.callback_url');

        $this->baseUrl = $this->environment === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Get access token for M-Pesa API
     */
    public function getAccessToken()
    {
        $cacheKey = 'mpesa_access_token';

        return Cache::remember($cacheKey, 3000, function () {
            $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';

            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('M-Pesa access token request failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        });
    }

    /**
     * Initiate STK Push payment
     */
    public function initiateSTKPush($phoneNumber, $amount, $accountReference, $transactionDesc)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to get access token'
            ];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        ];

        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
                return [
                    'success' => true,
                    'data' => $data,
                    'message' => 'STK Push initiated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $data['ResponseDescription'] ?? 'STK Push failed',
                    'data' => $data
                ];
            }
        }

        Log::error('M-Pesa STK Push request failed', [
            'status' => $response->status(),
            'response' => $response->body(),
            'payload' => $payload
        ]);

        return [
            'success' => false,
            'message' => 'STK Push request failed'
        ];
    }

    /**
     * Query transaction status
     */
    public function queryTransactionStatus($checkoutRequestId)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to get access token'
            ];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];

        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'data' => $data,
                'message' => 'Transaction status retrieved successfully'
            ];
        }

        Log::error('M-Pesa transaction query failed', [
            'status' => $response->status(),
            'response' => $response->body(),
            'checkout_request_id' => $checkoutRequestId
        ]);

        return [
            'success' => false,
            'message' => 'Transaction query failed'
        ];
    }

    /**
     * Process M-Pesa callback
     */
    public function processCallback($callbackData)
    {
        try {
            $body = $callbackData['Body'];
            $stkCallback = $body['stkCallback'];

            $merchantRequestId = $stkCallback['MerchantRequestID'];
            $checkoutRequestId = $stkCallback['CheckoutRequestID'];
            $resultCode = $stkCallback['ResultCode'];
            $resultDesc = $stkCallback['ResultDesc'];

            // Find the transaction by checkout request ID
            $transaction = FinancialTransaction::where('external_transaction_id', $checkoutRequestId)
                ->where('payment_provider', 'mpesa')
                ->first();

            if (!$transaction) {
                Log::warning('M-Pesa callback: Transaction not found', [
                    'checkout_request_id' => $checkoutRequestId
                ]);
                return;
            }

            if ($resultCode == 0) {
                // Payment successful
                $callbackMetadata = $stkCallback['CallbackMetadata']['Item'];

                $amount = null;
                $mpesaReceiptNumber = null;
                $transactionDate = null;
                $phoneNumber = null;

                foreach ($callbackMetadata as $item) {
                    switch ($item['Name']) {
                        case 'Amount':
                            $amount = $item['Value'];
                            break;
                        case 'MpesaReceiptNumber':
                            $mpesaReceiptNumber = $item['Value'];
                            break;
                        case 'TransactionDate':
                            $transactionDate = $item['Value'];
                            break;
                        case 'PhoneNumber':
                            $phoneNumber = $item['Value'];
                            break;
                    }
                }

                // Update transaction with M-Pesa details
                $transaction->update([
                    'status' => 'completed',
                    'external_transaction_id' => $mpesaReceiptNumber,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'mpesa_receipt_number' => $mpesaReceiptNumber,
                        'transaction_date' => $transactionDate,
                        'phone_number' => $phoneNumber,
                        'merchant_request_id' => $merchantRequestId,
                        'checkout_request_id' => $checkoutRequestId
                    ])
                ]);

                Log::info('M-Pesa payment completed successfully', [
                    'transaction_id' => $transaction->id,
                    'mpesa_receipt_number' => $mpesaReceiptNumber,
                    'amount' => $amount
                ]);

            } else {
                // Payment failed
                $transaction->update([
                    'status' => 'failed',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'failure_reason' => $resultDesc,
                        'merchant_request_id' => $merchantRequestId,
                        'checkout_request_id' => $checkoutRequestId
                    ])
                ]);

                Log::warning('M-Pesa payment failed', [
                    'transaction_id' => $transaction->id,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc
                ]);
            }

            return [
                'success' => true,
                'message' => 'Callback processed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('M-Pesa callback processing failed', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);

            return [
                'success' => false,
                'message' => 'Callback processing failed: ' . $e->getMessage()
            ];
        }
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
     * Validate phone number
     */
    public function validatePhoneNumber($phoneNumber)
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);

        // Kenyan phone numbers should be 12 digits starting with 254
        return preg_match('/^254[0-9]{9}$/', $formatted);
    }

    /**
     * Get supported countries for M-Pesa
     */
    public function getSupportedCountries()
    {
        return [
            'KE' => 'Kenya',
            'TZ' => 'Tanzania',
            'UG' => 'Uganda',
            'RW' => 'Rwanda',
            'BF' => 'Burkina Faso',
            'GH' => 'Ghana',
            'MW' => 'Malawi',
            'ZM' => 'Zambia',
            'ET' => 'Ethiopia',
            'EG' => 'Egypt',
            'DZ' => 'Algeria',
            'MA' => 'Morocco',
            'ZA' => 'South Africa',
            'NG' => 'Nigeria',
            'AO' => 'Angola',
            'MZ' => 'Mozambique',
            'MG' => 'Madagascar',
            'CM' => 'Cameroon',
            'CI' => 'Côte d\'Ivoire',
            'SN' => 'Senegal'
        ];
    }
}
