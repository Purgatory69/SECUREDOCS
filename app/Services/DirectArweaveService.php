<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Exception;

/**
 * Direct Arweave Integration Service
 * Uses Arweave HTTP API directly without Bundlr
 */
class DirectArweaveService
{
    protected string $arweaveHost;
    protected string $privateKey;
    protected string $walletAddress;
    
    public function __construct()
    {
        $this->arweaveHost = config('arweave.arweave_host', 'arweave.net');
        $this->privateKey = config('arweave.arweave_private_key', '');
        $this->walletAddress = config('arweave.arweave_wallet_address', '');
    }
    
    /**
     * Upload file directly to Arweave using HTTP API
     */
    public function uploadFile(UploadedFile $file, array $metadata = []): array
    {
        try {
            Log::info('Direct Arweave upload initiated', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'arweave_host' => $this->arweaveHost
            ]);
            
            // Step 1: Get file content
            $fileContent = file_get_contents($file->getPathname());
            $dataSize = strlen($fileContent);
            
            // Step 2: Get upload price from Arweave
            $price = $this->getUploadPrice($dataSize);
            Log::info('Arweave upload price', ['price' => $price, 'data_size' => $dataSize]);
            
            // Step 3: Check wallet balance
            $balance = $this->getWalletBalance();
            if ($balance < $price) {
                throw new Exception("Insufficient AR balance. Required: {$price} winston, Available: {$balance} winston");
            }
            
            // Step 4: Create transaction
            $transaction = $this->createTransaction($fileContent, $file, $metadata);
            
            // Step 5: Sign transaction (simplified - in production use proper crypto library)
            $signedTransaction = $this->signTransaction($transaction);
            
            // Step 6: Submit to Arweave
            $result = $this->submitTransaction($signedTransaction);
            
            Log::info('Direct Arweave upload successful', [
                'transaction_id' => $result['id'],
                'file_name' => $file->getClientOriginalName()
            ]);
            
            return [
                'transaction_id' => $result['id'],
                'url' => "https://{$this->arweaveHost}/{$result['id']}",
                'gateway_urls' => $this->getGatewayUrls($result['id']),
                'file_info' => [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType()
                ],
                'receipt' => [
                    'method' => 'direct_arweave',
                    'price_paid' => $price,
                    'timestamp' => now()->toISOString(),
                    'status' => 'submitted'
                ]
            ];
            
        } catch (Exception $e) {
            Log::error('Direct Arweave upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get upload price from Arweave
     */
    protected function getUploadPrice(int $dataSize): int
    {
        try {
            $response = Http::timeout(30)->get("https://{$this->arweaveHost}/price/{$dataSize}");
            
            if (!$response->successful()) {
                throw new Exception('Failed to get Arweave pricing');
            }
            
            return (int) $response->body();
            
        } catch (Exception $e) {
            Log::error('Failed to get Arweave price', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Get wallet balance from Arweave
     */
    protected function getWalletBalance(): int
    {
        try {
            if (empty($this->walletAddress)) {
                throw new Exception('Wallet address not configured');
            }
            
            $response = Http::timeout(30)->get("https://{$this->arweaveHost}/wallet/{$this->walletAddress}/balance");
            
            if (!$response->successful()) {
                throw new Exception('Failed to get wallet balance');
            }
            
            return (int) $response->body();
            
        } catch (Exception $e) {
            Log::error('Failed to get wallet balance', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Create Arweave transaction
     */
    protected function createTransaction(string $data, UploadedFile $file, array $metadata): array
    {
        // Get last transaction for wallet
        $lastTx = $this->getLastTransaction();
        
        // Create transaction structure
        $transaction = [
            'format' => 2,
            'id' => '', // Will be set after signing
            'last_tx' => $lastTx,
            'owner' => $this->getPublicKey(), // Derived from private key
            'tags' => $this->prepareTags($file, $metadata),
            'target' => '', // Empty for data upload
            'quantity' => '0', // No AR transfer, just data
            'data' => base64_encode($data),
            'data_size' => (string) strlen($data),
            'data_root' => '', // Will be calculated
            'reward' => (string) $this->getUploadPrice(strlen($data))
        ];
        
        return $transaction;
    }
    
    /**
     * Sign transaction (simplified - use proper crypto library in production)
     */
    protected function signTransaction(array $transaction): array
    {
        // TODO: Implement proper RSA signing with private key
        // This is a simplified version - in production you'd use:
        // - arweave-php library
        // - or implement RSA-PSS signing manually
        
        $transaction['id'] = $this->generateTransactionId();
        $transaction['signature'] = 'mock_signature_' . bin2hex(random_bytes(256));
        
        return $transaction;
    }
    
    /**
     * Submit transaction to Arweave
     */
    protected function submitTransaction(array $transaction): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(60)->post("https://{$this->arweaveHost}/tx", $transaction);
            
            if (!$response->successful()) {
                $error = $response->body();
                throw new Exception("Arweave submission failed: {$error}");
            }
            
            return ['id' => $transaction['id']];
            
        } catch (Exception $e) {
            Log::error('Failed to submit transaction to Arweave', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction['id'] ?? 'unknown'
            ]);
            throw $e;
        }
    }
    
    /**
     * Get last transaction ID for wallet
     */
    protected function getLastTransaction(): string
    {
        try {
            if (empty($this->walletAddress)) {
                return '';
            }
            
            $response = Http::timeout(30)->get("https://{$this->arweaveHost}/wallet/{$this->walletAddress}/last_tx");
            
            if ($response->successful()) {
                return $response->body();
            }
            
            return '';
            
        } catch (Exception $e) {
            Log::warning('Failed to get last transaction', ['error' => $e->getMessage()]);
            return '';
        }
    }
    
    /**
     * Prepare Arweave tags
     */
    protected function prepareTags(UploadedFile $file, array $metadata): array
    {
        $tags = [
            ['name' => 'Content-Type', 'value' => $file->getMimeType()],
            ['name' => 'App-Name', 'value' => 'SecureDocs'],
            ['name' => 'App-Version', 'value' => '1.0.0'],
            ['name' => 'File-Name', 'value' => $file->getClientOriginalName()],
            ['name' => 'Upload-Method', 'value' => 'direct-arweave'],
            ['name' => 'Timestamp', 'value' => now()->toISOString()]
        ];
        
        if (isset($metadata['user_id'])) {
            $tags[] = ['name' => 'User-ID', 'value' => (string) $metadata['user_id']];
        }
        
        return $tags;
    }
    
    /**
     * Get gateway URLs
     */
    protected function getGatewayUrls(string $transactionId): array
    {
        return [
            'primary' => "https://arweave.net/{$transactionId}",
            'ar_io' => "https://ar-io.net/{$transactionId}",
            'gateway_dev' => "https://gateway.ar-io.dev/{$transactionId}",
            'viewblock' => "https://viewblock.io/arweave/tx/{$transactionId}"
        ];
    }
    
    /**
     * Get public key from private key (simplified)
     */
    protected function getPublicKey(): string
    {
        // TODO: Derive public key from private key
        // In production, use proper crypto library
        return 'mock_public_key_' . substr(hash('sha256', $this->privateKey), 0, 32);
    }
    
    /**
     * Generate transaction ID (simplified)
     */
    protected function generateTransactionId(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
    
    /**
     * Check if service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->privateKey) && !empty($this->walletAddress);
    }
}
