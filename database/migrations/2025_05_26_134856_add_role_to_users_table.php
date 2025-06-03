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
        // Add enum type if it doesn't exist
        DB::statement("CREATE TYPE user_role AS ENUM ('user', 'record admin', 'admin')");
        
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'record admin', 'admin'])->default('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        
        // Drop the enum type
        DB::statement('DROP TYPE IF EXISTS user_role');
    }
};
