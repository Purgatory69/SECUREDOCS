<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Add public UUID for file identification (separate from primary key)
            $table->uuid('uuid')->nullable()->unique()->after('parent_id');
            
            // Add share_token column for individual sharing (UUID format)
            $table->uuid('share_token')->nullable()->unique()->after('uuid');
            
            // Add url_slug for SEO-friendly URLs (optional, for future use)
            $table->string('url_slug')->nullable()->after('share_token');
            
            // Add full_path for breadcrumb navigation (optional, for future use)
            $table->text('full_path')->nullable()->after('url_slug');
            
            // Add indexes for performance
            $table->index('uuid');
            $table->index('share_token');
            $table->index('url_slug');
        });
        
        // Populate UUID and share_token for existing files
        DB::table('files')->whereNull('uuid')->orWhereNull('share_token')->chunkById(100, function ($files) {
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
                    DB::table('files')
                        ->where('id', $file->id)
                        ->update($updates);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropIndex(['share_token']);
            $table->dropIndex(['url_slug']);
            $table->dropColumn(['uuid', 'share_token', 'url_slug', 'full_path']);
        });
    }
};
