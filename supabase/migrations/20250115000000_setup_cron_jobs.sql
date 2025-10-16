-- Migration: Setup pg_cron jobs for automated tasks
-- Created: 2025-01-15
-- Description: Configures pg_cron extension and schedules automated maintenance tasks

-- ============================================================================
-- STEP 1: Enable pg_cron extension
-- ============================================================================
CREATE EXTENSION IF NOT EXISTS pg_cron WITH SCHEMA pg_catalog;

-- Grant necessary permissions
GRANT USAGE ON SCHEMA cron TO postgres;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA cron TO postgres;

-- ============================================================================
-- STEP 2: Schedule auto-delete old trash job
-- ============================================================================
-- Deletes files that have been in trash for more than 30 days
-- Runs daily at midnight UTC (8:00 AM Manila time)
SELECT cron.schedule(
    'auto-delete-old-trash',
    '0 0 * * *',
    $$
    DELETE FROM public.files
    WHERE deleted_at IS NOT NULL
    AND deleted_at < NOW() - INTERVAL '30 days';
    $$
);

-- ============================================================================
-- STEP 3: Schedule subscription expiration check job
-- ============================================================================
-- Checks for expiring subscriptions and handles expired ones
-- Runs daily at 9:00 AM UTC (5:00 PM Manila time)
SELECT cron.schedule(
    'check-subscription-expiration',
    '0 9 * * *',
    $$
    SELECT notify_expiring_subscriptions();
    SELECT handle_expired_subscriptions();
    $$
);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- To verify cron jobs are scheduled, run:
-- SELECT jobid, jobname, schedule, command, active FROM cron.job ORDER BY jobid;

-- To check job execution history, run:
-- SELECT * FROM cron.job_run_details ORDER BY start_time DESC LIMIT 10;

-- To manually unschedule a job (if needed):
-- SELECT cron.unschedule('job-name-here');

-- ============================================================================
-- NOTES
-- ============================================================================
-- 1. All times are in UTC. Adjust schedule if needed for your timezone.
-- 2. pg_cron runs with the privileges of the postgres role.
-- 3. Jobs are persistent and survive database restarts.
-- 4. Maximum recommended concurrent jobs: 8
-- 5. Maximum recommended job duration: 10 minutes
-- 6. The notify_expiring_subscriptions() and handle_expired_subscriptions()
--    functions must exist before this migration runs.
