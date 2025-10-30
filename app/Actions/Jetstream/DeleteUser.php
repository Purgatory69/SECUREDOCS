<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Models\File;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        try {
            $this->deleteUserStorage($user->id);
        } catch (\Throwable $e) {
            Log::error('Failed deleting user storage', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        try {
            $this->forceDeleteUserFiles($user->id);
        } catch (\Throwable $e) {
            Log::error('Failed deleting user files rows', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->delete();
    }

    private function deleteUserStorage(int $userId): void
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = config('services.supabase.service_key');
        $bucket = 'docs';

        if (!$supabaseUrl || !$supabaseKey) {
            return;
        }

        $verifySsl = !(config('app.env') === 'local' || config('app.debug'));
        $client = new Client([
            'base_uri' => $supabaseUrl,
            'verify' => $verifySsl,
            'timeout' => 30,
        ]);

        $prefixes = [
            "user_{$userId}/",
            "trash/user_{$userId}/",
        ];

        foreach ($prefixes as $prefix) {
            // Try recursive folder removal using remove API with prefixes
            try {
                $client->post('/storage/v1/object/remove', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $supabaseKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'prefixes' => [ $bucket . '/' . $prefix ],
                    ],
                ]);
                continue; // Successful or best-effort; move to next prefix
            } catch (\Throwable $e) {
                Log::warning('Supabase remove by prefix failed, falling back to list+delete', [
                    'user_id' => $userId,
                    'prefix' => $prefix,
                    'error' => $e->getMessage(),
                ]);
            }

            // List objects by prefix then delete each as a fallback
            $paths = [];
            try {
                $response = $client->post("/storage/v1/object/list/{$bucket}", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $supabaseKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'prefix' => $prefix,
                        'limit' => 1000,
                        'offset' => 0,
                        'sortBy' => ['column' => 'name', 'order' => 'asc'],
                    ],
                ]);
                $items = json_decode($response->getBody()->getContents(), true) ?? [];
                foreach ($items as $item) {
                    if (!empty($item['name'])) {
                        $paths[] = $prefix . ltrim($item['name'], '/');
                    }
                }
            } catch (\Throwable $e) {
                $paths = [];
            }

            foreach ($paths as $path) {
                try {
                    $client->delete("/storage/v1/object/{$bucket}/{$path}", [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $supabaseKey,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed deleting storage object', ['path' => $path, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    private function forceDeleteUserFiles(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            // Hard delete all file rows for this user (ignores soft deletes)
            // Use Eloquent to respect model events if any
            File::withTrashed()->where('user_id', $userId)
                ->orderBy('id')
                ->chunkById(500, function ($chunk) {
                    foreach ($chunk as $file) {
                        $file->forceDelete();
                    }
                });
        });
    }
}
