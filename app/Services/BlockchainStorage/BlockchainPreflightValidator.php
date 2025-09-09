<?php

namespace App\Services\BlockchainStorage;

use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlockchainPreflightValidator
{
    protected BlockchainStorageManager $manager;

    public function __construct(BlockchainStorageManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Validate if a file can be uploaded to blockchain storage
     */
    public function validateFileUpload(File $file, User $user, string $provider = null): array
    {
        $provider = $provider ?: config('blockchain.default', 'pinata');
        $errors = [];
        $warnings = [];

        // 1. Check if user has premium access
        if (!$this->validatePremiumAccess($user)) {
            $errors[] = 'Premium subscription required for blockchain storage';
        }

        // 2. Check if provider is available and configured
        $providerValidation = $this->validateProvider($provider);
        if (!$providerValidation['success']) {
            $errors[] = $providerValidation['error'];
        }

        // 3. Check if file exists and is accessible
        $fileValidation = $this->validateFileAccess($file);
        if (!$fileValidation['success']) {
            $errors[] = $fileValidation['error'];
        }

        // 4. Check file size limits
        if ($fileValidation['success']) {
            $sizeValidation = $this->validateFileSize($file, $provider);
            if (!$sizeValidation['success']) {
                $errors[] = $sizeValidation['error'];
            }
        }

        // 5. Check file type support
        $typeValidation = $this->validateFileType($file);
        if (!$typeValidation['success']) {
            $warnings[] = $typeValidation['warning'];
        }

        // 6. Check if file is already on blockchain
        if ($file->isStoredOnBlockchain()) {
            $warnings[] = "File is already stored on blockchain (Provider: {$file->blockchain_provider}, Hash: {$file->ipfs_hash})";
        }

        // 7. Check user's monthly upload limits
        $limitValidation = $this->validateUploadLimits($user);
        if (!$limitValidation['success']) {
            $errors[] = $limitValidation['error'];
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'file_info' => $fileValidation['success'] ? [
                'size' => $file->file_size,
                'size_human' => $this->formatBytes($file->file_size),
                'type' => $file->mime_type,
                'extension' => pathinfo($file->file_name, PATHINFO_EXTENSION),
            ] : null,
            'provider_info' => $providerValidation['success'] ? [
                'name' => $provider,
                'max_file_size' => $providerValidation['max_file_size'],
                'max_file_size_human' => $this->formatBytes($providerValidation['max_file_size']),
            ] : null,
        ];
    }

    /**
     * Validate user has premium access
     */
    protected function validatePremiumAccess(User $user): bool
    {
        if (!config('blockchain.premium.require_premium', true)) {
            return true;
        }

        return $user->hasRole('premium') || ($user->is_premium ?? false);
    }

    /**
     * Validate blockchain provider is available and configured
     */
    protected function validateProvider(string $provider): array
    {
        try {
            $providerInstance = $this->manager->provider($provider);
            
            if (!$providerInstance->isConfigured()) {
                return [
                    'success' => false,
                    'error' => "Blockchain provider '{$provider}' is not properly configured"
                ];
            }

            return [
                'success' => true,
                'max_file_size' => $providerInstance->getMaxFileSize(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Blockchain provider '{$provider}' is not available: " . $e->getMessage()
            ];
        }
    }

    /**
     * Validate file exists and is accessible
     */
    protected function validateFileAccess(File $file): array
    {
        if ($file->is_folder) {
            return [
                'success' => false,
                'error' => 'Cannot upload folders to blockchain storage. Only files are supported.'
            ];
        }

        if (!$file->file_path || !Storage::exists($file->file_path)) {
            return [
                'success' => false,
                'error' => 'File not found in storage. The file may have been deleted or moved.'
            ];
        }

        // Check if file is readable
        try {
            $realPath = Storage::path($file->file_path);
            if (!is_readable($realPath)) {
                return [
                    'success' => false,
                    'error' => 'File is not readable. Check file permissions.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Cannot access file: ' . $e->getMessage()
            ];
        }

        return ['success' => true];
    }

    /**
     * Validate file size against provider limits
     */
    protected function validateFileSize(File $file, string $provider): array
    {
        try {
            $providerInstance = $this->manager->provider($provider);
            $maxSize = $providerInstance->getMaxFileSize();
            
            if ($file->file_size > $maxSize) {
                return [
                    'success' => false,
                    'error' => "File size ({$this->formatBytes($file->file_size)}) exceeds the maximum limit for {$provider} ({$this->formatBytes($maxSize)})"
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Cannot validate file size: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate file type is supported
     */
    protected function validateFileType(File $file): array
    {
        $supportedTypes = config('blockchain.supported_file_types', []);
        $extension = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));

        if (empty($supportedTypes) || in_array($extension, $supportedTypes)) {
            return ['success' => true];
        }

        return [
            'success' => false,
            'warning' => "File type '.{$extension}' is not in the list of recommended file types for blockchain storage. Upload may still proceed."
        ];
    }

    /**
     * Validate user hasn't exceeded monthly upload limits
     */
    protected function validateUploadLimits(User $user): array
    {
        $maxUploads = config('blockchain.premium.max_monthly_uploads', 1000);
        
        // Count successful uploads this month
        $currentMonth = now()->startOfMonth();
        $uploadsThisMonth = $user->files()
            ->blockchainStored()
            ->where('updated_at', '>=', $currentMonth)
            ->count();

        if ($uploadsThisMonth >= $maxUploads) {
            return [
                'success' => false,
                'error' => "Monthly upload limit exceeded ({$uploadsThisMonth}/{$maxUploads}). Limit resets on " . now()->addMonth()->startOfMonth()->format('M j, Y')
            ];
        }

        return [
            'success' => true,
            'uploads_remaining' => $maxUploads - $uploadsThisMonth,
        ];
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get a summary of blockchain storage requirements and limits
     */
    public function getStorageRequirements(string $provider = null): array
    {
        $provider = $provider ?: config('blockchain.default', 'pinata');
        
        try {
            $providerInstance = $this->manager->provider($provider);
            
            return [
                'provider' => $provider,
                'configured' => $providerInstance->isConfigured(),
                'max_file_size' => $providerInstance->getMaxFileSize(),
                'max_file_size_human' => $this->formatBytes($providerInstance->getMaxFileSize()),
                'supported_file_types' => config('blockchain.supported_file_types', []),
                'premium_required' => config('blockchain.premium.require_premium', true),
                'monthly_upload_limit' => config('blockchain.premium.max_monthly_uploads', 1000),
            ];
        } catch (\Exception $e) {
            return [
                'provider' => $provider,
                'configured' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
