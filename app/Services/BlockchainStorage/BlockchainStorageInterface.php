<?php

namespace App\Services\BlockchainStorage;

use Illuminate\Http\UploadedFile;

interface BlockchainStorageInterface
{
    /**
     * Upload a file to the blockchain storage provider
     * 
     * @param UploadedFile $file The file to upload
     * @param array $metadata Additional metadata for the file
     * @return array Upload result with success status, IPFS hash, and other data
     */
    public function uploadFile(UploadedFile $file, array $metadata = []): array;

    /**
     * Get file content from the provider using IPFS hash
     * 
     * @param string $ipfsHash The IPFS hash of the file
     * @return string|null File content or null if not found
     */
    public function getFile(string $ipfsHash): ?string;

    /**
     * Remove/unpin a file from the provider
     * 
     * @param string $ipfsHash The IPFS hash of the file to remove
     * @return bool True if successful, false otherwise
     */
    public function unpinFile(string $ipfsHash): bool;

    /**
     * Test the connection to the provider
     * 
     * @return array Connection test result
     */
    public function testConnection(): array;

    /**
     * Get the gateway URL for an IPFS hash
     * 
     * @param string $ipfsHash The IPFS hash
     * @return string The full gateway URL
     */
    public function getGatewayUrl(string $ipfsHash): string;

    /**
     * Get the provider name
     * 
     * @return string Provider name (e.g., 'pinata', 'filecoin')
     */
    public function getProviderName(): string;

    /**
     * Check if the service is properly configured
     * 
     * @return bool True if configured, false otherwise
     */
    public function isConfigured(): bool;

    /**
     * Get the maximum file size supported by this provider
     * 
     * @return int Maximum file size in bytes
     */
    public function getMaxFileSize(): int;
}
