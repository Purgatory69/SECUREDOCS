# Full Database Schema (Supabase public schema - Updated 2025-09-15 via MCP query tool)

Generated from live Supabase database via MCP queries.

Notes:
- Types follow Postgres naming (int8=bigint, int4=integer, timestamptz=timestamp with time zone).
- PKs, FKs, unique constraints, and check constraints are listed where available.
- Schema includes all current tables and their relationships.

---

## Table: blockchain_configs (public)
- Size: ~24 kB
- Primary key: (id)
- Rows: ~0-5 (small table)

Columns:
- id: bigint, not null, default nextval('blockchain_configs_id_seq')
- user_id: bigint, not null
- provider: character varying(50), not null
- api_key_encrypted: text, not null
- settings: json, nullable
- is_active: boolean, not null, default true
- created_at: timestamp without time zone, nullable
- updated_at: timestamp without time zone, nullable

Foreign keys:
- user_id → users(id) [blockchain_configs_user_id_foreign]

Unique constraints:
- blockchain_configs_user_id_provider_unique (user_id, provider)

---

## Table: blockchain_uploads (public)
- Size: ~32 kB
- Primary key: (id)
- Rows: ~0-10 (small table)

Columns:
- id: bigint, not null, default nextval('blockchain_uploads_id_seq')
- file_id: bigint, not null
- provider: character varying(50), not null
- ipfs_hash: character varying(100), nullable
- upload_status: character varying(255), not null, default 'pending'
- error_message: text, nullable
- upload_cost: numeric, nullable
- provider_response: json, nullable
- created_at: timestamp without time zone, nullable
- updated_at: timestamp without time zone, nullable

Foreign keys:
- file_id → files(id) [blockchain_uploads_file_id_foreign]

Check constraints:
- blockchain_uploads_upload_status_check: CHECK (upload_status IN ('pending','success','failed'))

---

## Table: cache (public)
- Size: ~64 kB
- Primary key: (key)
- Rows: ~10-20 (Laravel cache storage)

Columns:
- key: character varying(255), not null
- value: text, not null
- expiration: integer, not null

---

## Table: cache_locks (public)
- Size: ~16 kB
- Primary key: (key)
- Rows: ~0-5 (Laravel cache locks)

Columns:
- key: character varying(255), not null
- owner: character varying(255), not null
- expiration: integer, not null

---

## Table: daily_activity_stats (public) [NEW - Missing from previous schema]
- Size: ~16 kB
- Primary key: (id)
- Rows: ~0-100 (activity tracking)

Columns:
- id: bigint, not null, default nextval('daily_activity_stats_id_seq')
- user_id: bigint, nullable
- date: date, not null
- files_created: integer, nullable, default 0
- files_updated: integer, nullable, default 0
- files_deleted: integer, nullable, default 0
- files_accessed: integer, nullable, default 0
- login_count: integer, nullable, default 0
- storage_used_bytes: bigint, nullable, default 0
- bandwidth_used_bytes: bigint, nullable, default 0
- session_count: integer, nullable, default 0
- active_time_minutes: integer, nullable, default 0
- unique_files_accessed: integer, nullable, default 0
- created_at: timestamp without time zone, nullable, default now()
- updated_at: timestamp without time zone, nullable, default now()

Foreign keys:
- user_id → users(id) [daily_activity_stats_user_id_fkey]

Unique constraints:
- daily_activity_stats_user_id_date_key (user_id, date)

---

## Table: document_metadata (public)
- Size: ~32 kB
- Primary key: (id)
- Rows: ~3 (small table)

Columns:
- id: text, not null
- title: text, nullable
- url: text, nullable
- created_at: timestamp without time zone, nullable, default now()
- schema: text, nullable
- user_id: bigint, nullable
- file_id: bigint, nullable [NEW - Added for vector relations]

Foreign keys:
- file_id → files(id) [document_metadata_file_fk] [NEW]

Referenced by:
- document_rows.dataset_id → document_metadata.id [document_rows_dataset_id_fkey]

---

## Table: document_rows (public)
- Size: ~16 kB
- Primary key: (id)
- Rows: ~0 (empty table)

Columns:
- id: integer, not null, default nextval('document_rows_id_seq')
- dataset_id: text, nullable
- row_data: jsonb, nullable
- file_id: bigint, nullable [NEW - Added for vector relations]

Foreign keys:
- dataset_id → document_metadata(id) [document_rows_dataset_id_fkey]
- file_id → files(id) [document_rows_file_fk] [NEW]

---

## Table: documents (public)
- Size: ~88 kB
- Primary key: (id)
- Rows: ~4 (small table)

Columns:
- id: bigint, not null, default nextval('documents_id_seq')
- content: text, nullable
- metadata: jsonb, nullable
- embedding: USER-DEFINED (vector), nullable
- user_id: bigint, nullable
- file_id: bigint, nullable [NEW - Added for vector relations]

Foreign keys:
- file_id → files(id) [documents_file_fk] [NEW]

---

## Table: failed_jobs (public)
- Size: ~24 kB
- Primary key: (id)
- Rows: ~0 (Laravel queue failures)

Columns:
- id: bigint, not null, default nextval('failed_jobs_id_seq')
- uuid: character varying(255), not null, unique
- connection: text, not null
- queue: text, not null
- payload: text, not null
- exception: text, not null
- failed_at: timestamp without time zone, not null, default CURRENT_TIMESTAMP

Unique constraints:
- failed_jobs_uuid_unique

---

## Table: file_access_logs (public) [NEW - Missing from previous schema]
- Size: ~32 kB
- Primary key: (id)
- Rows: ~0 (access logging)

Columns:
- id: bigint, not null, default nextval('file_access_logs_id_seq')
- file_id: bigint, not null
- user_id: bigint, not null
- session_id: character varying(255), nullable
- access_type: character varying(255), not null
- access_method: character varying(255), nullable
- file_size_at_access: bigint, nullable
- file_version_at_access: character varying(255), nullable
- ip_address: inet, nullable
- user_agent: text, nullable
- referrer: text, nullable
- response_time_ms: integer, nullable
- bytes_transferred: bigint, nullable
- started_at: timestamp without time zone, nullable, default now()
- completed_at: timestamp without time zone, nullable
- duration_seconds: integer, generated, nullable

Foreign keys:
- file_id → files(id) [file_access_logs_file_id_fkey]
- user_id → users(id) [file_access_logs_user_id_fkey]
- session_id → user_sessions(session_id) [file_access_logs_session_id_fkey]

---

## Table: files (public) [UPDATED - Many new columns added]
- Size: ~80 kB
- Primary key: (id)
- Rows: ~18 (file storage)

Columns:
- id: bigint, not null, default nextval('files_id_seq')
- user_id: bigint, not null
- file_name: character varying, not null [UPDATED - was 'name']
- file_path: character varying, not null [UPDATED - was 'path']
- file_size: character varying, nullable [UPDATED - was 'size_bytes']
- file_type: character varying, nullable
- mime_type: character varying, nullable
- created_at: timestamp without time zone, nullable
- updated_at: timestamp without time zone, nullable
- parent_id: bigint, nullable
- is_folder: boolean, not null, default false
- deleted_at: timestamp with time zone, nullable
- blockchain_provider: character varying, nullable [NEW]
- ipfs_hash: character varying, nullable [NEW]
- blockchain_url: text, nullable [NEW]
- is_blockchain_stored: boolean, not null, default false [NEW]
- blockchain_metadata: json, nullable [NEW]
- is_vectorized: boolean, not null, default false [NEW]
- vectorized_at: timestamp with time zone, nullable [NEW]

Foreign keys:
- user_id → users(id) [files_user_id_foreign]
- parent_id → files(id) [files_parent_id_foreign]
- Referenced by multiple tables for file relations

---

## Table: job_batches (public)
- Size: ~16 kB
- Primary key: (id)
- Rows: ~0 (Laravel job batches)

Columns:
- id: character varying(255), not null
- name: character varying(255), not null
- total_jobs: integer, not null
- pending_jobs: integer, not null
- failed_jobs: integer, not null
- failed_job_ids: text, not null
- options: text, nullable
- cancelled_at: integer, nullable
- created_at: integer, not null
- finished_at: integer, nullable

---

## Table: jobs (public)
- Size: ~48 kB
- Primary key: (id)
- Rows: ~0 (Laravel job queue)

Columns:
- id: bigint, not null, default nextval('jobs_id_seq')
- queue: character varying(255), not null
- payload: text, not null
- attempts: smallint, not null
- reserved_at: integer, nullable
- available_at: integer, not null
- created_at: integer, not null

---

## Table: migrations (public)
- Size: ~24 kB
- Primary key: (id)
- Rows: ~12 (Laravel migrations)

Columns:
- id: integer, not null, default nextval('migrations_id_seq')
- migration: character varying(255), not null
- batch: integer, not null

---

## Table: n8n_chat_histories (public)
- Size: ~128 kB
- Primary key: (id)
- Rows: ~68 (chat history storage)

Columns:
- id: integer, not null, default nextval('n8n_chat_histories_id_seq')
- session_id: character varying(255), not null
- message: jsonb, not null

---

## Table: notifications (public) [NEW - Missing from previous schema]
- Size: ~16 kB
- Primary key: (id)
- Rows: ~0 (user notifications)

Columns:
- id: bigint, not null, default nextval('notifications_id_seq')
- user_id: bigint, not null
- type: character varying(255), not null
- title: character varying(255), not null
- message: text, not null
- data: jsonb, nullable
- read_at: timestamp with time zone, nullable
- created_at: timestamp with time zone, not null, default now()
- updated_at: timestamp with time zone, not null, default now()

Foreign keys:
- user_id → users(id) [notifications_user_id_fkey]

---

## Table: password_reset_tokens (public)
- Size: ~16 kB
- Primary key: (email)
- Rows: ~2 (password reset tokens)

Columns:
- email: character varying(255), not null
- token: character varying(255), not null
- created_at: timestamp without time zone, nullable

---

## Table: personal_access_tokens (public)
- Size: ~64 kB
- Primary key: (id)
- Rows: ~2 (Laravel Sanctum tokens)

Columns:
- id: bigint, not null, default nextval('personal_access_tokens_id_seq')
- tokenable_type: character varying(255), not null
- tokenable_id: bigint, not null
- name: character varying(255), not null
- token: character varying(64), not null, unique
- abilities: text, nullable
- last_used_at: timestamp without time zone, nullable
- expires_at: timestamp without time zone, nullable
- created_at: timestamp without time zone, nullable
- updated_at: timestamp without time zone, nullable

Unique constraints:
- personal_access_tokens_token_unique

---

## Table: saved_searches (public) [NEW - Missing from previous schema]
- Size: ~16 kB
- Primary key: (id)
- Rows: ~0 (saved search queries)

Columns:
- id: bigint, not null, default nextval('saved_searches_id_seq')
- user_id: bigint, not null
- name: character varying(255), not null
- query: text, not null
- filters: jsonb, nullable
- sort_by: character varying(255), nullable, default 'created_at'
- sort_order: character varying(10), nullable, default 'desc'
- is_default: boolean, not null, default false
- is_shared: boolean, not null, default false
- created_at: timestamp without time zone, nullable, default now()
- updated_at: timestamp without time zone, nullable, default now()

Foreign keys:
- user_id → users(id) [saved_searches_user_id_fkey]

---

## Table: search_logs (public) [NEW - Missing from previous schema]
- Size: ~24 kB
- Primary key: (id)
- Rows: ~0 (search activity logs)

Columns:
- id: bigint, not null, default nextval('search_logs_id_seq')
- user_id: bigint, nullable
- session_id: character varying(255), nullable
- query: text, not null
- filters_used: jsonb, nullable
- result_count: integer, nullable
- search_time_ms: integer, nullable
- ip_address: inet, nullable
- user_agent: text, nullable
- referrer: text, nullable
- created_at: timestamp without time zone, nullable, default now()

Foreign keys:
- user_id → users(id) [search_logs_user_id_fkey]

---

## Table: sessions (public)
- Size: ~96 kB
- Primary key: (id)
- Rows: ~5-10 (Laravel sessions)

Columns:
- id: character varying(255), not null
- user_id: bigint, nullable
- ip_address: character varying(45), nullable
- user_agent: text, nullable
- payload: text, not null
- last_activity: integer, not null

---

## Table: system_activities (public) [NEW - Missing from previous schema]
- Size: ~64 kB
- Primary key: (id)
- Rows: ~50-100 (system activity tracking)

Columns:
- id: bigint, not null, default nextval('system_activities_id_seq')
- user_id: bigint, nullable
- target_user_id: bigint, nullable
- file_id: bigint, nullable
- activity_type: character varying(255), not null
- action: character varying(255), not null
- entity_type: character varying(255), nullable
- entity_id: bigint, nullable
- description: text, not null
- metadata: jsonb, nullable
- ip_address: inet, nullable
- user_agent: text, nullable
- session_id: character varying(255), nullable
- location_country: character varying(255), nullable
- location_city: character varying(255), nullable
- device_type: character varying(255), nullable
- browser: character varying(255), nullable
- risk_level: character varying(255), nullable, default 'low'
- requires_audit: boolean, nullable, default false
- is_suspicious: boolean, nullable, default false
- created_at: timestamp without time zone, nullable, default now()

Foreign keys:
- user_id → users(id) [system_activities_user_id_fkey]
- target_user_id → users(id) [system_activities_target_user_id_fkey]
- file_id → files(id) [system_activities_file_id_fkey]

Check constraints:
- system_activities_risk_level_check: CHECK (risk_level IN ('low','medium','high','critical'))

---

## Table: user_sessions (public) [NEW - Missing from previous schema]
- Size: ~32 kB
- Primary key: (id)
- Rows: ~0 (detailed session tracking)

Columns:
- id: bigint, not null, default nextval('user_sessions_id_seq')
- user_id: bigint, not null
- session_id: character varying(255), not null, unique
- ip_address: inet, not null
- user_agent: text, nullable
- device_fingerprint: text, nullable
- location_country: character varying(255), nullable
- location_city: character varying(255), nullable
- location_timezone: character varying(255), nullable
- is_active: boolean, nullable, default true
- last_activity_at: timestamp without time zone, nullable, default now()
- login_method: character varying(255), nullable
- is_suspicious: boolean, nullable, default false
- trusted_device: boolean, nullable, default false
- created_at: timestamp without time zone, nullable, default now()
- expires_at: timestamp without time zone, nullable
- logged_out_at: timestamp without time zone, nullable

Foreign keys:
- user_id → users(id) [user_sessions_user_id_fkey]

Unique constraints:
- user_sessions_session_id_key (session_id)

---

## Table: users (public)
- Size: ~48 kB
- Primary key: (id)
- Rows: ~5-10 (user accounts)

Columns:
- id: bigint, not null, default nextval('users_id_seq')
- name: character varying(255), not null
- email: character varying(255), not null, unique
- email_verified_at: timestamp without time zone, nullable
- password: character varying(255), not null
- remember_token: character varying(100), nullable
- created_at: timestamp without time zone, nullable
- updated_at: timestamp without time zone, nullable
- two_factor_secret: text, nullable
- two_factor_recovery_codes: text, nullable
- two_factor_confirmed_at: timestamp without time zone, nullable
- is_approved: boolean, nullable, default false
- role: character varying(255), not null, default 'user'
- is_premium: boolean, not null, default false
- current_team_id: bigint, nullable
- profile_photo_path: character varying(255), nullable

Unique constraints:
- users_email_unique

Check constraints:
- users_role_check: CHECK (role IN ('user','record admin','admin'))

---

## Table: webauthn_credentials (public)
- Size: ~128 kB
- Primary key: (id)
- Rows: ~5-20 (WebAuthn credentials)

Columns:
- id: text, not null
- raw_id: text, nullable
- response: jsonb, nullable
- type: character varying(255), not null, default 'public-key'
- transports: ARRAY, nullable
- attestation_type: character varying(255), nullable
- trust_path: jsonb, nullable
- aaguid: uuid, nullable
- public_key: text, nullable
- counter: bigint, nullable, default 0
- user_handle: text, nullable
- user_id: text, not null
- authenticatable_type: character varying(255), not null
- authenticatable_id: bigint, not null
- disabled_at: timestamp with time zone, nullable
- created_at: timestamp with time zone, not null
- updated_at: timestamp with time zone, not null
- rp_id: text, not null, default ''
- origin: text, not null, default ''
- alias: character varying(255), nullable
- attestation_format: character varying(255), nullable

---

# Major Relationships Summary

**Core User-Files Relationship:**
- users.id ← files.user_id
- files.parent_id → files.id (self-referential for folder hierarchy)

**Blockchain Integration:**
- blockchain_configs.user_id → users.id
- blockchain_uploads.file_id → files.id

**Document Vectorization:**
- document_metadata.file_id → files.id
- document_rows.file_id → files.id
- documents.file_id → files.id

**Activity Tracking:**
- system_activities.user_id → users.id
- system_activities.target_user_id → users.id
- system_activities.file_id → files.id
- daily_activity_stats.user_id → users.id
- file_access_logs.file_id → files.id
- file_access_logs.user_id → users.id
- file_access_logs.session_id → user_sessions.session_id
- user_sessions.user_id → users.id

**Search & Notifications:**
- saved_searches.user_id → users.id
- search_logs.user_id → users.id
- notifications.user_id → users.id

# Unique Constraints Summary
- blockchain_configs_user_id_provider_unique (blockchain_configs)
- daily_activity_stats_user_id_date_key (daily_activity_stats)
- failed_jobs_uuid_unique (failed_jobs)
- personal_access_tokens_token_unique (personal_access_tokens)
- user_sessions_session_id_key (user_sessions)
- users_email_unique (users)

# Check Constraints Summary
- blockchain_uploads_upload_status_check (blockchain_uploads)
- system_activities_risk_level_check (system_activities)
- users_role_check (users)

---

## Recent Schema Changes (2025-09-15 Update)

### Added Tables (missing from previous schema documentation):
- `daily_activity_stats` - Daily user activity aggregation
- `file_access_logs` - Detailed file access logging
- `notifications` - User notification system
- `saved_searches` - Persistent search queries
- `search_logs` - Search activity tracking
- `system_activities` - Comprehensive activity logging
- `user_sessions` - Enhanced session management

### Updated Tables:
- `files` - Renamed columns (name→file_name, path→file_path, size_bytes→file_size) + added blockchain/vectorization fields
- `document_metadata` - Added file_id for vector relations
- `document_rows` - Added file_id for vector relations
- `documents` - Added file_id for vector relations
- `users` - Added current_team_id and profile_photo_path

### New Relationships:
- All document tables now link to files via file_id
- Enhanced activity tracking with cross-references
- Session-based access logging
- Notification system integration

### Data Insights:
- Total tables: 25 (up from 17 in previous schema)
- Most active tables: files (18 rows), system_activities (~50-100 rows), n8n_chat_histories (68 rows)
- New features supported: advanced analytics, detailed logging, vector search, notifications

This schema supports a comprehensive document management system with blockchain storage, AI vectorization, activity tracking, and advanced user management features.
