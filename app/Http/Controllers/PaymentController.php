<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;

class PaymentController extends Controller
{
    private $paymongoSecretKey;
    private $paymongoPublicKey;
    
    public function __construct()
    {
        $this->middleware('auth');
        $this->paymongoSecretKey = env('PAYMONGO_SECRET_KEY');
        $this->paymongoPublicKey = env('PAYMONGO_PUBLIC_KEY');
    }

    /**
     * Show the premium upgrade page
     */
    public function showUpgrade()
    {
        $user = Auth::user();
        $subscription = null;
        
        if ($user->is_premium) {
            $subscription = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();
        }
        
        return view('premium.upgrade', compact('subscription'));
    }

    /**
     * Create a PayMongo payment intent
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            // Check if PayMongo keys are configured
            if (empty($this->paymongoSecretKey) || empty($this->paymongoPublicKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment system not configured. Please contact support.'
                ], 500);
            }

            $request->validate([
                'payment_method' => 'required|in:gcash,paymaya,card',
                'plan' => 'required|in:premium_monthly,premium_yearly',
                'amount' => 'required|numeric|min:1'
            ]);

            $user = Auth::user();
            
            if ($user->is_premium) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already a premium user'
                ], 400);
            }

            // Create payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'currency' => 'PHP',
                'status' => 'pending',
                'payment_gateway' => 'paymongo'
            ]);

            // Create PayMongo checkout session (better for GCash/PayMaya)
            $checkoutData = $this->createPayMongoCheckoutSession(
                $request->amount * 100, // Convert to centavos
                $request->payment_method,
                $payment->id
            );

            if (!$checkoutData) {
                $payment->update(['status' => 'failed', 'failure_reason' => 'Failed to create checkout session']);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create checkout session'
                ], 500);
            }

            // Update payment with PayMongo data
            $payment->update([
                'payment_intent_id' => $checkoutData['id'],
                'gateway_response' => $checkoutData
            ]);

            // Get checkout URL
            $checkoutUrl = $checkoutData['attributes']['checkout_url'] ?? null;
            
            // Log the response for debugging
            Log::info('PayMongo checkout session created', [
                'payment_id' => $payment->id,
                'checkout_url' => $checkoutUrl,
                'response_structure' => array_keys($checkoutData['attributes'] ?? []),
                'full_response' => $checkoutData
            ]);

            // Validate checkout URL
            if (empty($checkoutUrl)) {
                $payment->update(['status' => 'failed', 'failure_reason' => 'No checkout URL returned from PayMongo']);
                
                return response()->json([
                    'success' => false,
                    'message' => 'PayMongo did not return a valid checkout URL. Please try again or contact support.',
                    'debug_info' => [
                        'response_keys' => array_keys($checkoutData['attributes'] ?? []),
                        'payment_method' => $payment->payment_method
                    ]
                ], 500);
            }

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'checkout_url' => $checkoutUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Payment intent creation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle PayMongo webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            Log::info('PayMongo webhook received', $payload);

            $eventType = $payload['data']['attributes']['type'] ?? null;
            $paymentIntentId = $payload['data']['attributes']['data']['id'] ?? null;

            if (!$paymentIntentId) {
                return response()->json(['success' => false], 400);
            }

            $payment = Payment::where('payment_intent_id', $paymentIntentId)->first();
            
            if (!$payment) {
                Log::warning('Payment not found for webhook', ['payment_intent_id' => $paymentIntentId]);
                return response()->json(['success' => false], 404);
            }

            switch ($eventType) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($payment, $payload);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($payment, $payload);
                    break;
                    
                default:
                    Log::info('Unhandled webhook event type', ['type' => $eventType]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment(Payment $payment, array $webhookData): void
    {
        DB::transaction(function () use ($payment, $webhookData) {
            // Update payment status
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'gateway_response' => $webhookData
            ]);

            // Create or update subscription
            $subscription = Subscription::create([
                'user_id' => $payment->user_id,
                'plan_name' => 'premium',
                'status' => 'active',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'auto_renew' => true
            ]);

            // Update payment with subscription
            $payment->update(['subscription_id' => $subscription->id]);

            // Update user premium status
            $user = User::find($payment->user_id);
            $user->update(['is_premium' => true]);

            Log::info('User upgraded to premium', [
                'user_id' => $payment->user_id,
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id
            ]);
        });
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment(Payment $payment, array $webhookData): void
    {
        $payment->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $webhookData['data']['attributes']['data']['attributes']['last_payment_error']['message'] ?? 'Payment failed',
            'gateway_response' => $webhookData
        ]);

        Log::warning('Payment failed', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'reason' => $payment->failure_reason
        ]);
    }

    /**
     * Create PayMongo checkout session via API (recommended for GCash/PayMaya)
     */
    private function createPayMongoCheckoutSession(int $amountInCentavos, string $paymentMethod, int $paymentId): ?array
    {
        try {
            $url = 'https://api.paymongo.com/v1/checkout_sessions';
            
            // Map payment methods to PayMongo format
            $paymentMethods = [
                'gcash' => 'gcash',
                'paymaya' => 'paymaya', 
                'card' => 'card'
            ];
            
            $mappedMethod = $paymentMethods[$paymentMethod] ?? 'card';
            
            $data = [
                'data' => [
                    'attributes' => [
                        'send_email_receipt' => false,
                        'show_description' => true,
                        'show_line_items' => true,
                        'description' => 'SecureDocs Premium Subscription - Monthly Plan',
                        'cancel_url' => route('premium.cancel'),
                        'success_url' => route('premium.success') . '?payment_id=' . $paymentId,
                        'payment_method_types' => [$mappedMethod],
                        'line_items' => [
                            [
                                'currency' => 'PHP',
                                'amount' => $amountInCentavos,
                                'description' => 'SecureDocs Premium Subscription',
                                'name' => 'Premium Monthly Plan',
                                'quantity' => 1
                            ]
                        ],
                        'metadata' => [
                            'payment_id' => (string)$paymentId,
                            'user_id' => (string)Auth::id()
                        ]
                    ]
                ]
            ];

            $response = $this->makePayMongoRequest($url, 'POST', $data);
            
            return $response['data'] ?? null;

        } catch (\Exception $e) {
            Log::error('PayMongo checkout session creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
                'payment_method' => $paymentMethod,
                'amount' => $amountInCentavos
            ]);
            return null;
        }
    }

    /**
     * Create PayMongo payment intent via API (legacy method)
     */
    private function createPayMongoPaymentIntent(int $amountInCentavos, string $paymentMethod, int $paymentId): ?array
    {
        try {
            $url = 'https://api.paymongo.com/v1/payment_intents';
            
            // Map payment methods to PayMongo format
            $paymentMethods = [
                'gcash' => 'gcash',
                'paymaya' => 'paymaya', 
                'card' => 'card'
            ];
            
            $mappedMethod = $paymentMethods[$paymentMethod] ?? 'card';
            
            // Basic PayMongo payment intent structure
            $data = [
                'data' => [
                    'attributes' => [
                        'amount' => $amountInCentavos,
                        'payment_method_allowed' => [$mappedMethod],
                        'currency' => 'PHP',
                        'description' => 'SecureDocs Premium Subscription',
                        'metadata' => [
                            'payment_id' => (string)$paymentId,
                            'user_id' => (string)Auth::id()
                        ]
                    ]
                ]
            ];
            
            // Add payment method options only for card payments
            if ($mappedMethod === 'card') {
                $data['data']['attributes']['payment_method_options'] = [
                    'card' => [
                        'request_three_d_secure' => 'automatic'
                    ]
                ];
            }

            $response = $this->makePayMongoRequest($url, 'POST', $data);
            
            return $response['data'] ?? null;

        } catch (\Exception $e) {
            Log::error('PayMongo API request failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
                'payment_method' => $paymentMethod,
                'amount' => $amountInCentavos,
                'url' => $url ?? 'unknown'
            ]);
            return null;
        }
    }

    /**
     * Make HTTP request to PayMongo API
     */
    private function makePayMongoRequest(string $url, string $method = 'GET', array $data = null): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->paymongoSecretKey . ':'),
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            // SSL configuration for development
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("CURL error: $curlError");
        }

        if ($httpCode >= 400) {
            Log::error('PayMongo API HTTP Error', [
                'http_code' => $httpCode,
                'response' => $response,
                'url' => $url,
                'method' => $method,
                'data' => $data
            ]);
            throw new \Exception("PayMongo API error: HTTP $httpCode - $response");
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response from PayMongo: " . json_last_error_msg());
        }

        return $decodedResponse ?? [];
    }

    /**
     * Payment success page
     */
    public function success(Request $request)
    {
        $paymentId = $request->get('payment_id');
        $payment = null;
        
        if ($paymentId) {
            $payment = Payment::where('id', $paymentId)
                ->where('user_id', Auth::id())
                ->first();
        }
        
        return view('premium.success', compact('payment'));
    }

    /**
     * Payment cancel page
     */
    public function cancel(Request $request)
    {
        $paymentId = $request->get('payment_id');
        $payment = null;
        
        if ($paymentId) {
            $payment = Payment::where('id', $paymentId)
                ->where('user_id', Auth::id())
                ->first();
                
            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'cancelled']);
            }
        }
        
        return view('premium.cancel', compact('payment'));
    }

    /**
     * Get user's payment history
     */
    public function paymentHistory()
    {
        $user = Auth::user();
        $payments = $user->payments()
            ->with('subscription')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        $subscriptions = $user->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get();
            
        $stats = [
            'total_spent' => $user->payments()->where('status', 'paid')->sum('amount'),
            'total_payments' => $user->payments()->count(),
            'successful_payments' => $user->payments()->where('status', 'paid')->count(),
            'failed_payments' => $user->payments()->where('status', 'failed')->count(),
        ];
        
        return view('premium.payment-history', compact('payments', 'subscriptions', 'stats'));
    }
}
