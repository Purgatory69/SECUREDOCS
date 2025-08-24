<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class N8nNotificationController extends Controller
{
    /**
     * Handle incoming notification requests from n8n
     */
    public function handleNotification(Request $request): JsonResponse
    {
        try {
            // Get the notification data from the request
            $title = $request->input('title', 'n8n Notification');
            $message = $request->input('message', 'Workflow executed successfully');
            $type = $request->input('type', 'info'); // info, success, warning, error
            $workflow = $request->input('workflow', 'Unknown Workflow');
            $refresh_website = $request->input('refresh_website', true); // New parameter
            
            // Log the notification for debugging
            Log::info('n8n notification received', [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'workflow' => $workflow,
                'refresh_website' => $refresh_website,
                'timestamp' => now(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            // Show Windows notification
            $this->showWindowsNotification($title, $message, $type);
            
            // Trigger website refresh if requested
            if ($refresh_website) {
                $refreshService = new \App\Services\WebsiteRefreshService();
                $refreshService->triggerRefresh('n8n_notification', [
                    'title' => $title,
                    'message' => $message,
                    'workflow' => $workflow
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Notification received and displayed',
                'data' => [
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'workflow' => $workflow,
                    'refresh_website' => $refresh_website,
                    'received_at' => now()->toISOString()
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error handling n8n notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show Windows toast notification
     */
    private function showWindowsNotification(string $title, string $message, string $type): void
    {
        // Escape special characters for PowerShell
        $title = addslashes($title);
        $message = addslashes($message);
        
        // Determine the icon based on type
        $icon = match($type) {
            'success' => 'Info',
            'warning' => 'Warning',
            'error' => 'Error',
            default => 'Info'
        };
        
        // Create PowerShell command for Windows toast notification
        $powershellCommand = sprintf(
            'powershell.exe -Command "Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.MessageBox]::Show(\'%s\', \'%s\', \'OK\', \'%s\')"',
            $message,
            $title,
            $icon
        );
        
        // Execute the command in background
        if (PHP_OS_FAMILY === 'Windows') {
            exec($powershellCommand . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                Log::warning('Failed to show Windows notification', [
                    'command' => $powershellCommand,
                    'output' => $output,
                    'return_code' => $returnCode
                ]);
            }
        } else {
            Log::info('Windows notification skipped (not on Windows)', [
                'title' => $title,
                'message' => $message
            ]);
        }
    }
    
    /**
     * Get notification status and logs
     */
    public function getStatus(): JsonResponse
    {
        return response()->json([
            'status' => 'active',
            'server_time' => now()->toISOString(),
            'platform' => PHP_OS_FAMILY,
            'endpoints' => [
                'notification' => url('/api/n8n/notification'),
                'status' => url('/api/n8n/status')
            ]
        ]);
    }
    
    /**
     * Test endpoint for debugging
     */
    public function test(): JsonResponse
    {
        $this->showWindowsNotification(
            'Test Notification',
            'This is a test notification from your Laravel API',
            'info'
        );
        
        return response()->json([
            'status' => 'success',
            'message' => 'Test notification sent'
        ]);
    }
}
