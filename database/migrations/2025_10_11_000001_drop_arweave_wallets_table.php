<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Drop arweave_wallets table as it's no longer needed with Bundlr.
     * Bundlr handles wallet management on the client side.
     */
    public function up(): void
    {
        Schema::dropIfExists('arweave_wallets');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('arweave_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('wallet_address')->unique();
            $table->text('encrypted_jwk');
            $table->decimal('balance_ar', 18, 12)->nullable()->default(0);
            $table->decimal('balance_usd', 10, 4)->nullable()->default(0);
            $table->timestamp('last_balance_check')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
