<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\File;

class PopulateFileUuids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:populate-uuids {--force : Force update even if UUIDs exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate UUID and share_token for existing files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate UUIDs for existing files...');
        
        $query = File::query();
        
        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNull('uuid')
                  ->orWhereNull('share_token')
                  ->orWhereNull('url_slug');
            });
        }
        
        $totalFiles = $query->count();
        
        if ($totalFiles === 0) {
            $this->info('No files need UUID population.');
            return 0;
        }
        
        $this->info("Found {$totalFiles} files to update.");
        
        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();
        
        $updated = 0;
        
        $query->chunk(100, function ($files) use ($bar, &$updated) {
            foreach ($files as $file) {
                $updates = [];
                
                if (empty($file->uuid)) {
                    $updates['uuid'] = Str::uuid();
                }
                
                if (empty($file->share_token)) {
                    $updates['share_token'] = Str::uuid();
                }
                
                if (empty($file->url_slug) && !empty($file->file_name)) {
                    $updates['url_slug'] = Str::slug($file->file_name);
                }
                
                if (!empty($updates)) {
                    $file->update($updates);
                    $updated++;
                }
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Successfully updated {$updated} files with UUIDs and share tokens.");
        
        return 0;
    }
}
