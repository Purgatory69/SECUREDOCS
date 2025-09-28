@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#141326] text-white flex items-center justify-center">
    <div class="max-w-md mx-auto text-center">
        <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-8">
            <!-- Cancel Icon -->
            <div class="w-20 h-20 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>

            <!-- Cancel Message -->
            <h1 class="text-2xl font-bold text-white mb-4">Payment Cancelled</h1>
            <p class="text-gray-400 mb-6">
                Your payment was cancelled. No charges have been made to your account.
            </p>

            @if($payment && $payment->status === 'cancelled')
            <div class="bg-[#2A2D47] rounded-lg p-4 mb-6">
                <div class="text-left space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Amount:</span>
                        <span class="text-white font-medium">{{ $payment->formatted_amount }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Status:</span>
                        <span class="text-yellow-400">Cancelled</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Date:</span>
                        <span class="text-white">{{ $payment->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Why Upgrade -->
            <div class="text-left mb-6">
                <h3 class="text-lg font-semibold text-white mb-3">Why upgrade to Premium?</h3>
                <ul class="space-y-2 text-sm text-gray-300">
                    <li class="flex items-center">
                        <span class="text-[#f89c00] mr-2">★</span>
                        Unlimited document uploads
                    </li>
                    <li class="flex items-center">
                        <span class="text-[#f89c00] mr-2">★</span>
                        Blockchain security & immutability
                    </li>
                    <li class="flex items-center">
                        <span class="text-[#f89c00] mr-2">★</span>
                        AI-powered document insights
                    </li>
                    <li class="flex items-center">
                        <span class="text-[#f89c00] mr-2">★</span>
                        Priority customer support
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <a href="{{ route('premium.upgrade') }}" 
                   class="w-full bg-[#f89c00] hover:bg-[#e88900] text-white py-3 px-6 rounded-lg font-bold transition-colors inline-block">
                    Try Again
                </a>
                <a href="{{ route('user.dashboard') }}" 
                   class="w-full bg-[#3C3F58] hover:bg-[#4A4D6A] text-white py-3 px-6 rounded-lg transition-colors inline-block">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Support Notice -->
        <p class="text-gray-500 text-sm mt-4">
            Need help? <a href="mailto:support@securedocs.com" class="text-[#f89c00] hover:underline">Contact Support</a>
        </p>
    </div>
</div>
@endsection
