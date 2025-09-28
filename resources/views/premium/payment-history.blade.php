@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#141326] text-white p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Payment History</h1>
                    <p class="text-gray-400 mt-2">Track your premium subscription payments and billing history</p>
                </div>
                <a href="{{ route('user.dashboard') }}" 
                   class="bg-[#3C3F58] hover:bg-[#4A4D6A] text-white px-6 py-3 rounded-lg transition-colors">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <span class="text-xl">💰</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400 text-sm">Total Spent</p>
                        <p class="text-2xl font-bold text-white">₱{{ number_format($stats['total_spent'], 2) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <span class="text-xl">📊</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400 text-sm">Total Payments</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_payments'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <span class="text-xl">✅</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400 text-sm">Successful</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['successful_payments'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                        <span class="text-xl">❌</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400 text-sm">Failed</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['failed_payments'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-[#4A4D6A]">
                <nav class="-mb-px flex space-x-8">
                    <button class="tab-btn active py-2 px-1 border-b-2 border-[#f89c00] text-[#f89c00] font-medium text-sm" data-tab="payments">
                        Payment History
                    </button>
                    <button class="tab-btn py-2 px-1 border-b-2 border-transparent text-gray-400 hover:text-gray-300 font-medium text-sm" data-tab="subscriptions">
                        Subscriptions
                    </button>
                </nav>
            </div>
        </div>

        <!-- Payment History Tab -->
        <div id="payments-tab" class="tab-content">
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl overflow-hidden">
                @if($payments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-[#2A2D47]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Payment ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Method</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#4A4D6A]">
                                @foreach($payments as $payment)
                                <tr class="hover:bg-[#2A2D47] transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white font-mono">#{{ $payment->id }}</div>
                                        @if($payment->gateway_payment_id)
                                            <div class="text-xs text-gray-400">{{ substr($payment->gateway_payment_id, 0, 20) }}...</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">{{ $payment->formatted_amount }}</div>
                                        <div class="text-xs text-gray-400">{{ $payment->currency }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white">{{ $payment->payment_method_display }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($payment->status === 'paid') bg-green-100 text-green-800
                                            @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($payment->status === 'failed') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        <div>{{ $payment->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs">{{ $payment->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($payment->gateway_response)
                                            <button onclick="showPaymentDetails({{ $payment->id }})" 
                                                    class="text-[#f89c00] hover:text-[#e88900] font-medium">
                                                View Details
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 bg-[#2A2D47]">
                        {{ $payments->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">💳</span>
                        </div>
                        <h3 class="text-lg font-medium text-white mb-2">No Payment History</h3>
                        <p class="text-gray-400 mb-6">You haven't made any payments yet.</p>
                        <a href="{{ route('premium.upgrade') }}" 
                           class="bg-[#f89c00] hover:bg-[#e88900] text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            Upgrade to Premium
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Subscriptions Tab -->
        <div id="subscriptions-tab" class="tab-content hidden">
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl overflow-hidden">
                @if($subscriptions->count() > 0)
                    <div class="p-6">
                        @foreach($subscriptions as $subscription)
                        <div class="border border-[#4A4D6A] rounded-lg p-6 mb-4 last:mb-0">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">{{ $subscription->plan_display }} Plan</h3>
                                    <p class="text-gray-400">{{ $subscription->billing_cycle_display }} billing</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-white">{{ $subscription->formatted_amount }}</div>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $subscription->status_color }}">
                                        {{ $subscription->status_icon }} {{ ucfirst($subscription->status) }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-400">Started</p>
                                    <p class="text-white">{{ $subscription->starts_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">{{ $subscription->status === 'active' ? 'Renews' : 'Ended' }}</p>
                                    <p class="text-white">{{ $subscription->ends_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Auto Renew</p>
                                    <p class="text-white">{{ $subscription->auto_renew ? 'Yes' : 'No' }}</p>
                                </div>
                            </div>
                            
                            @if($subscription->status === 'active')
                            <div class="mt-4 pt-4 border-t border-[#4A4D6A]">
                                <p class="text-sm text-gray-400">
                                    {{ $subscription->days_until_expiration }} days remaining
                                </p>
                                <div class="w-full bg-[#3C3F58] rounded-full h-2 mt-2">
                                    <div class="bg-[#f89c00] h-2 rounded-full" 
                                         style="width: {{ max(0, min(100, ($subscription->days_until_expiration / 30) * 100)) }}%"></div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">📋</span>
                        </div>
                        <h3 class="text-lg font-medium text-white mb-2">No Subscriptions</h3>
                        <p class="text-gray-400 mb-6">You don't have any active subscriptions.</p>
                        <a href="{{ route('premium.upgrade') }}" 
                           class="bg-[#f89c00] hover:bg-[#e88900] text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            Subscribe to Premium
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div id="paymentDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6 max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white">Payment Details</h3>
            <button onclick="closePaymentDetails()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="paymentDetailsContent" class="text-gray-300">
            <!-- Payment details will be loaded here -->
        </div>
    </div>
</div>

<script>
// Tab functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.dataset.tab;
        
        // Update active tab button
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active', 'border-[#f89c00]', 'text-[#f89c00]');
            b.classList.add('border-transparent', 'text-gray-400');
        });
        this.classList.add('active', 'border-[#f89c00]', 'text-[#f89c00]');
        this.classList.remove('border-transparent', 'text-gray-400');
        
        // Show/hide tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        document.getElementById(tab + '-tab').classList.remove('hidden');
    });
});

// Payment details modal
function showPaymentDetails(paymentId) {
    // You can fetch payment details via AJAX here
    document.getElementById('paymentDetailsModal').classList.remove('hidden');
    document.getElementById('paymentDetailsModal').classList.add('flex');
}

function closePaymentDetails() {
    document.getElementById('paymentDetailsModal').classList.add('hidden');
    document.getElementById('paymentDetailsModal').classList.remove('flex');
}
</script>
@endsection
