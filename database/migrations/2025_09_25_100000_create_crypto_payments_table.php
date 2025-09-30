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
        Schema::create('crypto_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Payment details
            $table->string('wallet_address', 42); // User's wallet address
            $table->decimal('amount_usd', 10, 4); // Amount in USD
            $table->decimal('amount_crypto', 18, 8); // Amount in crypto
            $table->string('token_symbol', 10); // USDC, USDT, ETH, etc.
            $table->string('network', 20); // ethereum, polygon, ronin, etc.
            $table->integer('chain_id'); // Network chain ID
            
            // Transaction tracking
            $table->enum('status', ['pending', 'completed', 'expired', 'failed'])->default('pending');
            $table->string('tx_hash', 66)->nullable(); // Blockchain transaction hash
            $table->decimal('actual_amount_received', 18, 8)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at');
            
            // Arweave integration
            $table->string('arweave_tx_id', 43)->nullable();
            $table->text('arweave_url')->nullable();
            $table->enum('upload_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Metadata
            $table->json('cost_breakdown')->nullable();
            $table->json('payment_metadata')->nullable(); // Store additional payment info
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['wallet_address', 'status']);
            $table->index(['tx_hash']);
            $table->index(['expires_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_payments');
    }
};
