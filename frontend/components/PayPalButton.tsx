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
    let isMounted = true;
    let paypalButtonsInstance: any = null;
    let venmoButtonsInstance: any = null;

    const loadPayPal = async () => {
      try {
        const clientId = await getPayPalClientId();
        
        // Check if PayPal SDK is already loaded
        if (window.paypal) {
          setPaypalLoaded(true);
          // Wait a bit to ensure DOM is ready
          setTimeout(() => initializePayPal(clientId), 100);
          return;
        }

        // Load PayPal SDK with Venmo support
        script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=${currency}&intent=capture&enable-funding=venmo,paypal`;
        script.async = true;
        
        script.onload = () => {
          if (isMounted) {
            setPaypalLoaded(true);
            // Wait a bit to ensure DOM is ready
            setTimeout(() => initializePayPal(clientId), 100);
          }
        };
        
        script.onerror = () => {
          if (isMounted) {
            setError('Failed to load PayPal SDK');
            setLoading(false);
          }
        };
        
        document.body.appendChild(script);
      } catch (err) {
        console.error('Error loading PayPal:', err);
        if (isMounted) {
          setError('Failed to initialize PayPal');
          setLoading(false);
        }
      }
    };

    const initializePayPal = (clientId: string) => {
      // Check if component is still mounted and container exists
      if (!isMounted || !window.paypal || !paypalButtonContainerRef.current) {
        console.log('PayPal initialization skipped - container not ready or component unmounted');
        return;
      }

      // Verify container is still in DOM
      if (!document.body.contains(paypalButtonContainerRef.current)) {
        console.error('Container element not in DOM');
        if (isMounted) {
          setError('Payment container not available');
          setLoading(false);
        }
        return;
      }

      try {
        // Clear any existing buttons
        if (paypalButtonsInstance) {
          try {
            paypalButtonsInstance.close();
          } catch (e) {
            // Ignore cleanup errors
          }
        }
        if (venmoButtonsInstance) {
          try {
            venmoButtonsInstance.close();
          } catch (e) {
            // Ignore cleanup errors
          }
        }

        // Clear container
        if (paypalButtonContainerRef.current) {
          paypalButtonContainerRef.current.innerHTML = '';
        }

        const createOrderHandler = async (data: any, actions: any) => {
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
            if (isMounted) {
              setError(err.response?.data?.message || 'Failed to create payment order');
            }
            throw err;
          }
        };

        const onApproveHandler = async (data: any, actions: any) => {
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

                if (isMounted) {
                  if (onSuccess) {
                    onSuccess({ contribution, capture: captureData });
                  } else {
                    // Default success handling
                    alert('Payment successful! Thank you for your contribution.');
                    window.location.reload();
                  }
                }
              }
            }
          } catch (err: any) {
            console.error('Error capturing PayPal payment:', err);
            if (isMounted) {
              setError(err.response?.data?.message || 'Failed to process payment');
              if (onError) {
                onError(err);
              }
            }
          }
        };

        const onErrorHandler = (err: any) => {
          console.error('PayPal button error:', err);
          if (isMounted) {
            setError('Payment failed. Please try again.');
            if (onError) {
              onError(err);
            }
          }
        };

        // Render PayPal button
        if (paypalButtonContainerRef.current && document.body.contains(paypalButtonContainerRef.current)) {
          paypalButtonsInstance = window.paypal.Buttons({
            style: {
              layout: 'vertical',
              color: 'blue',
              shape: 'rect',
              label: 'paypal',
            },
            fundingSource: window.paypal.FUNDING.PAYPAL,
            createOrder: createOrderHandler,
            onApprove: onApproveHandler,
            onError: onErrorHandler,
            onCancel: () => {
              if (isMounted) {
                setError(null);
              }
            },
          });

          paypalButtonsInstance.render(paypalButtonContainerRef.current).catch((err: any) => {
            console.error('Error rendering PayPal button:', err);
            if (isMounted) {
              setError('Failed to render PayPal button');
              setLoading(false);
            }
          });

          // Render Venmo button if available
          if (window.paypal.FUNDING && window.paypal.FUNDING.VENMO) {
            // Find or create Venmo container
            let venmoContainer = document.getElementById('venmo-button-container');
            
            if (venmoContainer && document.body.contains(venmoContainer)) {
              // Show container
              venmoContainer.style.display = 'block';
              
              venmoButtonsInstance = window.paypal.Buttons({
                fundingSource: window.paypal.FUNDING.VENMO,
                style: {
                  layout: 'vertical',
                  color: 'blue',
                  shape: 'rect',
                  height: 50,
                },
                createOrder: createOrderHandler,
                onApprove: onApproveHandler,
                onError: onErrorHandler,
                onCancel: () => {
                  if (isMounted) {
                    setError(null);
                  }
                },
              });

              venmoButtonsInstance.render(venmoContainer)
                .then(() => {
                  console.log('Venmo button rendered successfully');
                  if (venmoContainer) {
                    venmoContainer.style.display = 'block';
                  }
                })
                .catch((err: any) => {
                  console.log('Venmo not available:', err);
                  // Hide container if Venmo is not available
                  if (venmoContainer) {
                    venmoContainer.style.display = 'none';
                  }
                });
            }
          } else {
            // Hide Venmo container if not available
            const venmoContainer = document.getElementById('venmo-button-container');
            if (venmoContainer) {
              venmoContainer.style.display = 'none';
            }
          }
        }

        if (isMounted) {
          setLoading(false);
        }
      } catch (err) {
        console.error('Error initializing PayPal buttons:', err);
        if (isMounted) {
          setError('Failed to initialize PayPal button');
          setLoading(false);
        }
      }
    };

    loadPayPal();

    return () => {
      isMounted = false;
      
      // Cleanup PayPal button instances
      if (paypalButtonsInstance) {
        try {
          paypalButtonsInstance.close();
        } catch (e) {
          // Ignore cleanup errors
        }
      }
      if (venmoButtonsInstance) {
        try {
          venmoButtonsInstance.close();
        } catch (e) {
          // Ignore cleanup errors
        }
      }

      // Remove script if still exists
      if (script && script.parentNode) {
        script.parentNode.removeChild(script);
      }
    };
  }, [amount, currency, campaignId, campaignTitle, onSuccess, onError]);

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
      <div id="venmo-button-container" className="mt-4 min-h-[50px]" style={{ display: 'none' }}></div>
    </div>
  );
}

