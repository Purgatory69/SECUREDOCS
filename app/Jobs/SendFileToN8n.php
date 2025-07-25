<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFileToN8n implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The file instance.
     *
     * @var \App\Models\File
     */
    public $file;

    /**
     * Create a new job instance.
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Eager load the user relationship to avoid extra queries
        $this->file->load('user');
        $user = $this->file->user;

        if (!$user) {
            Log::error('Job SendFileToN8n: User not found for file.', ['file_id' => $this->file->id]);
            return;
        }

        $user = $this->file->user;

        if (!$user) {
            Log::error('Job SendFileToN8n: User not found for file.', ['file_id' => $this->file->id]);
            return;
        }

        // If user is not premium, log and do nothing further.
        if (!$user->is_premium) {
            Log::info('Job SendFileToN8n: User is not premium. No action taken for this premium feature.', ['file_id' => $this->file->id, 'user_id' => $user->id]);
            return; 
        }

        // If we reach here, user IS premium.
        $n8nWebhookUrl = config('services.n8n.premium_webhook_url');

        if (empty($n8nWebhookUrl)) {
            Log::error('Job SendFileToN8n: Premium Webhook URL is not configured.', [
                'file_id' => $this->file->id,
                'user_id' => $user->id
            ]);
            return;
        }

        Log::info('Job SendFileToN8n: Starting for premium user.', [
            'file_id' => $this->file->id, 
            'user_id' => $user->id,
            'webhook_url' => $n8nWebhookUrl 
        ]);

        try {
            $payload = $this->file->toArray();
            // Example: $payload['user_id_for_n8n'] = $user->id; // Ensure user_id is in payload if needed by n8n

            $response = Http::post($n8nWebhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Job SendFileToN8n: File metadata successfully sent for premium user.', ['file_id' => $this->file->id]);
            } else {
                Log::error('Job SendFileToN8n: Failed to send file metadata for premium user.', [
                    'file_id' => $this->file->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                // Optional: throw new \Exception('Failed to send to n8n. Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Job SendFileToN8n: Exception while sending metadata for premium user.', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage()
            ]);
            // Optional: throw $e;
        }
    }
}
