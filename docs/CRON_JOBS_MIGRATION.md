# Cron Jobs Migration to Supabase pg_cron

## Overview
All scheduled tasks have been migrated from Laravel's task scheduler to Supabase's `pg_cron` extension. This eliminates the need for a separate server or worker process to run scheduled tasks in production.

## Benefits
- **No separate server required**: Cron jobs run directly in the database
- **Zero network latency**: Jobs execute within Postgres
- **Persistent**: Jobs survive database restarts
- **Centralized**: All scheduling logic in one place
- **Production-ready**: No need to configure system cron or worker processes

## Active Cron Jobs

### 1. Auto-Delete Old Trash
- **Job Name**: `auto-delete-old-trash`
- **Schedule**: Daily at midnight UTC (8:00 AM Manila time)
- **Cron Expression**: `0 0 * * *`
- **Purpose**: Permanently deletes files that have been in trash for more than 30 days
- **SQL Command**:
  ```sql
  DELETE FROM public.files
  WHERE deleted_at IS NOT NULL
  AND deleted_at < NOW() - INTERVAL '30 days';
  ```

### 2. Check Subscription Expiration
- **Job Name**: `check-subscription-expiration`
- **Schedule**: Daily at 9:00 AM UTC (5:00 PM Manila time)
- **Cron Expression**: `0 9 * * *`
- **Purpose**: Sends notifications for expiring subscriptions and handles expired ones
- **SQL Command**:
  ```sql
  SELECT notify_expiring_subscriptions();
  SELECT handle_expired_subscriptions();
  ```

## Management Commands

### View All Scheduled Jobs
```sql
SELECT 
    jobid,
    jobname,
    schedule,
    command,
    active
FROM cron.job
ORDER BY jobid;
```

### View Job Execution History
```sql
SELECT 
    jobid,
    runid,
    job_pid,
    database,
    username,
    command,
    status,
    return_message,
    start_time,
    end_time
FROM cron.job_run_details
ORDER BY start_time DESC
LIMIT 20;
```

### Check Recent Failed Jobs
```sql
SELECT 
    j.jobname,
    jrd.status,
    jrd.return_message,
    jrd.start_time,
    jrd.end_time
FROM cron.job_run_details jrd
JOIN cron.job j ON j.jobid = jrd.jobid
WHERE jrd.status = 'failed'
ORDER BY jrd.start_time DESC
LIMIT 10;
```

### Manually Trigger a Job (for testing)
```sql
-- Trigger auto-delete job
DELETE FROM public.files
WHERE deleted_at IS NOT NULL
AND deleted_at < NOW() - INTERVAL '30 days';

-- Trigger subscription check
SELECT notify_expiring_subscriptions();
SELECT handle_expired_subscriptions();
```

### Unschedule a Job
```sql
SELECT cron.unschedule('job-name-here');
```

### Reschedule a Job
```sql
-- First unschedule
SELECT cron.unschedule('auto-delete-old-trash');

-- Then reschedule with new timing
SELECT cron.schedule(
    'auto-delete-old-trash',
    '0 2 * * *',  -- New time: 2:00 AM UTC
    $$
    DELETE FROM public.files
    WHERE deleted_at IS NOT NULL
    AND deleted_at < NOW() - INTERVAL '30 days';
    $$
);
```

## Monitoring in Production

### Via Supabase Dashboard
1. Go to **Integrations** → **Cron** in your Supabase project
2. View all scheduled jobs and their execution history
3. Monitor job success/failure rates

### Via SQL Editor
Use the queries above to check job status and history directly in the SQL editor.

### Setting Up Alerts
You can create a monitoring function that checks for failed jobs:

```sql
CREATE OR REPLACE FUNCTION check_failed_cron_jobs()
RETURNS TABLE(jobname text, failure_count bigint) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        j.jobname,
        COUNT(*) as failure_count
    FROM cron.job_run_details jrd
    JOIN cron.job j ON j.jobid = jrd.jobid
    WHERE jrd.status = 'failed'
    AND jrd.start_time > NOW() - INTERVAL '24 hours'
    GROUP BY j.jobname
    HAVING COUNT(*) > 0;
END;
$$ LANGUAGE plpgsql;

-- Run this periodically to check for failures
SELECT * FROM check_failed_cron_jobs();
```

## Migration Notes

### What Changed
- **Before**: Laravel scheduler required `php artisan schedule:run` to be executed every minute via system cron or a worker process
- **After**: All jobs run directly in Postgres via `pg_cron` extension

### Laravel Commands Status
The following Laravel commands are **no longer scheduled** but remain available for manual execution:
- `php artisan subscriptions:check-expiration` - Can still be run manually if needed
- `php artisan app:auto-delete-old-trash` - Empty command, logic now in database

### Production Deployment
**No additional setup required!** Once this migration is applied:
- ✅ No need to configure system cron
- ✅ No need to run `php artisan schedule:run`
- ✅ No need for worker processes
- ✅ Jobs run automatically in the database

## Troubleshooting

### Job Not Running
1. Check if job is active:
   ```sql
   SELECT * FROM cron.job WHERE jobname = 'job-name-here';
   ```
2. Check execution history:
   ```sql
   SELECT * FROM cron.job_run_details 
   WHERE jobid = (SELECT jobid FROM cron.job WHERE jobname = 'job-name-here')
   ORDER BY start_time DESC LIMIT 5;
   ```

### Job Failing
1. Check error message:
   ```sql
   SELECT return_message, start_time 
   FROM cron.job_run_details 
   WHERE jobid = (SELECT jobid FROM cron.job WHERE jobname = 'job-name-here')
   AND status = 'failed'
   ORDER BY start_time DESC LIMIT 1;
   ```
2. Test the SQL command manually in SQL editor
3. Check database permissions

### Timezone Issues
All cron schedules use UTC. To adjust for your timezone:
- Manila (UTC+8): Subtract 8 hours from desired local time
- Example: Want 5:00 PM Manila → Use 9:00 AM UTC (`0 9 * * *`)

## Best Practices
1. **Keep jobs short**: Maximum 10 minutes execution time
2. **Limit concurrency**: No more than 8 jobs running simultaneously
3. **Monitor regularly**: Check `cron.job_run_details` for failures
4. **Test changes**: Use manual execution to test before scheduling
5. **Document changes**: Update this file when adding/modifying jobs

## References
- [Supabase Cron Documentation](https://supabase.com/docs/guides/cron)
- [pg_cron GitHub](https://github.com/citusdata/pg_cron)
- Migration file: `supabase/migrations/20250115000000_setup_cron_jobs.sql`
