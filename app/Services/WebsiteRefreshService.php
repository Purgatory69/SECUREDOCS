<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebsiteRefreshService
{
    /**
     * Channel name for website refresh events
     */
    private const REFRESH_CHANNEL = 'website_refresh';
    
    /**
     * Trigger a website refresh
     */
    public function triggerRefresh(string $source = 'api', array $data = []): bool
    {
        try {
            $refreshData = [
                'timestamp' => now()->toISOString(),
                'source' => $source,
                'data' => $data,
                'refresh_id' => uniqid('refresh_')
            ];
            
            // Store the refresh event in cache for 30 seconds
            Cache::put('last_refresh_event', $refreshData, 30);
            
            // Log the refresh trigger
            Log::info('Website refresh triggered', $refreshData);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to trigger website refresh', [
                'error' => $e->getMessage(),
                'source' => $source,
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Get the last refresh event
     */
    public function getLastRefreshEvent(): ?array
    {
        return Cache::get('last_refresh_event');
    }
    
    /**
     * Check if refresh is needed based on file changes
     */
    public function checkFileChanges(string $directory): array
    {
        $changes = [];
        
        try {
            // Get the last modification time of the directory
            $lastModified = $this->getDirectoryLastModified($directory);
            
            // Compare with stored last check time
            $lastCheck = Cache::get('last_file_check_time', 0);
            
            if ($lastModified > $lastCheck) {
                $changes = [
                    'directory' => $directory,
                    'last_modified' => $lastModified,
                    'previous_check' => $lastCheck,
                    'has_changes' => true
                ];
            }
            
            // Update last check time
            Cache::put('last_file_check_time', time(), 3600); // Cache for 1 hour
            
        } catch (\Exception $e) {
            Log::error('Error checking file changes', [
                'directory' => $directory,
                'error' => $e->getMessage()
            ]);
        }
        
        return $changes;
    }
    
    /**
     * Get the last modification time of a directory
     */
    private function getDirectoryLastModified(string $directory): int
    {
        $lastModified = 0;
        
        if (!is_dir($directory)) {
            return $lastModified;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $mtime = $file->getMTime();
                if ($mtime > $lastModified) {
                    $lastModified = $mtime;
                }
            }
        }
        
        return $lastModified;
    }
}
