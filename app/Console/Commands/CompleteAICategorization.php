<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompleteAICategorization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:complete-categorization {user_id : The user ID to complete categorization for} {--progress=100 : Progress percentage (default: 100)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete AI categorization for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $progress = (int) $this->option('progress');
        
        $this->info("🤖 Completing AI categorization for user {$userId}");
        
        // Show current status
        $this->showCurrentStatus($userId);
        
        if ($this->confirm('Do you want to proceed with completing the categorization?')) {
            $this->completeAICategorization($userId, $progress);
        } else {
            $this->info('Operation cancelled.');
        }
    }

    /**
     * Show current categorization status
     */
    private function showCurrentStatus($userId)
    {
        $cacheKey = "ai_categorization_status_{$userId}";
        $status = Cache::get($cacheKey);
        
        if ($status) {
            $this->info("📊 Current Status:");
            $this->line("   Status: {$status['status']}");
            $this->line("   Progress: {$status['progress']}%");
            $this->line("   Message: {$status['message']}");
            $this->line("   Updated: {$status['updated_at']}");
        } else {
            $this->warn("❌ No categorization status found for user {$userId}");
        }
    }

    /**
     * Complete AI categorization
     */
    private function completeAICategorization($userId, $progress)
    {
        $cacheKey = "ai_categorization_status_{$userId}";
        
        $statusData = [
            'status' => $progress >= 100 ? 'completed' : 'in_progress',
            'progress' => $progress,
            'message' => $progress >= 100 ? 'AI categorization completed successfully' : "Processing... {$progress}%",
            'updated_at' => now()->toISOString(),
            'details' => null
        ];
        
        try {
            // Update cache (expires in 1 hour)
            Cache::put($cacheKey, $statusData, 3600);
            
            // Log the update
            Log::info('AI categorization status updated', [
                'user_id' => $userId,
                'status' => $statusData['status'],
                'progress' => $progress
            ]);
            
            $this->info("✅ Successfully updated AI categorization status");
            $this->line("📊 Status: {$statusData['status']} ({$progress}%)");
            $this->line("💾 Cache key: {$cacheKey}");
            $this->line("🔄 Frontend will detect this change automatically");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to update categorization status: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
