<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Modern Arweave HTTP Client
 * 
 * A PHP 8.4+ compatible Arweave client using Laravel's HTTP client
 * Supports both direct Arweave network and L2 bundling services
 */
class ModernArweaveClient
{
    protected string $gatewayUrl;
    protected string $nodeUrl;
    protected array $config;

    public function __construct()
    {
        $this->config = config('blockchain.providers.arweave');
        $this->gatewayUrl = $this->config['gateway_url'] ?? 'https://arweave.net';
        $this->nodeUrl = $this->config['node_url'] ?? 'https://arweave.net';
    }

    /**
     * Get transaction data from Arweave
     */
    public function getTransactionData(string $txId): ?string
    {
        try {
            $response = Http::timeout(30)->get("{$this->gatewayUrl}/{$txId}");
            
            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('Failed to fetch Arweave transaction data', [
                'tx_id' => $txId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (Exception $e) {
            Log::error('Error fetching Arweave transaction data', [
                'tx_id' => $txId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get transaction metadata
     */
    public function getTransaction(string $txId): ?array
    {
        try {
            $response = Http::timeout(15)->get("{$this->nodeUrl}/tx/{$txId}");
            
            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (Exception $e) {
            Log::error('Error fetching Arweave transaction', [
                'tx_id' => $txId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus(string $txId): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->nodeUrl}/tx/{$txId}/status");
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'confirmed' => isset($data['block_height']),
                    'block_height' => $data['block_height'] ?? null,
                    'confirmations' => $data['number_of_confirmations'] ?? 0,
                    'block_hash' => $data['block_indep_hash'] ?? null,
                ];
            }

            return [
                'confirmed' => false,
                'error' => 'Transaction not found or pending'
            ];

        } catch (Exception $e) {
            return [
                'confirmed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get upload price from Arweave network
     */
    public function getPrice(int $dataSize): ?int
    {
        try {
            $response = Http::timeout(15)->get("{$this->nodeUrl}/price/{$dataSize}");
            
            if ($response->successful()) {
                return (int) $response->body(); // Price in Winston
            }

            return null;

        } catch (Exception $e) {
            Log::error('Error fetching Arweave price', [
                'data_size' => $dataSize,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get wallet balance
     */
    public function getWalletBalance(string $address): ?int
    {
        try {
            $response = Http::timeout(15)->get("{$this->nodeUrl}/wallet/{$address}/balance");
            
            if ($response->successful()) {
                return (int) $response->body(); // Balance in Winston
            }

            return null;

        } catch (Exception $e) {
            Log::error('Error fetching wallet balance', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get network info
     */
    public function getNetworkInfo(): ?array
    {
        try {
            $response = Http::timeout(15)->get("{$this->nodeUrl}/info");
            
            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (Exception $e) {
            Log::error('Error fetching network info', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Convert Winston to AR
     */
    public function winstonToAR(int $winston): float
    {
        return $winston / 1000000000000; // 1 AR = 1,000,000,000,000 Winston
    }

    /**
     * Convert AR to Winston
     */
    public function arToWinston(float $ar): int
    {
        return (int) ($ar * 1000000000000);
    }

    /**
     * Get current AR to USD exchange rate
     */
    public function getARtoUSDRate(): float
    {
        try {
            $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => 'arweave',
                'vs_currencies' => 'usd'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['arweave']['usd'] ?? 10.0;
            }

        } catch (Exception $e) {
            Log::warning('Failed to fetch AR/USD rate', ['error' => $e->getMessage()]);
        }

        return 10.0; // Fallback rate
    }

    /**
     * Validate Arweave transaction ID format
     */
    public function isValidTxId(string $txId): bool
    {
        // Arweave transaction IDs are 43 characters long, base64url encoded
        return preg_match('/^[A-Za-z0-9_-]{43}$/', $txId) === 1;
    }

    /**
     * Validate Arweave wallet address format
     */
    public function isValidAddress(string $address): bool
    {
        // Arweave addresses are 43 characters long, base64url encoded
        return preg_match('/^[A-Za-z0-9_-]{43}$/', $address) === 1;
    }

    /**
     * Get gateway URL for a transaction
     */
    public function getGatewayUrl(string $txId): string
    {
        return "{$this->gatewayUrl}/{$txId}";
    }

    /**
     * Get explorer URL for a transaction
     */
    public function getExplorerUrl(string $txId): string
    {
        return "https://viewblock.io/arweave/tx/{$txId}";
    }

    /**
     * Check if Arweave network is accessible
     */
    public function isNetworkAccessible(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->nodeUrl}/info");
            return $response->successful();

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get multiple transaction statuses in batch
     */
    public function getBatchTransactionStatus(array $txIds): array
    {
        $results = [];
        
        foreach ($txIds as $txId) {
            $results[$txId] = $this->getTransactionStatus($txId);
        }

        return $results;
    }

    /**
     * Search for transactions by tags (using GraphQL)
     */
    public function searchTransactions(array $tags, int $limit = 10): ?array
    {
        try {
            // Build GraphQL query for transaction search
            $query = [
                'query' => '
                    query($tags: [TagFilter!], $first: Int) {
                        transactions(tags: $tags, first: $first) {
                            edges {
                                node {
                                    id
                                    owner {
                                        address
                                    }
                                    tags {
                                        name
                                        value
                                    }
                                    block {
                                        height
                                        timestamp
                                    }
                                }
                            }
                        }
                    }
                ',
                'variables' => [
                    'tags' => $tags,
                    'first' => $limit
                ]
            ];

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post('https://arweave.net/graphql', $query);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (Exception $e) {
            Log::error('Error searching Arweave transactions', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
