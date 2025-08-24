<?php

namespace App\Http\Controllers;

use App\Services\WebsiteRefreshService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebsiteRefreshController extends Controller
{
    protected WebsiteRefreshService $refreshService;
    
    public function __construct(WebsiteRefreshService $refreshService)
    {
        $this->refreshService = $refreshService;
    }
    
    /**
     * Get the last refresh event (for SSE polling)
     */
    public function getRefreshEvent(Request $request): JsonResponse
    {
        $lastEvent = $this->refreshService->getLastRefreshEvent();
        
        return response()->json([
            'status' => 'success',
            'data' => $lastEvent,
            'should_refresh' => $lastEvent !== null
        ]);
    }
    
    /**
     * Trigger a manual website refresh
     */
    public function triggerRefresh(Request $request): JsonResponse
    {
        $source = $request->input('source', 'manual');
        $data = $request->input('data', []);
        
        $this->refreshService->triggerRefresh($source, $data);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Website refresh triggered',
            'source' => $source,
            'data' => $data
        ]);
    }
    
    /**
     * Check for file changes and trigger refresh if needed
     */
    public function checkFileChanges(Request $request): JsonResponse
    {
        $directory = $request->input('directory', base_path());
        
        $changes = $this->refreshService->checkFileChanges($directory);
        
        if (!empty($changes) && $changes['has_changes']) {
            $this->refreshService->triggerRefresh('file_changes', $changes);
        }
        
        return response()->json([
            'status' => 'success',
            'changes' => $changes,
            'refresh_triggered' => $changes['has_changes'] ?? false
        ]);
    }
    
    /**
     * Server-Sent Events endpoint for real-time refresh events
     */
    public function sseRefreshEvents(Request $request)
    {
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        
        // Keep connection alive
        set_time_limit(0);
        
        // Check for last event ID
        $lastEventId = $request->header('Last-Event-ID', 0);
        
        while (true) {
            // Get last refresh event
            $lastEvent = $this->refreshService->getLastRefreshEvent();
            
            if ($lastEvent && $lastEvent['refresh_id'] !== $lastEventId) {
                echo "data: " . json_encode($lastEvent) . "\n\n";
                echo "id: " . $lastEvent['refresh_id'] . "\n\n";
                flush();
            }
            
            // Sleep for 1 second before checking again
            sleep(1);
            
            // Break after 30 seconds to prevent infinite connections
            if (connection_aborted()) {
                break;
            }
        }
    }
}
