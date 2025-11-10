<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing views that reference the non-existent 'name' column
        DB::statement('DROP VIEW IF EXISTS recent_activities');
        DB::statement('DROP VIEW IF EXISTS user_activity_summary');
        
        // Recreate recent_activities view with correct column references
        DB::statement("
            CREATE VIEW recent_activities AS
            SELECT 
                sa.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                u.email as user_email,
                f.file_name,
                CONCAT(target_user.firstname, ' ', target_user.lastname) as target_user_name
            FROM system_activities sa
            JOIN users u ON sa.user_id = u.id
            LEFT JOIN files f ON sa.file_id = f.id
            LEFT JOIN users target_user ON sa.target_user_id = target_user.id
            ORDER BY sa.created_at DESC
        ");
        
        // Recreate user_activity_summary view with correct column references
        DB::statement("
            CREATE VIEW user_activity_summary AS
            SELECT 
                u.id as user_id,
                CONCAT(u.firstname, ' ', u.lastname) as name,
                u.email,
                COUNT(sa.id) as total_activities,
                COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '24 hours' THEN 1 END) as activities_24h,
                COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '7 days' THEN 1 END) as activities_7d,
                COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '30 days' THEN 1 END) as activities_30d,
                MAX(sa.created_at) as last_activity_at,
                COUNT(CASE WHEN sa.risk_level = 'high' OR sa.risk_level = 'critical' THEN 1 END) as high_risk_activities
            FROM users u
            LEFT JOIN system_activities sa ON u.id = sa.user_id
            GROUP BY u.id, u.firstname, u.lastname, u.email
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the fixed views
        DB::statement('DROP VIEW IF EXISTS recent_activities');
        DB::statement('DROP VIEW IF EXISTS user_activity_summary');
        
        // Note: Original views referenced non-existent 'name' column
        // For rollback safety, we'll recreate views with firstname/lastname structure
        // to avoid breaking the database if rollback is needed
        DB::statement("
            CREATE VIEW recent_activities AS
            SELECT 
                sa.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                u.email as user_email,
                f.file_name,
                CONCAT(target_user.firstname, ' ', target_user.lastname) as target_user_name
            FROM system_activities sa
            JOIN users u ON sa.user_id = u.id
            LEFT JOIN files f ON sa.file_id = f.id
            LEFT JOIN users target_user ON sa.target_user_id = target_user.id
            ORDER BY sa.created_at DESC
        ");
        
        DB::statement("
            CREATE VIEW user_activity_summary AS
            SELECT 
                u.id as user_id,
                CONCAT(u.firstname, ' ', u.lastname) as name,
                u.email,
                COUNT(sa.id) as total_activities,
                COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '24 hours' THEN 1 END) as activities_24h,
                COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '7 days' THEN 1 END) as activities_7d,
                COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '30 days' THEN 1 END) as activities_30d,
                MAX(sa.created_at) as last_activity_at,
                COUNT(CASE WHEN sa.risk_level = 'high' OR sa.risk_level = 'critical' THEN 1 END) as high_risk_activities
            FROM users u
            LEFT JOIN system_activities sa ON u.id = sa.user_id
            GROUP BY u.id, u.firstname, u.lastname, u.email
        ");
    }
};
