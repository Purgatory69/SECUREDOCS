<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class VerifyPaymentSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $paymentId = $request->get('payment_id');
        $userId = Auth::id();
        
        // If no payment_id is provided, redirect to upgrade page
        if (!$paymentId) {
            Log::warning('Payment verification failed: No payment ID provided', [
                'user_id' => $userId,
                'url' => $request->fullUrl()
            ]);
            return redirect()->route('premium.upgrade')
                ->with('error', 'Invalid payment session. Please start a new payment.');
        }
        
        // Validate payment_id is numeric to prevent injection
        if (!is_numeric($paymentId)) {
            Log::warning('Payment verification failed: Invalid payment ID format', [
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'url' => $request->fullUrl()
            ]);
            return redirect()->route('premium.upgrade')
                ->with('error', 'Invalid payment session. Please start a new payment.');
        }
        
        // Get the payment record
        $payment = Payment::where('id', (int)$paymentId)
            ->where('user_id', $userId)
            ->first();
        
        // If payment doesn't exist or doesn't belong to user, redirect
        if (!$payment) {
            Log::warning('Payment verification failed: Invalid payment ID or not owned by user', [
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'url' => $request->fullUrl()
            ]);
            return redirect()->route('premium.upgrade')
                ->with('error', 'Invalid payment session. Please start a new payment.');
        }
        
        // Check if payment session is too old (older than 24 hours)
        if ($payment->created_at->diffInHours(now()) > 24) {
            Log::warning('Access to expired payment session', [
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'created_at' => $payment->created_at,
                'url' => $request->fullUrl()
            ]);
            return redirect()->route('premium.upgrade')
                ->with('error', 'This payment session has expired. Please start a new payment.');
        }
        
        // For success page, ensure payment is either pending or completed
        if ($request->routeIs('premium.success') && !in_array($payment->status, ['pending', 'completed'])) {
            Log::warning('Access to success page with invalid payment status', [
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'status' => $payment->status,
                'url' => $request->fullUrl()
            ]);
            return redirect()->route('premium.upgrade')
                ->with('error', 'This payment session is no longer valid.');
        }
        
        // For cancel page, allow pending, failed, or cancelled status
        if ($request->routeIs('premium.cancel') && !in_array($payment->status, ['pending', 'failed', 'cancelled'])) {
            Log::warning('Access to cancel page with invalid payment status', [
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'status' => $payment->status,
                'url' => $request->fullUrl()
            ]);
            return redirect()->route('premium.upgrade')
                ->with('error', 'This payment session is no longer valid.');
        }
        
        // Add payment to the request for use in the controller
        $request->attributes->add(['payment' => $payment]);
        
        return $next($request);
    }
}
