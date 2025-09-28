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
        // Add Arweave-specific columns to files table
        Schema::table('files', function (Blueprint $table) {
            // Arweave transaction ID (replaces IPFS hash for Arweave files)
            $table->string('arweave_tx_id', 43)->nullable()->after('ipfs_hash');
            
            // Arweave gateway URL (auto-generated)
            $table->text('arweave_url')->nullable()->after('arweave_tx_id');
            
            // Storage provider type (pinata, arweave, etc.)
            $table->enum('storage_provider', ['pinata', 'arweave', 'local'])->default('local')->after('blockchain_provider');
            
            // Permanent storage status (for Arweave)
            $table->boolean('is_permanent_arweave')->default(false)->after('is_permanent_storage');
            
            // Arweave cost tracking
            $table->decimal('arweave_cost_ar', 18, 12)->nullable()->after('is_permanent_arweave');
            $table->decimal('arweave_cost_usd', 10, 4)->nullable()->after('arweave_cost_ar');
            
            // Indexes for performance
            $table->index('arweave_tx_id');
            $table->index(['storage_provider', 'is_permanent_arweave']);
        });

        // Update blockchain_uploads table for Arweave support
        Schema::table('blockchain_uploads', function (Blueprint $table) {
            // Arweave transaction ID
            $table->string('arweave_tx_id', 43)->nullable()->after('ipfs_hash');
            
            // Arweave wallet address used
            $table->string('arweave_wallet_address')->nullable()->after('arweave_tx_id');
            
            // Arweave block height when confirmed
            $table->bigInteger('arweave_block_height')->nullable()->after('arweave_wallet_address');
            
            // Arweave confirmation status
            $table->enum('arweave_status', ['pending', 'confirmed', 'failed'])->nullable()->after('arweave_block_height');
            
            // Arweave network fees
            $table->decimal('arweave_network_fee', 18, 12)->nullable()->after('arweave_status');
            
            // Update provider enum to include arweave
            $table->dropColumn('provider');
        });
        
        Schema::table('blockchain_uploads', function (Blueprint $table) {
            $table->enum('provider', ['pinata', 'arweave'])->after('file_id');
        });

        // Create arweave_wallets table for wallet management
        Schema::create('arweave_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('wallet_address', 43)->unique();
            $table->text('encrypted_jwk'); // Encrypted JSON Web Key
            $table->decimal('balance_ar', 18, 12)->default(0); // AR balance
            $table->decimal('balance_usd', 10, 4)->default(0); // USD equivalent
            $table->timestamp('last_balance_check')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
        });

        // Create arweave_transactions table for detailed tracking
        Schema::create('arweave_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tx_id', 43)->unique();
            $table->string('wallet_address', 43);
            $table->enum('tx_type', ['data', 'transfer'])->default('data');
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->bigInteger('data_size')->nullable(); // File size in bytes
            $table->decimal('fee_ar', 18, 12); // Transaction fee in AR
            $table->decimal('fee_usd', 10, 4)->nullable(); // Fee in USD at time of transaction
            $table->bigInteger('block_height')->nullable();
            $table->string('block_hash')->nullable();
            $table->integer('confirmations')->default(0);
            $table->json('tx_metadata')->nullable(); // Full transaction data
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'submitted_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arweave_transactions');
        Schema::dropIfExists('arweave_wallets');
        
        Schema::table('blockchain_uploads', function (Blueprint $table) {
            $table->dropColumn([
                'arweave_tx_id',
                'arweave_wallet_address', 
                'arweave_block_height',
                'arweave_status',
                'arweave_network_fee'
            ]);
        });
        
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['arweave_tx_id']);
            $table->dropIndex(['storage_provider', 'is_permanent_arweave']);
            
            $table->dropColumn([
                'arweave_tx_id',
                'arweave_url',
                'storage_provider',
                'is_permanent_arweave',
                'arweave_cost_ar',
                'arweave_cost_usd'
            ]);
        });
    }
};
