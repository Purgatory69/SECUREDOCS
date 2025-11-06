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
        Schema::table('file_otp_security', function (Blueprint $table) {
            // Add OTP requirement fields for new actions
            $table->boolean('require_otp_for_arweave_upload')->default(false)->after('require_otp_for_preview');
            $table->boolean('require_otp_for_ai_share')->default(false)->after('require_otp_for_arweave_upload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_otp_security', function (Blueprint $table) {
            $table->dropColumn(['require_otp_for_arweave_upload', 'require_otp_for_ai_share']);
        });
    }
};
