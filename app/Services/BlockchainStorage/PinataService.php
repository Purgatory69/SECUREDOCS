<?php

namespace App\Services\BlockchainStorage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PinataService implements BlockchainStorageInterface
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $apiUrl;
    protected string $gatewayUrl;

    public function __construct()
    {
        $this->apiKey = config('blockchain.providers.pinata.api_key');
        $this->apiSecret = config('blockchain.providers.pinata.api_secret');
        $this->apiUrl = config('blockchain.providers.pinata.api_url');
        $this->gatewayUrl = config('blockchain.providers.pinata.gateway_url');

        if (!$this->apiKey || !$this->apiSecret) {
            throw new Exception('Pinata API credentials are not configured. Please check your environment variables.');
        }
    }

    /**
     * Upload a file to Pinata IPFS
     */
    public function uploadFile(UploadedFile $file, array $metadata = []): array
    {
        try {
            Log::info('Starting Pinata file upload', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            // Prepare the multipart form data
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->apiSecret,
            ])
            ->timeout(120) // 2 minutes timeout for large files
            ->attach('file', file_get_contents($file->path()), $file->getClientOriginalName())
            ->post($this->apiUrl . '/pinning/pinFileToIPFS', [
                'pinataMetadata' => json_encode([
                    'name' => $metadata['name'] ?? $file->getClientOriginalName(),
                    'keyvalues' => array_merge([
                        'uploaded_by' => 'SECUREDOCS',
                        'upload_timestamp' => now()->toISOString(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ], $metadata['keyvalues'] ?? [])
                ]),
                'pinataOptions' => json_encode([
                    'cidVersion' => 1,
                    'wrapWithDirectory' => false,
                ])
            ]);

            if (!$response->successful()) {
                $errorMessage = $response->json()['error'] ?? 'Unknown Pinata API error';
                throw new Exception("Pinata upload failed: {$errorMessage}");
            }

            $responseData = $response->json();
            
            Log::info('Pinata upload successful', [
                'ipfs_hash' => $responseData['IpfsHash'],
                'pin_size' => $responseData['PinSize'],
                'timestamp' => $responseData['Timestamp']
            ]);

            return [
                'success' => true,
                'ipfs_hash' => $responseData['IpfsHash'],
                'gateway_url' => $this->getGatewayUrl($responseData['IpfsHash']),
                'pin_size' => $responseData['PinSize'],
                'timestamp' => $responseData['Timestamp'],
                'provider' => 'pinata',
                'raw_response' => $responseData
            ];

        } catch (Exception $e) {
            Log::error('Pinata upload failed', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'pinata'
            ];
        }
    }

    /**
     * Get file content from IPFS hash
     */
    public function getFile(string $ipfsHash): ?string
    {
        try {
            $response = Http::timeout(30)->get($this->getGatewayUrl($ipfsHash));
            
            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (Exception $e) {
            Log::error('Failed to retrieve file from Pinata', [
                'ipfs_hash' => $ipfsHash,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Unpin a file from Pinata (remove from IPFS)
     */
    public function unpinFile(string $ipfsHash): bool
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->apiSecret,
            ])->delete($this->apiUrl . "/pinning/unpin/{$ipfsHash}");

            if ($response->successful()) {
                Log::info('File unpinned from Pinata', ['ipfs_hash' => $ipfsHash]);
                return true;
            }

            Log::warning('Failed to unpin file from Pinata', [
                'ipfs_hash' => $ipfsHash,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return false;

        } catch (Exception $e) {
            Log::error('Error unpinning file from Pinata', [
                'ipfs_hash' => $ipfsHash,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Test the Pinata connection
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->apiSecret,
            ])->get($this->apiUrl . '/data/testAuthentication');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Pinata connection successful!',
                    'response' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Pinata authentication failed',
                'status' => $response->status(),
                'response' => $response->json()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Pinata connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get the gateway URL for an IPFS hash
     */
    public function getGatewayUrl(string $ipfsHash): string
    {
        return "{$this->gatewayUrl}/ipfs/{$ipfsHash}";
    }

    /**
     * Get Pinata account usage information
     */
    public function getAccountUsage(): array
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->apiSecret,
            ])->get($this->apiUrl . '/data/userPinPolicy');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get account usage',
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting account usage: ' . $e->getMessage()
            ];
        }
    }

    /**
     * List pinned files (with pagination)
     */
    public function listPinnedFiles(int $limit = 10, int $offset = 0): array
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->apiSecret,
            ])->get($this->apiUrl . '/data/pinList', [
                'pageLimit' => $limit,
                'pageOffset' => $offset,
                'status' => 'pinned'
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to list pinned files',
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error listing files: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'pinata';
    }

    /**
     * Check if the service is configured properly
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Get maximum file size supported (in bytes)
     */
    public function getMaxFileSize(): int
    {
        return config('blockchain.providers.pinata.max_file_size', 104857600); // 100MB default
    }
}
