'use client';

import { useEffect, useRef, useState } from 'react';
import { paymentsApi } from '@/lib/api/payments';
import apiClient from '@/lib/api/client';

declare global {
  interface Window {
    paypal?: any;
  }
}

interface PayPalButtonProps {
  amount: number;
  currency?: string;
  campaignId: string | number;
  campaignTitle?: string;
  onSuccess?: (data: any) => void;
  onError?: (error: any) => void;
}

export default function PayPalButton({
  amount,
  currency = 'USD',
  campaignId,
  campaignTitle = 'Campaign Contribution',
  onSuccess,
  onError,
}: PayPalButtonProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [paypalLoaded, setPaypalLoaded] = useState(false);
  const paypalButtonContainerRef = useRef<HTMLDivElement>(null);
  const paypalInstanceRef = useRef<any>(null);

  // Get PayPal client ID from settings or use default
  const getPayPalClientId = async () => {
    try {
      // Try to get from public API endpoint
      const response = await apiClient.get('/v1/paypal/client-id');
      if (response.data?.data?.client_id) {
        return response.data.data.client_id;
      }
    } catch (err) {
      console.warn('Could not fetch PayPal client ID from API');
    }
    
    // Fallback to env or default test credentials
    return process.env.NEXT_PUBLIC_PAYPAL_CLIENT_ID || 'AT16jl6nE2hAKGojRWT8_NsI7iVHl79Q_A7nNkysNVC_M2X0AYHbE_YKD7_YLcXs9X1BkMm7nXo2nEwt';
  };

  useEffect(() => {
    let script: HTMLScriptElement | null = null;

    const loadPayPal = async () => {
      try {
        const clientId = await getPayPalClientId();
        
        // Check if PayPal SDK is already loaded
        if (window.paypal) {
          setPaypalLoaded(true);
          initializePayPal(clientId);
          return;
        }

        // Load PayPal SDK
        script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=${currency}`;
        script.async = true;
        
        script.onload = () => {
          setPaypalLoaded(true);
          initializePayPal(clientId);
        };
        
        script.onerror = () => {
          setError('Failed to load PayPal SDK');
          setLoading(false);
        };
        
        document.body.appendChild(script);
      } catch (err) {
        console.error('Error loading PayPal:', err);
        setError('Failed to initialize PayPal');
        setLoading(false);
      }
    };

    const initializePayPal = (clientId: string) => {
      if (!window.paypal || !paypalButtonContainerRef.current) {
        return;
      }

      try {
        // Clear any existing buttons
        if (paypalInstanceRef.current) {
          paypalInstanceRef.current.close();
        }
        paypalButtonContainerRef.current.innerHTML = '';

        window.paypal.Buttons({
          style: {
            layout: 'vertical',
            color: 'blue',
            shape: 'rect',
            label: 'paypal',
          },
          createOrder: async (data: any, actions: any) => {
            try {
              const order = await paymentsApi.createPayPalOrder(
                amount,
                currency,
                campaignTitle,
                `CAMP-${campaignId}-${Date.now()}`
              );
              return order.id;
            } catch (err: any) {
              console.error('Error creating PayPal order:', err);
              setError(err.response?.data?.message || 'Failed to create payment order');
              throw err;
            }
          },
          onApprove: async (data: any, actions: any) => {
            try {
              // Capture the payment
              const captureData = await paymentsApi.capturePayPalOrder(data.orderID);
              
              if (captureData.status === 'COMPLETED') {
                const capture = captureData.purchase_units[0]?.payments?.captures[0];
                
                if (capture) {
                  // Create contribution record
                  const contribution = await paymentsApi.createContribution(campaignId, {
                    amount: parseFloat(capture.amount.value),
                    currency: capture.amount.currency_code,
                    payment_processor: 'paypal',
                    transaction_id: capture.id,
                    status: 'succeeded',
                  });

                  if (onSuccess) {
                    onSuccess({ contribution, capture: captureData });
                  } else {
                    // Default success handling
                    alert('Payment successful! Thank you for your contribution.');
                    window.location.reload();
                  }
                }
              }
            } catch (err: any) {
              console.error('Error capturing PayPal payment:', err);
              setError(err.response?.data?.message || 'Failed to process payment');
              if (onError) {
                onError(err);
              }
            }
          },
          onError: (err: any) => {
            console.error('PayPal button error:', err);
            setError('Payment failed. Please try again.');
            if (onError) {
              onError(err);
            }
          },
          onCancel: () => {
            setError(null);
          },
        }).render(paypalButtonContainerRef.current);

        setLoading(false);
      } catch (err) {
        console.error('Error initializing PayPal buttons:', err);
        setError('Failed to initialize PayPal button');
        setLoading(false);
      }
    };

    loadPayPal();

    return () => {
      if (script && script.parentNode) {
        script.parentNode.removeChild(script);
      }
    };
  }, [amount, currency, campaignId, campaignTitle]);

  if (error && !loading) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-md">
        <p className="text-sm text-red-800">{error}</p>
        <button
          onClick={() => {
            setError(null);
            setLoading(true);
            window.location.reload();
          }}
          className="mt-2 text-sm text-red-600 hover:text-red-800 underline"
        >
          Try again
        </button>
      </div>
    );
  }

  return (
    <div>
      {loading && (
        <div className="text-center py-4">
          <div className="inline-block animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent mb-2"></div>
          <p className="text-sm text-gray-600">Loading PayPal...</p>
        </div>
      )}
      <div ref={paypalButtonContainerRef} className="min-h-[50px]"></div>
    </div>
  );
}

