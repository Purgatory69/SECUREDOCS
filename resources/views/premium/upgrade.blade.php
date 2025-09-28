@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#141326] text-white">
    <!-- Header -->
    <div class="bg-[#1F2235] border-b border-[#4A4D6A] px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Upgrade to Premium</h1>
                <p class="text-gray-400 mt-1">Unlock advanced features and enhanced security</p>
            </div>
            <a href="{{ route('user.dashboard') }}" class="bg-[#3C3F58] hover:bg-[#4A4D6A] text-white px-4 py-2 rounded-lg transition-colors">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="max-w-4xl mx-auto">
            
            <!-- Current Plan Status -->
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Current Plan</h3>
                        <p class="text-gray-400">
                            @if(auth()->user()->is_premium)
                                <span class="text-green-400">✓ Premium Active</span>
                            @else
                                <span class="text-yellow-400">Basic Plan</span>
                            @endif
                        </p>
                    </div>
                    @if(auth()->user()->is_premium)
                        <div class="text-right">
                            <p class="text-sm text-gray-400">Next billing</p>
                            <p class="text-white font-medium">{{ $subscription->ends_at ?? 'N/A' }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pricing Plans -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                
                <!-- Basic Plan -->
                <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">Basic Plan</h3>
                        <div class="text-3xl font-bold text-white mb-1">Free</div>
                        <p class="text-gray-400">Forever</p>
                    </div>
                    
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center text-gray-300">
                            <span class="text-green-400 mr-3">✓</span>
                            Upload up to 500 mb worth of storage
                        </li>
                        <li class="flex items-center text-gray-300">
                            <span class="text-green-400 mr-3">✓</span>
                            Standard security
                        </li>
                        <li class="flex items-center text-gray-300">
                            <span class="text-red-400 mr-3">✗</span>
                            <span class="line-through">Blockchain storage</span>
                        </li>
                        <li class="flex items-center text-gray-300">
                            <span class="text-red-400 mr-3">✗</span>
                            <span class="line-through">AI document analysis</span>
                        </li>
                    </ul>
                    
                    @if(!auth()->user()->is_premium)
                        <button class="w-full bg-gray-600 text-gray-400 py-3 rounded-lg cursor-not-allowed">
                            Current Plan
                        </button>
                    @else
                        <button class="w-full bg-gray-600 text-gray-400 py-3 rounded-lg cursor-not-allowed">
                            Downgrade Available
                        </button>
                    @endif
                </div>

                <!-- Premium Plan -->
                <div class="bg-gradient-to-br from-[#f89c00] to-[#ff8c00] rounded-xl p-6 relative">
                    <div class="absolute top-4 right-4">
                        <span class="bg-white text-[#f89c00] px-3 py-1 rounded-full text-sm font-bold">POPULAR</span>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold text-white mb-2">Premium Plan</h3>
                        <div class="text-3xl font-bold text-white mb-1">₱299</div>
                        <p class="text-white/80">per month</p>
                    </div>
                    
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center text-white">
                            <span class="text-white mr-3">✓</span>
                            Unlimited document uploads
                        </li>
                        <li class="flex items-center text-white">
                            <span class="text-white mr-3">✓</span>
                            Enhanced security features
                        </li>
                        <li class="flex items-center text-white">
                            <span class="text-white mr-3">✓</span>
                            Blockchain document storage
                        </li>
                        <li class="flex items-center text-white">
                            <span class="text-white mr-3">✓</span>
                            AI-powered document analysis
                        </li>
                        <li class="flex items-center text-white">
                            <span class="text-white mr-3">✓</span>
                            Priority customer support
                        </li>
                    </ul>
                    
                    @if(!auth()->user()->is_premium)
                        <button id="upgradeBtn" class="w-full bg-white text-[#f89c00] py-3 rounded-lg font-bold hover:bg-gray-100 transition-colors">
                            Upgrade Now
                        </button>
                    @else
                        <button class="w-full bg-white/20 text-white py-3 rounded-lg cursor-not-allowed">
                            ✓ Current Plan
                        </button>
                    @endif
                </div>
            </div>

            <!-- Payment Methods -->
            @if(!auth()->user()->is_premium)
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Choose Payment Method</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- GCash -->
                    <label class="payment-method cursor-pointer">
                        <input type="radio" name="payment_method" value="gcash" class="hidden">
                        <div class="border-2 border-[#4A4D6A] rounded-lg p-4 text-center hover:border-[#f89c00] transition-colors">
                            <div class="text-2xl mb-2">📱</div>
                            <div class="text-white font-medium">GCash</div>
                            <div class="text-gray-400 text-sm">E-wallet</div>
                        </div>
                    </label>

                    <!-- PayMaya -->
                    <label class="payment-method cursor-pointer">
                        <input type="radio" name="payment_method" value="paymaya" class="hidden">
                        <div class="border-2 border-[#4A4D6A] rounded-lg p-4 text-center hover:border-[#f89c00] transition-colors">
                            <div class="text-2xl mb-2">💳</div>
                            <div class="text-white font-medium">PayMaya</div>
                            <div class="text-gray-400 text-sm">E-wallet</div>
                        </div>
                    </label>

                    <!-- Credit/Debit Card -->
                    <label class="payment-method cursor-pointer">
                        <input type="radio" name="payment_method" value="card" class="hidden">
                        <div class="border-2 border-[#4A4D6A] rounded-lg p-4 text-center hover:border-[#f89c00] transition-colors">
                            <div class="text-2xl mb-2">💳</div>
                            <div class="text-white font-medium">Card</div>
                            <div class="text-gray-400 text-sm">Visa, Mastercard</div>
                        </div>
                    </label>
                </div>

                <!-- Proceed Button -->
                <div class="mt-6 text-center">
                    <button id="proceedPayment" class="bg-[#f89c00] hover:bg-[#e88900] text-white px-8 py-3 rounded-lg font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Proceed to Payment
                    </button>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 p-4 bg-[#2A2D47] rounded-lg">
                    <div class="flex items-start">
                        <span class="text-green-400 mr-3 mt-1">🔒</span>
                        <div>
                            <h4 class="text-white font-medium mb-1">Secure Payment</h4>
                            <p class="text-gray-400 text-sm">
                                Your payment is processed securely through PayMongo. We never store your payment information.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    const proceedBtn = document.getElementById('proceedPayment');
    const upgradeBtn = document.getElementById('upgradeBtn');
    
    // Handle payment method selection
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            paymentMethods.forEach(m => {
                m.querySelector('div').classList.remove('border-[#f89c00]', 'bg-[#f89c00]/10');
                m.querySelector('div').classList.add('border-[#4A4D6A]');
            });
            
            // Add selected class to clicked method
            const div = this.querySelector('div');
            div.classList.remove('border-[#4A4D6A]');
            div.classList.add('border-[#f89c00]', 'bg-[#f89c00]/10');
            
            // Check the radio button
            this.querySelector('input').checked = true;
            
            // Enable proceed button
            proceedBtn.disabled = false;
        });
    });
    
    // Handle upgrade button click
    if (upgradeBtn) {
        upgradeBtn.addEventListener('click', function() {
            document.querySelector('.payment-method').scrollIntoView({ 
                behavior: 'smooth' 
            });
        });
    }
    
    // Handle proceed to payment
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethod) {
                alert('Please select a payment method');
                return;
            }
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = 'Processing...';
            
            // Create payment intent with PayMongo
            fetch('/premium/create-payment-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    payment_method: selectedMethod.value,
                    plan: 'premium_monthly',
                    amount: 299
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to PayMongo checkout
                    window.location.href = data.checkout_url;
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = 'Proceed to Payment';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                this.disabled = false;
                this.innerHTML = 'Proceed to Payment';
            });
        });
    }
});
</script>
@endpush
@endsection
