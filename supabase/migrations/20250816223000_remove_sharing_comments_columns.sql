-- SECUREDOCS: Remove sharing/comments-related columns and index
-- Safe to re-run (uses IF EXISTS). Wrap in a transaction.

BEGIN;

-- Drop index on files.shared_drive_id if present
DROP INDEX IF EXISTS public.idx_files_shared_drive;

-- Drop and recreate view to remove share/comment aggregates
DROP VIEW IF EXISTS public.file_activity_summary;
CREATE VIEW public.file_activity_summary AS
SELECT 
    f.id AS file_id,
    f.file_name,
    f.user_id AS owner_id,
    COUNT(sa.id) AS total_activities,
    COUNT(DISTINCT sa.user_id) AS unique_users,
    COUNT(CASE WHEN sa.action = 'accessed' THEN 1 END) AS access_count,
    MAX(sa.created_at) AS last_activity_at,
    COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '24 hours' THEN 1 END) AS activities_24h
FROM files f
LEFT JOIN system_activities sa ON f.id = sa.file_id
GROUP BY f.id, f.file_name, f.user_id;

-- Update function to stop referencing removed daily stats columns
CREATE OR REPLACE FUNCTION update_daily_stats()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO daily_activity_stats (
        user_id, date, 
        files_created, files_updated, files_deleted, 
        files_accessed
    )
    VALUES (
        NEW.user_id, 
        DATE(NEW.created_at),
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'created' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'updated' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'deleted' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'accessed' THEN 1 ELSE 0 END
    )
    ON CONFLICT (user_id, date) DO UPDATE SET
        files_created = daily_activity_stats.files_created + EXCLUDED.files_created,
        files_updated = daily_activity_stats.files_updated + EXCLUDED.files_updated,
        files_deleted = daily_activity_stats.files_deleted + EXCLUDED.files_deleted,
        files_accessed = daily_activity_stats.files_accessed + EXCLUDED.files_accessed,
        updated_at = now();
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Remove sharing/comments metrics from daily_activity_stats
ALTER TABLE IF EXISTS public.daily_activity_stats
  DROP COLUMN IF EXISTS files_shared,
  DROP COLUMN IF EXISTS comments_created;

-- Remove shared drive columns from files
ALTER TABLE IF EXISTS public.files
  DROP COLUMN IF EXISTS shared_drive_id,
  DROP COLUMN IF EXISTS shared_drive_path;

-- Remove sharing metric from security_metrics
ALTER TABLE IF EXISTS public.security_metrics
  DROP COLUMN IF EXISTS files_shared;

COMMIT;
