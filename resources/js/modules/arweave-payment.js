/**
 * Arweave Payment Module
 * Handles real payments for Arweave uploads with service fees
 */

class ArweavePayment {
    constructor() {
        this.currentFile = null;
        this.pricing = null;
        this.selectedPaymentMethod = null;
        this.walletConnected = false;
        this.userWalletAddress = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Payment modal events
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-arweave-payment]')) {
                const fileId = parseInt(e.target.dataset.arweavePayment);
                this.showPaymentModal(fileId);
            }
        });

        // Payment method selection
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[name="payment-method"]')) {
                this.handlePaymentMethodChange(e.target.value);
            }
        });

        // Process payment button
        document.addEventListener('click', (e) => {
            if (e.target.matches('#processPaymentBtn')) {
                this.processPayment();
            }
        });

        // Wallet connection buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.connect-wallet-btn')) {
                const walletType = e.target.dataset.wallet;
                this.connectWallet(walletType);
            }
        });
    }

    async showPaymentModal(fileId) {
        try {
            // Get file details
            const fileResponse = await fetch(`/files/${fileId}`);
            if (!fileResponse.ok) throw new Error('Failed to fetch file details');
            
            this.currentFile = await fileResponse.json();
            
            // Get pricing
            await this.loadPricing(this.currentFile.file_size);
            
            // Show modal
            this.renderPaymentModal();
            
        } catch (error) {
            console.error('Failed to show payment modal:', error);
            this.showNotification('Failed to load payment information', 'error');
        }
    }

    async loadPricing(fileSize) {
        try {
            const response = await fetch('/arweave/pricing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ file_size: fileSize })
            });

            if (!response.ok) throw new Error('Failed to get pricing');
            
            const data = await response.json();
            this.pricing = data.pricing;
            this.supportedCurrencies = data.supported_currencies;
            
        } catch (error) {
            console.error('Failed to load pricing:', error);
            throw error;
        }
    }

    renderPaymentModal() {
        const modal = document.getElementById('arweavePaymentModal') || this.createPaymentModal();
        
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">
                            💰 Pay for Arweave Storage
                        </h2>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- File Information -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h3 class="font-semibold text-gray-900 mb-2">File Details</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Name:</span>
                                <span class="font-medium">${this.currentFile.file_name}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Size:</span>
                                <span class="font-medium">${this.pricing.file_size_mb} MB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Breakdown -->
                    <div class="bg-blue-50 rounded-lg p-4 mb-6">
                        <h3 class="font-semibold text-blue-900 mb-3">💸 Cost Breakdown</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-blue-700">Arweave Storage Cost:</span>
                                <span class="font-medium">$${this.pricing.base_cost.usd}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-700">Service Fee (${this.pricing.service_fee_percentage}%):</span>
                                <span class="font-medium">$${this.pricing.service_fee_usd}</span>
                            </div>
                            <div class="border-t border-blue-200 pt-2 flex justify-between font-bold text-blue-900">
                                <span>Total Cost:</span>
                                <span>$${this.pricing.total_usd}</span>
                            </div>
                        </div>
                        <p class="text-xs text-blue-600 mt-2">
                            ⏱️ Estimated confirmation: ${this.pricing.estimated_confirmation_time}
                        </p>
                    </div>

                    <!-- Payment Methods -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">💳 Payment Method</h3>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="payment-method" value="crypto" class="mr-3">
                                <div class="flex-1">
                                    <div class="font-medium">Cryptocurrency</div>
                                    <div class="text-sm text-gray-600">Pay with ETH, MATIC, or other supported tokens</div>
                                </div>
                                <div class="text-green-600 font-medium">Available</div>
                            </label>
                            
                            <label class="flex items-center p-3 border rounded-lg opacity-50 cursor-not-allowed">
                                <input type="radio" name="payment-method" value="fiat" disabled class="mr-3">
                                <div class="flex-1">
                                    <div class="font-medium">Credit Card</div>
                                    <div class="text-sm text-gray-600">Pay with credit/debit card (Coming Soon)</div>
                                </div>
                                <div class="text-gray-500 font-medium">Coming Soon</div>
                            </label>
                        </div>
                    </div>

                    <!-- Crypto Payment Options -->
                    <div id="cryptoPaymentOptions" class="hidden mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3">Select Cryptocurrency</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            ${this.renderCryptoOptions()}
                        </div>
                    </div>

                    <!-- Wallet Connection -->
                    <div id="walletConnection" class="hidden mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3">Connect Wallet</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <button class="connect-wallet-btn flex items-center justify-center p-3 border rounded-lg hover:bg-gray-50" data-wallet="metamask">
                                <img src="/metamask-icon.png" alt="MetaMask" class="w-6 h-6 mr-2">
                                MetaMask
                            </button>
                            <button class="connect-wallet-btn flex items-center justify-center p-3 border rounded-lg hover:bg-gray-50" data-wallet="ronin">
                                <img src="/ronin-icon.png" alt="Ronin" class="w-6 h-6 mr-2">
                                Ronin Wallet
                            </button>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div id="paymentSummary" class="hidden bg-green-50 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-green-900 mb-2">Payment Summary</h4>
                        <div id="summaryContent" class="text-sm text-green-800"></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3">
                        <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Cancel
                        </button>
                        <button id="processPaymentBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Process Payment
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    renderCryptoOptions() {
        return Object.entries(this.pricing.payment_options).map(([symbol, option]) => `
            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                <input type="radio" name="crypto-currency" value="${symbol}" class="mr-3">
                <div class="flex-1">
                    <div class="font-medium">${option.name} (${symbol})</div>
                    <div class="text-sm text-gray-600">${option.amount_formatted}</div>
                    <div class="text-xs text-gray-500">≈ $${this.pricing.total_usd}</div>
                </div>
            </label>
        `).join('');
    }

    handlePaymentMethodChange(method) {
        this.selectedPaymentMethod = method;
        
        const cryptoOptions = document.getElementById('cryptoPaymentOptions');
        const walletConnection = document.getElementById('walletConnection');
        
        if (method === 'crypto') {
            cryptoOptions.classList.remove('hidden');
            walletConnection.classList.remove('hidden');
        } else {
            cryptoOptions.classList.add('hidden');
            walletConnection.classList.add('hidden');
        }
        
        this.updatePaymentButton();
    }

    async connectWallet(walletType) {
        try {
            let walletAddress = null;
            
            switch (walletType) {
                case 'metamask':
                    walletAddress = await this.connectMetaMask();
                    break;
                case 'ronin':
                    walletAddress = await this.connectRonin();
                    break;
                default:
                    throw new Error('Unsupported wallet type');
            }
            
            if (walletAddress) {
                this.walletConnected = true;
                this.userWalletAddress = walletAddress;
                this.showNotification(`${walletType} wallet connected: ${walletAddress.substring(0, 6)}...${walletAddress.substring(-4)}`, 'success');
                this.updatePaymentSummary();
                this.updatePaymentButton();
            }
            
        } catch (error) {
            console.error('Wallet connection failed:', error);
            this.showNotification(`Failed to connect ${walletType}: ${error.message}`, 'error');
        }
    }

    async connectMetaMask() {
        if (!window.ethereum) {
            throw new Error('MetaMask is not installed');
        }
        
        const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
        return accounts[0];
    }

    async connectRonin() {
        if (!window.ronin && !window.ethereum) {
            throw new Error('Ronin Wallet is not installed');
        }
        
        const provider = window.ronin || window.ethereum;
        const accounts = await provider.request({ method: 'eth_requestAccounts' });
        return accounts[0];
    }

    updatePaymentSummary() {
        const summaryDiv = document.getElementById('paymentSummary');
        const summaryContent = document.getElementById('summaryContent');
        
        if (this.walletConnected && this.selectedPaymentMethod === 'crypto') {
            const selectedCurrency = document.querySelector('input[name="crypto-currency"]:checked');
            
            if (selectedCurrency) {
                const currencySymbol = selectedCurrency.value;
                const paymentOption = this.pricing.payment_options[currencySymbol];
                
                summaryContent.innerHTML = `
                    <div class="space-y-1">
                        <div>Wallet: ${this.userWalletAddress.substring(0, 10)}...${this.userWalletAddress.substring(-6)}</div>
                        <div>Amount: ${paymentOption.amount_formatted}</div>
                        <div>Total USD: $${this.pricing.total_usd}</div>
                        <div>Network: ${paymentOption.network}</div>
                    </div>
                `;
                
                summaryDiv.classList.remove('hidden');
            }
        } else {
            summaryDiv.classList.add('hidden');
        }
    }

    updatePaymentButton() {
        const paymentBtn = document.getElementById('processPaymentBtn');
        const selectedCurrency = document.querySelector('input[name="crypto-currency"]:checked');
        
        const canProcess = this.selectedPaymentMethod === 'crypto' && 
                          this.walletConnected && 
                          selectedCurrency;
        
        paymentBtn.disabled = !canProcess;
        paymentBtn.textContent = canProcess ? 'Process Payment' : 'Complete Setup Above';
    }

    async processPayment() {
        try {
            const selectedCurrency = document.querySelector('input[name="crypto-currency"]:checked');
            if (!selectedCurrency) {
                throw new Error('Please select a cryptocurrency');
            }

            const currencySymbol = selectedCurrency.value;
            const paymentOption = this.pricing.payment_options[currencySymbol];

            // Show processing state
            const paymentBtn = document.getElementById('processPaymentBtn');
            paymentBtn.textContent = 'Processing...';
            paymentBtn.disabled = true;

            // TODO: Implement actual blockchain transaction
            // For now, we'll simulate a transaction hash
            const mockTxHash = '0x' + Array.from({length: 64}, () => Math.floor(Math.random() * 16).toString(16)).join('');

            const paymentData = {
                file_id: this.currentFile.id,
                payment_method: 'crypto',
                currency: currencySymbol,
                amount: paymentOption.amount,
                amount_usd: this.pricing.total_usd - this.pricing.service_fee_usd,
                service_fee_usd: this.pricing.service_fee_usd,
                wallet_address: this.userWalletAddress,
                transaction_hash: mockTxHash,
            };

            const response = await fetch('/arweave/payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(paymentData)
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessModal(result.data);
                document.querySelector('.fixed').remove(); // Close payment modal
            } else {
                throw new Error(result.message || 'Payment failed');
            }

        } catch (error) {
            console.error('Payment processing failed:', error);
            this.showNotification(`Payment failed: ${error.message}`, 'error');
            
            // Reset button
            const paymentBtn = document.getElementById('processPaymentBtn');
            paymentBtn.textContent = 'Process Payment';
            paymentBtn.disabled = false;
        }
    }

    showSuccessModal(paymentData) {
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <div class="text-center">
                        <div class="text-6xl mb-4">🎉</div>
                        <h2 class="text-2xl font-bold text-green-600 mb-4">Payment Successful!</h2>
                        <div class="bg-green-50 rounded-lg p-4 mb-4 text-left">
                            <h3 class="font-semibold text-green-900 mb-2">Transaction Details</h3>
                            <div class="space-y-1 text-sm text-green-800">
                                <div>Payment ID: ${paymentData.payment_id}</div>
                                <div>Arweave TX: ${paymentData.arweave_tx_id}</div>
                                <div>Total Cost: $${paymentData.total_cost_usd}</div>
                                <div>Service Fee: $${paymentData.service_fee_usd}</div>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-4">
                            Your file is now permanently stored on Arweave blockchain!
                        </p>
                        <div class="flex justify-center space-x-3">
                            <a href="${paymentData.arweave_url}" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                View on Arweave
                            </a>
                            <button onclick="this.closest('.fixed').remove(); location.reload();" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    createPaymentModal() {
        const modal = document.createElement('div');
        modal.id = 'arweavePaymentModal';
        return modal;
    }

    showNotification(message, type = 'info') {
        // Use existing notification system if available
        if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            alert(message);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ArweavePayment();
});

export default ArweavePayment;
