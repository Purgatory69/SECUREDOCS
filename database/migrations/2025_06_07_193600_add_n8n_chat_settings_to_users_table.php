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
        Schema::table('users', function (Blueprint $table) {
            $table->string('n8n_webhook_url')->nullable()->after('email'); // Or after another relevant column
            $table->boolean('is_premium')->default(false)->after('n8n_webhook_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('n8n_webhook_url');
            $table->dropColumn('is_premium');
        });
    }
};
