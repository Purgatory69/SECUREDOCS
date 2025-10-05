@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#141326] text-white flex items-center justify-center">
    <div class="max-w-md mx-auto text-center">
        <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-8">
            <!-- Success Icon -->
            <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Success Message -->
            <h1 class="text-2xl font-bold text-white mb-4">
                @if($payment && $payment->status === 'paid')
                    Payment Successful!
                @elseif($verificationError ?? false)
                    Payment Verification Issue
                @else
                    Payment Processing
                @endif
            </h1>
            <p class="text-gray-400 mb-6">
                @if($payment && $payment->status === 'paid')
                    Welcome to SecureDocs Premium! Your account has been upgraded successfully.
                @elseif($verificationError ?? false)
                    {{ $verificationError }}
                @else
                    Your payment is being processed. This may take a few moments.
                @endif
            </p>

            @if($payment)
            <div class="bg-[#2A2D47] rounded-lg p-4 mb-6">
                <div class="text-left space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Amount:</span>
                        <span class="text-white font-medium">{{ $payment->formatted_amount }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Payment Method:</span>
                        <span class="text-white">{{ $payment->payment_method_display }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Date:</span>
                        <span class="text-white">{{ $payment->paid_at?->format('M d, Y') ?? 'Just now' }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Premium Features -->
            <div class="text-left mb-6">
                <h3 class="text-lg font-semibold text-white mb-3">You now have access to:</h3>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center text-green-400">
                        <span class="mr-2">✓</span>
                        Unlimited document uploads
                    </li>
                    <li class="flex items-center text-green-400">
                        <span class="mr-2">✓</span>
                        Blockchain document storage
                    </li>
                    <li class="flex items-center text-green-400">
                        <span class="mr-2">✓</span>
                        AI-powered document analysis
                    </li>
                    <li class="flex items-center text-green-400">
                        <span class="mr-2">✓</span>
                        Priority customer support
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <a href="{{ route('user.dashboard') }}" 
                   class="w-full bg-[#f89c00] hover:bg-[#e88900] text-white py-3 px-6 rounded-lg font-bold transition-colors inline-block">
                    Go to Dashboard
                </a>
                <a href="{{ route('premium.payment-history') }}" 
                   class="w-full bg-[#3C3F58] hover:bg-[#4A4D6A] text-white py-3 px-6 rounded-lg transition-colors inline-block">
                    View Payment History
                </a>
            </div>
        </div>

        <!-- Receipt Notice -->
        <p class="text-gray-500 text-sm mt-4">
            A receipt has been sent to your email address.
        </p>
    </div>
</div>
@endsection
