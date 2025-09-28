<?php

namespace App\Services\BlockchainStorage;

use App\Services\ArweaveService;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class ArweaveStorageService implements BlockchainStorageInterface
{
    protected ArweaveService $arweaveService;

    public function __construct()
    {
        $this->arweaveService = new ArweaveService();
    }

    /**
     * Upload a file to Arweave
     */
    public function uploadFile(UploadedFile $file, array $metadata = []): array
    {
        try {
            Log::info('ArweaveStorageService: Starting file upload', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'metadata' => $metadata
            ]);

            // Create a temporary File model for the upload process
            $tempFile = new File([
                'user_id' => Auth::id(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => (string) $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_path' => 'temp/' . uniqid() . '_' . $file->getClientOriginalName(),
            ]);

            // Store the uploaded file temporarily
            $tempPath = $file->store('temp');
            $tempFile->file_path = $tempPath;
            $tempFile->save();

            // Upload to Arweave
            $result = $this->arweaveService->uploadFile($tempFile, Auth::id());

            if ($result['success']) {
                // Update the file record with Arweave data
                $tempFile->update([
                    'arweave_tx_id' => $result['tx_id'],
                    'arweave_url' => $result['url'],
                    'storage_provider' => 'arweave',
                    'is_blockchain_stored' => true,
                    'blockchain_provider' => 'arweave',
                    'blockchain_url' => $result['url'],
                ]);

                return [
                    'success' => true,
                    'tx_id' => $result['tx_id'],
                    'arweave_url' => $result['url'],
                    'cost' => $result['cost'],
                    'file_id' => $tempFile->id,
                    'provider' => 'arweave',
                    'estimated_confirmation' => $result['estimated_confirmation'] ?? '5-15 minutes'
                ];
            }

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Upload failed'
            ];

        } catch (Exception $e) {
            Log::error('ArweaveStorageService: Upload failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get file content from Arweave using transaction ID
     */
    public function getFile(string $txId): ?string
    {
        try {
            return $this->arweaveService->getArweaveFile($txId);
        } catch (Exception $e) {
            Log::error('ArweaveStorageService: Failed to get file', [
                'tx_id' => $txId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Arweave files are permanent and cannot be unpinned
     */
    public function unpinFile(string $txId): bool
    {
        Log::info('ArweaveStorageService: Unpin requested but Arweave files are permanent', [
            'tx_id' => $txId
        ]);
        
        // Arweave files are permanent and cannot be removed
        // Return true to indicate the operation completed (even though no action was taken)
        return true;
    }

    /**
     * Test connection to Arweave network
     */
    public function testConnection(): array
    {
        try {
            // Test by getting current AR price
            $cost = $this->arweaveService->calculateUploadCost(1024); // 1KB test
            
            return [
                'success' => true,
                'message' => 'Arweave connection successful',
                'test_cost_1kb' => $cost,
                'gateway_url' => config('blockchain.providers.arweave.gateway_url')
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get gateway URL for an Arweave transaction
     */
    public function getGatewayUrl(string $txId): string
    {
        $gatewayUrl = config('blockchain.providers.arweave.gateway_url', 'https://arweave.net');
        return $gatewayUrl . '/' . $txId;
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'arweave';
    }

    /**
     * Check if Arweave is properly configured
     */
    public function isConfigured(): bool
    {
        $config = config('blockchain.providers.arweave');
        
        return !empty($config) && 
               ($config['enabled'] ?? false) && 
               !empty($config['gateway_url']);
    }

    /**
     * Get maximum file size for Arweave
     */
    public function getMaxFileSize(): int
    {
        return config('blockchain.providers.arweave.max_file_size', 104857600); // 100MB default
    }
}
