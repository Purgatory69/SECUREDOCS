/**
 * Bundlr Integration for Real Arweave Uploads
 * Handles client-side Bundlr operations for permanent storage
 */

import { WebBundlr } from '@bundlr-network/client';

class BundlrIntegration {
    constructor() {
        this.bundlr = null;
        this.isInitialized = false;
    }

    /**
     * Initialize Bundlr with user's wallet
     */
    async initialize(walletType = 'metamask') {
        try {
            console.log('🔧 Initializing Bundlr integration...');
            
            let provider;
            
            switch (walletType) {
                case 'metamask':
                    if (!window.ethereum) {
                        throw new Error('MetaMask not found');
                    }
                    provider = window.ethereum;
                    break;
                    
                case 'ronin':
                    if (!window.ronin) {
                        throw new Error('Ronin Wallet not found');
                    }
                    provider = window.ronin;
                    break;
                    
                default:
                    throw new Error('Unsupported wallet type');
            }

            // Initialize Bundlr with the wallet provider
            this.bundlr = new WebBundlr(
                'https://node1.bundlr.network', // Bundlr node URL
                'matic', // Currency (Polygon MATIC)
                provider
            );

            await this.bundlr.ready();
            this.isInitialized = true;
            
            console.log('✅ Bundlr initialized successfully');
            return true;
            
        } catch (error) {
            console.error('❌ Bundlr initialization failed:', error);
            throw new Error(`Bundlr initialization failed: ${error.message}`);
        }
    }

    /**
     * Check Bundlr balance
     */
    async getBalance() {
        if (!this.isInitialized) {
            throw new Error('Bundlr not initialized');
        }

        try {
            const balance = await this.bundlr.getLoadedBalance();
            const balanceInMatic = this.bundlr.utils.unitConverter(balance);
            
            console.log('💰 Bundlr balance:', balanceInMatic, 'MATIC');
            return {
                raw: balance.toString(),
                formatted: balanceInMatic,
                currency: 'MATIC'
            };
        } catch (error) {
            console.error('Failed to get Bundlr balance:', error);
            throw error;
        }
    }

    /**
     * Fund Bundlr account if needed
     */
    async fundAccount(amount) {
        if (!this.isInitialized) {
            throw new Error('Bundlr not initialized');
        }

        try {
            console.log('💳 Funding Bundlr account with', amount, 'MATIC...');
            
            const fundTx = await this.bundlr.fund(
                this.bundlr.utils.parseUnits(amount.toString(), 18)
            );
            
            console.log('✅ Bundlr account funded:', fundTx);
            return fundTx;
            
        } catch (error) {
            console.error('❌ Failed to fund Bundlr account:', error);
            throw error;
        }
    }

    /**
     * Upload file to Arweave via Bundlr
     */
    async uploadFile(fileContent, metadata = {}) {
        if (!this.isInitialized) {
            throw new Error('Bundlr not initialized');
        }

        try {
            console.log('🚀 Starting Arweave upload via Bundlr...');
            
            // Prepare tags
            const tags = [
                { name: 'Content-Type', value: metadata.mimeType || 'application/octet-stream' },
                { name: 'App-Name', value: 'SecureDocs' },
                { name: 'App-Version', value: '1.0.0' },
                { name: 'File-Name', value: metadata.fileName || 'unknown' },
                { name: 'Upload-Timestamp', value: new Date().toISOString() }
            ];

            // Add user metadata
            if (metadata.userId) {
                tags.push({ name: 'User-ID', value: metadata.userId.toString() });
            }
            
            if (metadata.paymentId) {
                tags.push({ name: 'Payment-ID', value: metadata.paymentId });
            }

            // Create data item
            const dataItem = this.bundlr.createTransaction(fileContent, { tags });
            
            // Sign the transaction
            await dataItem.sign();
            
            // Upload to Arweave
            const response = await this.bundlr.upload(dataItem);
            
            console.log('✅ File uploaded to Arweave:', response.id);
            
            return {
                transactionId: response.id,
                url: `https://arweave.net/${response.id}`,
                gatewayUrls: {
                    primary: `https://arweave.net/${response.id}`,
                    ar_io: `https://ar-io.net/${response.id}`,
                    gateway_dev: `https://gateway.ar-io.dev/${response.id}`,
                    viewblock: `https://viewblock.io/arweave/tx/${response.id}`
                },
                receipt: {
                    bundler: 'bundlr',
                    timestamp: new Date().toISOString(),
                    status: 'confirmed'
                }
            };
            
        } catch (error) {
            console.error('❌ Arweave upload failed:', error);
            throw new Error(`Arweave upload failed: ${error.message}`);
        }
    }

    /**
     * Get upload cost estimate
     */
    async getUploadCost(dataSize) {
        if (!this.isInitialized) {
            throw new Error('Bundlr not initialized');
        }

        try {
            const cost = await this.bundlr.getPrice(dataSize);
            const costInMatic = this.bundlr.utils.unitConverter(cost);
            
            return {
                raw: cost.toString(),
                formatted: costInMatic,
                currency: 'MATIC',
                dataSize: dataSize
            };
        } catch (error) {
            console.error('Failed to get upload cost:', error);
            throw error;
        }
    }

    /**
     * Check if sufficient balance for upload
     */
    async hasSufficientBalance(dataSize) {
        try {
            const balance = await this.getBalance();
            const cost = await this.getUploadCost(dataSize);
            
            return parseFloat(balance.formatted) >= parseFloat(cost.formatted);
        } catch (error) {
            console.error('Failed to check balance:', error);
            return false;
        }
    }
}

// Export for use in other modules
export default BundlrIntegration;
