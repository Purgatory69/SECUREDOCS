<?php

namespace App\Http\Controllers;

use App\Services\BlockchainStorage\BlockchainStorageManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class BlockchainTestController extends Controller
{
    protected BlockchainStorageManager $blockchainManager;

    public function __construct(BlockchainStorageManager $blockchainManager)
    {
        $this->blockchainManager = $blockchainManager;
    }

    /**
     * Test Pinata connection
     */
    public function testPinata(): JsonResponse
    {
        try {
            $result = $this->blockchainManager->testProvider('pinata');
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'provider' => 'pinata',
                'timestamp' => now()->toIso8601String(),
                'response' => $result['response'] ?? null
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pinata test failed: ' . $e->getMessage(),
                'provider' => 'pinata',
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get available blockchain providers
     */
    public function getProviders(): JsonResponse
    {
        try {
            $providers = $this->blockchainManager->getAvailableProviders();
            
            return response()->json([
                'success' => true,
                'providers' => $providers,
                'default_provider' => config('blockchain.default'),
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get providers: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Test file upload to Pinata (for development only)
     */
    public function testUpload(Request $request): JsonResponse
    {
        try {
            if (!$request->hasFile('test_file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No test file provided. Please upload a small test file.'
                ], 400);
            }

            $file = $request->file('test_file');
            
            // Basic validation
            if ($file->getSize() > 5 * 1024 * 1024) { // 5MB limit for test
                return response()->json([
                    'success' => false,
                    'message' => 'Test file too large. Please use a file smaller than 5MB.'
                ], 400);
            }

            $pinataService = $this->blockchainManager->provider('pinata');
            
            $result = $pinataService->uploadFile($file, [
                'name' => 'Test Upload - ' . now()->format('Y-m-d H:i:s'),
                'keyvalues' => [
                    'test' => true,
                    'environment' => app()->environment(),
                    'timestamp' => now()->toIso8601String()
                ]
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Test upload successful!' : 'Test upload failed',
                'ipfs_hash' => $result['ipfs_hash'] ?? null,
                'gateway_url' => $result['gateway_url'] ?? null,
                'provider' => 'pinata',
                'file_info' => [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ],
                'result' => $result,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test upload failed: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Get blockchain storage configuration
     */
    public function getConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'config' => [
                'default_provider' => config('blockchain.default'),
                'premium_enabled' => config('blockchain.premium.enabled'),
                'require_premium' => config('blockchain.premium.require_premium'),
                'max_monthly_uploads' => config('blockchain.premium.max_monthly_uploads'),
                'supported_file_types' => config('blockchain.supported_file_types'),
                'providers' => [
                    'pinata' => [
                        'enabled' => config('blockchain.providers.pinata.enabled'),
                        'configured' => !empty(config('blockchain.providers.pinata.api_key')),
                        'max_file_size' => config('blockchain.providers.pinata.max_file_size'),
                        'gateway_url' => config('blockchain.providers.pinata.gateway_url')
                    ]
                ]
            ],
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
