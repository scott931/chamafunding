<?php

namespace Modules\Payments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class PayPalController extends Controller
{
    protected function baseUrl(): string
    {
        $mode = config('services.paypal.mode', 'sandbox');
        return $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get HTTP client options with SSL certificate verification
     */
    protected function getHttpOptions(): array
    {
        $options = [];

        // Use CA certificate bundle if available
        $certPath = storage_path('cacert.pem');
        if (file_exists($certPath)) {
            $options['verify'] = $certPath;
        } else {
            // Fallback: verify SSL but use system defaults
            $options['verify'] = true;
        }

        return $options;
    }

    protected function getAccessToken(): string
    {
        // Get credentials from config with fallback
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');

        // Fallback to env variables if config is empty
        if (empty($clientId)) {
            $clientId = env('PAYPAL_CLIENT_ID');
        }
        if (empty($secret)) {
            $secret = env('PAYPAL_CLIENT_SECRET');
        }

        // Final fallback to hardcoded test credentials (for development only)
        if (empty($clientId)) {
            $clientId = 'AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt';
        }
        if (empty($secret)) {
            $secret = 'EDVoxL5U9u4v-Z5hZNFnE8Ss6wAYtq2hTA6Cqj8KvrBCoC5hJ8_ZoqITfhnaiBACRynyvnUKUsekhc8b';
        }

        if (!$clientId || !$secret) {
            Log::error('PayPal credentials not configured');
            abort(500, 'PayPal credentials not configured.');
        }

        $response = Http::asForm()
            ->withOptions($this->getHttpOptions())
            ->withBasicAuth($clientId, $secret)
            ->post($this->baseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            Log::error('PayPal OAuth error', ['body' => $response->body()]);
            abort(500, 'Unable to authenticate with PayPal.');
        }

        return $response->json('access_token');
    }

    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'reference_id' => 'sometimes|string|max:100',
            'description' => 'sometimes|string|max:127',
        ]);

        $accessToken = $this->getAccessToken();
        $currency = strtoupper($request->input('currency', 'USD'));

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $request->input('reference_id', 'PU-' . uniqid()),
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($request->input('amount'), 2, '.', ''),
                ],
                'description' => $request->input('description', 'Payment'),
            ]],
            'application_context' => [
                'return_url' => config('app.url') . '/paypal/return',
                'cancel_url' => config('app.url') . '/paypal/cancel',
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withOptions($this->getHttpOptions())
            ->acceptJson()
            ->post($this->baseUrl() . '/v2/checkout/orders', $payload);

        if (!$response->successful()) {
            Log::error('PayPal create order error', ['body' => $response->body()]);
            return response()->json(['message' => 'Failed to create order'], 422);
        }

        return response()->json($response->json());
    }

    public function captureOrder(Request $request): JsonResponse
    {
        $request->validate([
            'orderId' => 'required|string',
        ]);

        $accessToken = $this->getAccessToken();
        $orderId = $request->input('orderId');

        // PayPal capture endpoint - POST with Content-Type: application/json but NO body
        $url = $this->baseUrl() . "/v2/checkout/orders/{$orderId}/capture";

        try {
            // Use Laravel HTTP client with empty body
            // The key is to use withBody('', 'application/json') to send empty string with correct content type
            $response = Http::withToken($accessToken)
                ->withOptions($this->getHttpOptions())
                ->withBody('', 'application/json') // Empty string body with JSON content type
                ->acceptJson()
                ->post($url);

            if ($response->successful()) {
                $data = $response->json();

                // Log successful capture for testing
                Log::info('PayPal payment captured successfully', [
                    'orderId' => $orderId,
                    'status' => $data['status'] ?? 'unknown',
                    'capture_id' => $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? 'unknown'
                ]);

                return response()->json($data);
            } else {
                // Extract error details from PayPal response
                $errorMessage = 'Failed to capture order';
                $responseBody = $response->body();
                $errorData = json_decode($responseBody, true);

                if (isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                } elseif (isset($errorData['details']) && is_array($errorData['details']) && count($errorData['details']) > 0) {
                    $errorMessage = $errorData['details'][0]['description'] ?? $errorMessage;
                }

                Log::error('PayPal capture error', [
                    'orderId' => $orderId,
                    'statusCode' => $response->status(),
                    'body' => $responseBody,
                    'error' => $errorData
                ]);

                return response()->json([
                    'message' => $errorMessage,
                    'details' => $errorData['details'] ?? [],
                    'debug_id' => $errorData['debug_id'] ?? null
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('PayPal capture exception', [
                'orderId' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to capture order: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Verify PayPal webhook signature using PayPal API
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $mode = config('services.paypal.mode', 'sandbox');
        $webhookId = config('services.paypal.webhook_id');

        // Skip verification if webhook ID not configured (for development)
        if (empty($webhookId)) {
            Log::warning('PayPal webhook ID not configured, skipping verification');
            return true; // Allow in development, but log warning
        }

        // PayPal sends webhook signature in headers
        $paypalAuthAlgo = $request->header('Paypal-Auth-Algo');
        $paypalCertUrl = $request->header('Paypal-Cert-Url');
        $paypalTransmissionId = $request->header('Paypal-Transmission-Id');
        $paypalTransmissionSig = $request->header('Paypal-Transmission-Sig');
        $paypalTransmissionTime = $request->header('Paypal-Transmission-Time');

        // All headers are required for verification
        if (empty($paypalAuthAlgo) || empty($paypalCertUrl) || empty($paypalTransmissionId)
            || empty($paypalTransmissionSig) || empty($paypalTransmissionTime)) {
            Log::warning('PayPal webhook verification headers missing');
            return false;
        }

        // Verify webhook signature via PayPal API
        try {
            $accessToken = $this->getAccessToken();

            $verificationPayload = [
                'auth_algo' => $paypalAuthAlgo,
                'cert_url' => $paypalCertUrl,
                'transmission_id' => $paypalTransmissionId,
                'transmission_sig' => $paypalTransmissionSig,
                'transmission_time' => $paypalTransmissionTime,
                'webhook_id' => $webhookId,
                'webhook_event' => $request->all(),
            ];

            $response = Http::withToken($accessToken)
                ->withOptions($this->getHttpOptions())
                ->acceptJson()
                ->post($this->baseUrl() . '/v1/notifications/verify-webhook-signature', $verificationPayload);

            if ($response->successful()) {
                $verificationResult = $response->json();
                if (($verificationResult['verification_status'] ?? '') === 'SUCCESS') {
                    Log::info('PayPal webhook signature verified successfully');
                    return true;
                } else {
                    Log::warning('PayPal webhook signature verification failed', [
                        'status' => $verificationResult['verification_status'] ?? 'Unknown'
                    ]);
                    return false;
                }
            } else {
                Log::error('PayPal webhook signature verification request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('PayPal webhook signature verification error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        Log::info('PayPal webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);

        // Verify webhook signature
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('PayPal webhook signature verification failed');
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        $eventType = $request->input('event_type');

        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                Log::info('Payment capture completed', $request->all());
                // TODO: Update contribution status, send notifications, etc.
                break;
            case 'PAYMENT.CAPTURE.DENIED':
                Log::warning('Payment capture denied', $request->all());
                // TODO: Handle failed payment
                break;
            case 'CHECKOUT.ORDER.APPROVED':
                Log::info('Checkout order approved', $request->all());
                break;
            default:
                Log::info('Unhandled PayPal webhook event', ['event_type' => $eventType]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function testConnection(): JsonResponse
    {
        try {
            $accessToken = $this->getAccessToken();
            return response()->json([
                'status' => 'success',
                'message' => 'PayPal connection successful',
                'mode' => config('services.paypal.mode'),
                'base_url' => $this->baseUrl()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'PayPal connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
}