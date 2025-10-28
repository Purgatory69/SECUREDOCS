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
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('files', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('parent_id');
            }
            if (!Schema::hasColumn('files', 'share_token')) {
                $table->uuid('share_token')->nullable()->unique()->after('uuid');
            }
            if (!Schema::hasColumn('files', 'url_slug')) {
                $table->string('url_slug')->nullable()->after('share_token');
            }
            if (!Schema::hasColumn('files', 'full_path')) {
                $table->text('full_path')->nullable()->after('url_slug');
            }
        });
        
        // Add indexes if they don't exist
        if (!Schema::hasColumn('files', 'uuid')) {
            Schema::table('files', function (Blueprint $table) {
                $table->index('uuid');
                $table->index('share_token');
                $table->index('url_slug');
            });
        }
        
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
            if (Schema::hasColumn('files', 'uuid')) {
                $table->dropIndex(['uuid']);
                $table->dropColumn('uuid');
            }
            if (Schema::hasColumn('files', 'share_token')) {
                $table->dropIndex(['share_token']);
                $table->dropColumn('share_token');
            }
            if (Schema::hasColumn('files', 'url_slug')) {
                $table->dropIndex(['url_slug']);
                $table->dropColumn('url_slug');
            }
            if (Schema::hasColumn('files', 'full_path')) {
                $table->dropColumn('full_path');
            }
        });
    }
};
