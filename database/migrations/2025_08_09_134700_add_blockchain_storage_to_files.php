<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add blockchain storage columns to existing files table
        Schema::table('files', function (Blueprint $table) {
            $table->string('blockchain_provider', 50)->nullable()->after('file_path');
            $table->string('ipfs_hash', 100)->nullable()->after('blockchain_provider');
            $table->text('blockchain_url')->nullable()->after('ipfs_hash');
            $table->boolean('is_blockchain_stored')->default(false)->after('blockchain_url');
            $table->json('blockchain_metadata')->nullable()->after('is_blockchain_stored');
            
            // Index for performance
            $table->index(['is_blockchain_stored', 'blockchain_provider']);
            $table->index('ipfs_hash');
        });

        // Create blockchain_configs table for user API configurations
        Schema::create('blockchain_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider', 50); // 'pinata', 'filecoin', 'storj', etc.
            $table->text('api_key_encrypted');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'provider']);
        });

        // Create blockchain_uploads table for tracking upload history
        Schema::create('blockchain_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->string('provider', 50);
            $table->string('ipfs_hash', 100)->nullable();
            $table->enum('upload_status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->decimal('upload_cost', 10, 4)->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();
            
            $table->index(['file_id', 'provider']);
            $table->index('upload_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['is_blockchain_stored', 'blockchain_provider']);
            $table->dropIndex(['ipfs_hash']);
            $table->dropColumn([
                'blockchain_provider',
                'ipfs_hash', 
                'blockchain_url',
                'is_blockchain_stored',
                'blockchain_metadata'
            ]);
        });

        Schema::dropIfExists('blockchain_uploads');
        Schema::dropIfExists('blockchain_configs');
    }
};
