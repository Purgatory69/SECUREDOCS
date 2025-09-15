-- Migration: Remove security-related features and trusted devices
-- Date: 2025-09-10
-- This migration drops security-centric tables no longer used by the application.
-- Note: This mirrors the changes applied via MCP to ensure the schema is codified in version control.

BEGIN;

-- Drop DLP scan results (depends on security_policies and files)
DROP TABLE IF EXISTS public.dlp_scan_results CASCADE;

-- Drop file encryption table
DROP TABLE IF EXISTS public.file_encryption CASCADE;

-- Drop aggregated security metrics table
DROP TABLE IF EXISTS public.security_metrics CASCADE;

-- Drop security violations and policies
DROP TABLE IF EXISTS public.security_violations CASCADE;
DROP TABLE IF EXISTS public.security_policies CASCADE;

-- Drop trusted devices
DROP TABLE IF EXISTS public.trusted_devices CASCADE;

-- Optional: security events audit table if present in environment
DROP TABLE IF EXISTS public.security_events CASCADE;

COMMIT;
