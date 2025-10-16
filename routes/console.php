<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// NOTE: Scheduled tasks have been migrated to Supabase pg_cron
// All cron jobs now run directly in the database without requiring Laravel scheduler
// See supabase/migrations/cron_jobs.sql for configuration
//
// Active cron jobs:
// 1. auto-delete-old-trash: Deletes files in trash older than 30 days (daily at midnight UTC)
// 2. check-subscription-expiration: Checks and notifies about expiring subscriptions (daily at 9:00 AM UTC)
