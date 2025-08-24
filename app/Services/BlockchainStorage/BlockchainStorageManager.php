<?php

namespace App\Services\BlockchainStorage;

use App\Models\File;
use App\Models\User;
use App\Models\BlockchainUpload;
use App\Models\BlockchainConfig;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class BlockchainStorageManager
{
    protected array $providers = [];

    public function __construct()
    {
        // Register available providers
        if (config('blockchain.providers.pinata.enabled')) {
            $this->providers['pinata'] = PinataService::class;
        }
        
        // Future providers can be added here
        // if (config('blockchain.providers.filecoin.enabled')) {
        //     $this->providers['filecoin'] = FilecoinService::class;
        // }
    }

    /**
     * Get a blockchain storage provider instance
     */
    public function provider(string $name = null): BlockchainStorageInterface
    {
        $providerName = $name ?: config('blockchain.default', 'pinata');

        if (!isset($this->providers[$providerName])) {
            throw new Exception("Blockchain storage provider '{$providerName}' is not available or not enabled.");
        }

        $providerClass = $this->providers[$providerName];
        $provider = new $providerClass();

        if (!$provider->isConfigured()) {
            throw new Exception("Blockchain storage provider '{$providerName}' is not properly configured.");
        }

        return $provider;
    }

    /**
     * Upload a file to blockchain storage with full tracking
     */
    public function uploadFileWithTracking(File $file, UploadedFile $uploadedFile, User $user, string $provider = null): array
    {
        $providerName = $provider ?: config('blockchain.default', 'pinata');
        
        try {
            // Check if user has premium access
            if (!$this->userHasPremiumAccess($user)) {
                throw new Exception('Premium subscription required for blockchain storage.');
            }

            // Check file size limits
            $providerInstance = $this->provider($providerName);
            if ($uploadedFile->getSize() > $providerInstance->getMaxFileSize()) {
                throw new Exception('File size exceeds the maximum limit for blockchain storage.');
            }

            // Create blockchain upload record
            $blockchainUpload = BlockchainUpload::create([
                'file_id' => $file->id,
                'provider' => $providerName,
                'upload_status' => 'pending'
            ]);

            Log::info('Starting blockchain upload', [
                'file_id' => $file->id,
                'provider' => $providerName,
                'user_id' => $user->id,
                'upload_id' => $blockchainUpload->id
            ]);

            // Prepare metadata
            $metadata = [
                'name' => $file->file_name,
                'keyvalues' => [
                    'user_id' => $user->id,
                    'file_id' => $file->id,
                    'original_filename' => $file->file_name,
                    'upload_id' => $blockchainUpload->id,
                ]
            ];

            // Upload to blockchain storage
            $result = $providerInstance->uploadFile($uploadedFile, $metadata);

            if ($result['success']) {
                // Update blockchain upload record as successful
                $blockchainUpload->markAsSuccessful(
                    $result['ipfs_hash'], 
                    $result['raw_response'] ?? null
                );

                // Update the file record with blockchain information
                $file->update([
                    'blockchain_provider' => $providerName,
                    'ipfs_hash' => $result['ipfs_hash'],
                    'blockchain_url' => $result['gateway_url'],
                    'is_blockchain_stored' => true,
                    'blockchain_metadata' => [
                        'provider' => $providerName,
                        'pin_size' => $result['pin_size'] ?? null,
                        'upload_timestamp' => $result['timestamp'] ?? now()->toIso8601String(),
                        'gateway_url' => $result['gateway_url'],
                    ]
                ]);

                Log::info('Blockchain upload completed successfully', [
                    'file_id' => $file->id,
                    'ipfs_hash' => $result['ipfs_hash'],
                    'provider' => $providerName
                ]);

                return [
                    'success' => true,
                    'ipfs_hash' => $result['ipfs_hash'],
                    'gateway_url' => $result['gateway_url'],
                    'provider' => $providerName,
                    'upload_id' => $blockchainUpload->id,
                ];

            } else {
                // Mark upload as failed
                $blockchainUpload->markAsFailed(
                    $result['error'] ?? 'Unknown upload error',
                    $result
                );

                Log::error('Blockchain upload failed', [
                    'file_id' => $file->id,
                    'provider' => $providerName,
                    'error' => $result['error'] ?? 'Unknown error',
                    'upload_id' => $blockchainUpload->id
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Upload failed',
                    'provider' => $providerName,
                    'upload_id' => $blockchainUpload->id,
                ];
            }

        } catch (Exception $e) {
            // Update upload record if it exists
            if (isset($blockchainUpload)) {
                $blockchainUpload->markAsFailed($e->getMessage());
            }

            Log::error('Blockchain upload exception', [
                'file_id' => $file->id,
                'provider' => $providerName,
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $providerName,
            ];
        }
    }

    /**
     * Remove a file from blockchain storage
     */
    public function removeFile(File $file): bool
    {
        if (!$file->isStoredOnBlockchain()) {
            return false;
        }

        try {
            $provider = $this->provider($file->blockchain_provider);
            $result = $provider->unpinFile($file->ipfs_hash);

            if ($result) {
                // Update file record
                $file->update([
                    'blockchain_provider' => null,
                    'ipfs_hash' => null,
                    'blockchain_url' => null,
                    'is_blockchain_stored' => false,
                    'blockchain_metadata' => null,
                ]);

                Log::info('File removed from blockchain storage', [
                    'file_id' => $file->id,
                    'ipfs_hash' => $file->ipfs_hash
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to remove file from blockchain', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Test connection to a provider
     */
    public function testProvider(string $providerName): array
    {
        try {
            $provider = $this->provider($providerName);
            return $provider->testConnection();
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        $available = [];

        foreach ($this->providers as $name => $class) {
            try {
                $provider = new $class();
                $available[$name] = [
                    'name' => $name,
                    'class' => $class,
                    'configured' => $provider->isConfigured(),
                    'max_file_size' => $provider->getMaxFileSize(),
                ];
            } catch (Exception $e) {
                $available[$name] = [
                    'name' => $name,
                    'class' => $class,
                    'configured' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $available;
    }

    /**
     * Check if user has premium access for blockchain storage
     */
    protected function userHasPremiumAccess(User $user): bool
    {
        // If blockchain premium is not required, allow all users
        if (!config('blockchain.premium.require_premium', true)) {
            return true;
        }

        // Check if user has premium subscription
        // This should be implemented based on your subscription system
        // For now, we'll check if the user has a 'premium' role or similar
        return $user->hasRole('premium') || $user->is_premium ?? false;
    }

    /**
     * Get blockchain storage statistics for a user
     */
    public function getUserStats(User $user): array
    {
        $blockchainFiles = $user->files()->blockchainStored()->get();
        $totalUploads = BlockchainUpload::whereHas('file', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
        
        $successfulUploads = BlockchainUpload::whereHas('file', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->successful()->count();

        return [
            'total_blockchain_files' => $blockchainFiles->count(),
            'total_blockchain_size' => $blockchainFiles->sum('file_size'),
            'total_uploads_attempted' => $totalUploads,
            'successful_uploads' => $successfulUploads,
            'success_rate' => $totalUploads > 0 ? ($successfulUploads / $totalUploads) * 100 : 0,
            'providers_used' => $blockchainFiles->pluck('blockchain_provider')->unique()->values()->toArray(),
        ];
    }
}
