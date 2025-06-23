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

        $isPremium = $user->is_premium;
        $n8nWebhookUrl = $isPremium ? config('services.n8n.premium_webhook_url') : config('services.n8n.default_webhook_url');

        if (empty($n8nWebhookUrl)) {
            Log::error('Job SendFileToN8n: Webhook URL is not configured.', [
                'file_id' => $this->file->id,
                'is_premium' => $isPremium
            ]);
            return;
        }

        Log::info('Job SendFileToN8n: Starting', ['file_id' => $this->file->id, 'webhook_type' => $isPremium ? 'premium' : 'default']);

        try {
            $response = Http::post($n8nWebhookUrl, $this->file->toArray());

            if ($response->successful()) {
                Log::info('Job SendFileToN8n: File metadata successfully sent.', ['file_id' => $this->file->id]);
            } else {
                Log::error('Job SendFileToN8n: Failed to send file metadata.', [
                    'file_id' => $this->file->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Job SendFileToN8n: Exception while sending metadata.', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
