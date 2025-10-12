<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Simplify files table by keeping only essential columns:
     * - Basic file info (id, user_id, file_name, etc.)
     * - Arweave support (is_arweave, arweave_url)
     * - Upload status (uploading)
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Drop blockchain-related columns we don't need
            $table->dropIndex(['is_blockchain_stored', 'blockchain_provider']);
            $table->dropIndex(['ipfs_hash']);
            $table->dropIndex(['arweave_tx_id']);
            $table->dropIndex(['storage_provider', 'is_permanent_arweave']);
            
            $table->dropColumn([
                // Old blockchain columns
                'blockchain_provider',
                'ipfs_hash',
                'blockchain_url',
                'is_blockchain_stored',
                'blockchain_metadata',
                
                // Old Arweave columns
                'arweave_tx_id',
                'storage_provider',
                'is_permanent_arweave',
                'arweave_cost_ar',
                'arweave_cost_usd',
                
                // Permanent storage columns
                'is_permanent_stored',
                'is_permanent_storage',
                'permanent_storage_enabled_at',
                'permanent_storage_enabled_by',
                
                // Vectorization columns
                'is_vectorized',
                'vectorized_at',
                
                // Confidential columns
                'is_confidential',
                'confidential_enabled_at',
            ]);
        });
        
        Schema::table('files', function (Blueprint $table) {
            // Add simplified Arweave columns
            $table->boolean('is_arweave')->default(false)->after('is_folder');
            $table->text('arweave_url')->nullable()->after('is_arweave');
            $table->boolean('uploading')->default(false)->after('arweave_url');
            
            // Add indexes for performance
            $table->index('is_arweave');
            $table->index('uploading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Remove new simplified columns
            $table->dropIndex(['is_arweave']);
            $table->dropIndex(['uploading']);
            
            $table->dropColumn([
                'is_arweave',
                'arweave_url',
                'uploading'
            ]);
        });
        
        Schema::table('files', function (Blueprint $table) {
            // Restore old columns (basic restore, might need adjustment)
            $table->string('blockchain_provider', 50)->nullable();
            $table->string('ipfs_hash', 100)->nullable();
            $table->text('blockchain_url')->nullable();
            $table->boolean('is_blockchain_stored')->default(false);
            $table->json('blockchain_metadata')->nullable();
            
            $table->string('arweave_tx_id', 43)->nullable();
            $table->text('arweave_url')->nullable(); 
            $table->enum('storage_provider', ['pinata', 'arweave', 'local'])->default('local');
            $table->boolean('is_permanent_arweave')->default(false);
            $table->decimal('arweave_cost_ar', 18, 12)->nullable();
            $table->decimal('arweave_cost_usd', 10, 4)->nullable();
            
            $table->boolean('is_permanent_stored')->default(false);
            $table->boolean('is_permanent_storage')->default(false);
            $table->timestamp('permanent_storage_enabled_at')->nullable();
            $table->unsignedBigInteger('permanent_storage_enabled_by')->nullable();
            
            $table->boolean('is_vectorized')->default(false);
            $table->timestamp('vectorized_at')->nullable();
            
            $table->boolean('is_confidential')->default(false);
            $table->timestamp('confidential_enabled_at')->nullable();
            
            // Indexes
            $table->index(['is_blockchain_stored', 'blockchain_provider']);
            $table->index('ipfs_hash');
            $table->index('arweave_tx_id');
            $table->index(['storage_provider', 'is_permanent_arweave']);
        });
    }
};
