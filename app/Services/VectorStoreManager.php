<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class VectorStoreManager
{
    private string $baseUrl;
    private string $serviceKey;

    public function __construct()
    {
        $supabaseUrl = config('services.supabase.url');
        $this->serviceKey = config('services.supabase.service_key');
        
        if (!$supabaseUrl) {
            throw new Exception('SUPABASE_URL is missing from .env file');
        }
        
        if (!$this->serviceKey) {
            throw new Exception('SUPABASE_SERVICE_ROLE_KEY is missing from .env file. Please add it to enable vector operations.');
        }
        
        $this->baseUrl = $supabaseUrl . '/rest/v1';
    }

    /**
     * Soft-hide vectors for a file (used for Trash)
     */
    public function softHide(int $fileId, int $userId): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal'
            ])->withOptions(['verify' => false])->patch("{$this->baseUrl}/document_metadata?file_id=eq.{$fileId}&user_id=eq.{$userId}", [
                'is_deleted' => true,
                'deleted_at' => now()->toISOString()
            ]);

            Log::info('Vector soft-hide', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to soft-hide vectors', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Restore hidden vectors (clear soft-delete flags)
     */
    public function restoreHidden(int $fileId, int $userId): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal'
            ])->withOptions(['verify' => false])->patch("{$this->baseUrl}/document_metadata?file_id=eq.{$fileId}&user_id=eq.{$userId}", [
                'is_deleted' => false,
                'deleted_at' => null
            ]);

            Log::info('Vector restore', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to restore vectors', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Completely remove all vectors for a file
     */
    public function unvector(int $fileId, int $userId): bool
    {
        try {
            // Delete in correct order due to FK constraints
            // 1. Document rows first
            $rowsResponse = Http::withHeaders([
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Prefer' => 'return=minimal'
            ])->withOptions(['verify' => false])->delete("{$this->baseUrl}/document_rows?file_id=eq.{$fileId}");

            // 2. Documents
            $docsResponse = Http::withHeaders([
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Prefer' => 'return=minimal'
            ])->withOptions(['verify' => false])->delete("{$this->baseUrl}/documents?file_id=eq.{$fileId}&user_id=eq.{$userId}");

            // 3. Document metadata last
            $metaResponse = Http::withHeaders([
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Prefer' => 'return=minimal'
            ])->withOptions(['verify' => false])->delete("{$this->baseUrl}/document_metadata?file_id=eq.{$fileId}&user_id=eq.{$userId}");

            $success = $rowsResponse->successful() && $docsResponse->successful() && $metaResponse->successful();

            if ($success) {
                // Update file model to mark as not vectorized
                $file = File::find($fileId);
                if ($file) {
                    $file->markAsNotVectorized();
                    Log::info('File marked as not vectorized', ['file_id' => $fileId]);
                }
            }

            Log::info('Vector unvector', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'rows_status' => $rowsResponse->status(),
                'rows_body' => $rowsResponse->body(),
                'docs_status' => $docsResponse->status(),
                'docs_body' => $docsResponse->body(),
                'meta_status' => $metaResponse->status(),
                'meta_body' => $metaResponse->body(),
                'success' => $success
            ]);

            return $success;
        } catch (Exception $e) {
            Log::error('Failed to unvector', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a file has vectors
     */
    public function hasVectors(int $fileId, int $userId): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
            ])->withOptions(['verify' => false])->get("{$this->baseUrl}/document_metadata?file_id=eq.{$fileId}&user_id=eq.{$userId}&select=id");

            return $response->successful() && count($response->json()) > 0;
        } catch (Exception $e) {
            Log::error('Failed to check vectors', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if vectors are soft-deleted
     */
    public function areVectorsSoftDeleted(int $fileId, int $userId): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
            ])->withOptions(['verify' => false])->get("{$this->baseUrl}/document_metadata?file_id=eq.{$fileId}&user_id=eq.{$userId}&is_deleted=eq.true&select=id");

            return $response->successful() && count($response->json()) > 0;
        } catch (Exception $e) {
            Log::error('Failed to check soft-deleted vectors', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
