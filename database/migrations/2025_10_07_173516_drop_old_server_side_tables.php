<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Drop old server-side payment tables
     */
    public function up(): void
    {
        // Drop old server-side payment and storage tables
        Schema::dropIfExists('crypto_payments');
        Schema::dropIfExists('payment_transactions'); 
        Schema::dropIfExists('permanent_storage');
        
        // Note: Keep these for client-side tracking:
        // - arweave_transactions (user upload tracking)
        // - arweave_wallets (user wallet info)
        // - files (core file management)
    }

    /**
     * Reverse the migrations - Recreate basic table structures
     */
    public function down(): void
    {
        // Recreate crypto_payments table structure (for rollback)
        Schema::create('crypto_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('wallet_address', 42);
            $table->decimal('amount_usd', 10, 4);
            $table->decimal('amount_crypto', 18, 8);
            $table->string('token_symbol', 10);
            $table->string('network', 20);
            $table->integer('chain_id');
            $table->enum('status', ['pending', 'completed', 'expired', 'failed'])->default('pending');
            $table->string('tx_hash', 66)->nullable();
            $table->decimal('actual_amount_received', 18, 8)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at');
            $table->string('arweave_tx_id', 43)->nullable();
            $table->text('arweave_url')->nullable();
            $table->enum('upload_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('cost_breakdown')->nullable();
            $table->json('payment_metadata')->nullable();
            $table->timestamps();
        });
        
        // Basic payment_transactions structure
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
