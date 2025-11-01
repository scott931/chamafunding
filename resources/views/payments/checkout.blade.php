<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal & Venmo Checkout</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .payment-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: none;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .test-info {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .test-info h3 {
            margin-top: 0;
            color: #0066cc;
        }
        .credentials {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PayPal & Venmo Integration Test</h1>

        <div class="test-info">
            <h3>Test Environment</h3>
            <p>This is a sandbox test environment. Use PayPal sandbox credentials to test payments.</p>
            <div class="credentials">
                <strong>PayPal Sandbox Credentials:</strong><br>
                Client ID: AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt<br>
                Mode: {{ config('services.paypal.mode') }}<br>
                Base URL: {{ config('services.paypal.sandbox.base_url') }}
            </div>
        </div>

        <div id="status" class="status"></div>

        <div class="payment-section">
            <h3>Test Payment - $10.00 USD</h3>
            <p>Click the buttons below to test PayPal and Venmo payments:</p>

            <div id="paypal-buttons"></div>
            <div id="venmo-buttons"></div>
        </div>

        <div class="payment-section">
            <h3>API Test</h3>
            <button onclick="testPayPalConnection()">Test PayPal Connection</button>
            <button onclick="createTestOrder()">Create Test Order</button>
        </div>
    </div>

    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt&currency=USD&components=buttons,marks&enable-funding=venmo,paypal"></script>

    <script>
        const API_BASE = '/api/v1';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function showStatus(message, type = 'success') {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = `status ${type}`;
            status.style.display = 'block';
        }

        function getHeaders() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            };
        }

        // Test PayPal connection
        async function testPayPalConnection() {
            try {
                const response = await fetch(`${API_BASE}/paypal/test-connection`, {
                    method: 'GET',
                    headers: getHeaders()
                });
                const data = await response.json();

                if (data.status === 'success') {
                    showStatus(`✅ PayPal Connection Successful: ${data.message}`, 'success');
                } else {
                    showStatus(`❌ PayPal Connection Failed: ${data.message}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Error testing connection: ${error.message}`, 'error');
            }
        }

        // Create test order
        async function createTestOrder() {
            try {
                const response = await fetch(`${API_BASE}/paypal/test-order`, {
                    method: 'POST',
                    headers: getHeaders(),
                    body: JSON.stringify({
                        amount: 10.00,
                        currency: 'USD',
                        description: 'Test Payment',
                        reference_id: 'TEST-' + Date.now()
                    })
                });
                const data = await response.json();

                if (data.id) {
                    showStatus(`✅ Order Created: ${data.id}`, 'success');
                    console.log('Order created:', data);
                } else {
                    showStatus(`❌ Failed to create order: ${data.message || 'Unknown error'}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Error creating order: ${error.message}`, 'error');
            }
        }

        // PayPal Buttons
        paypal.Buttons({
            fundingSource: paypal.FUNDING.PAYPAL,
            createOrder: function() {
                return fetch(`${API_BASE}/paypal/test-order`, {
                    method: 'POST',
                    headers: getHeaders(),
                    body: JSON.stringify({
                        amount: 10.00,
                        currency: 'USD',
                        description: 'PayPal Test Payment',
                        reference_id: 'PAYPAL-' + Date.now()
                    })
                }).then(res => res.json()).then(data => {
                    if (data.id) {
                        return data.id;
                    } else {
                        throw new Error(data.message || 'Failed to create order');
                    }
                });
            },
            onApprove: function(data) {
                return fetch(`${API_BASE}/paypal/test-capture`, {
                    method: 'POST',
                    headers: getHeaders(),
                    body: JSON.stringify({
                        orderId: data.orderID
                    })
                }).then(res => res.json()).then(data => {
                    showStatus(`✅ PayPal Payment Completed! Order ID: ${data.id}`, 'success');
                    console.log('Payment captured:', data);
                }).catch(error => {
                    showStatus(`❌ Payment capture failed: ${error.message}`, 'error');
                });
            },
            onError: function(err) {
                showStatus(`❌ PayPal Error: ${err.message}`, 'error');
                console.error('PayPal error:', err);
            }
        }).render('#paypal-buttons');

        // Venmo Button (if eligible) - Wait for PayPal SDK to load
        paypal.Buttons({
            fundingSource: paypal.FUNDING.VENMO,
            createOrder: function() {
                return fetch(`${API_BASE}/paypal/test-order`, {
                    method: 'POST',
                    headers: getHeaders(),
                    body: JSON.stringify({
                        amount: 10.00,
                        currency: 'USD',
                        description: 'Venmo Test Payment',
                        reference_id: 'VENMO-' + Date.now()
                    })
                }).then(res => res.json()).then(data => {
                    if (data.id) {
                        return data.id;
                    } else {
                        throw new Error(data.message || 'Failed to create order');
                    }
                });
            },
            onApprove: function(data) {
                return fetch(`${API_BASE}/paypal/test-capture`, {
                    method: 'POST',
                    headers: getHeaders(),
                    body: JSON.stringify({
                        orderId: data.orderID
                    })
                }).then(res => res.json()).then(data => {
                    showStatus(`✅ Venmo Payment Completed! Order ID: ${data.id}`, 'success');
                    console.log('Venmo payment captured:', data);
                }).catch(error => {
                    showStatus(`❌ Venmo payment capture failed: ${error.message}`, 'error');
                });
            },
            onError: function(err) {
                showStatus(`❌ Venmo Error: ${err.message}`, 'error');
                console.error('Venmo error:', err);
            }
        }).render('#venmo-buttons').catch(function(err) {
            // If Venmo is not available, show message
            document.getElementById('venmo-buttons').innerHTML =
                '<p style="color: #666;">Venmo is not available in your region or device.</p>';
        });

        // Auto-test connection on page load
        window.addEventListener('load', function() {
            setTimeout(testPayPalConnection, 1000);
        });
    </script>
</body>
</html>
