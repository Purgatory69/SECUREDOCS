# Full Database Schema (Supabase public schema)

Generated from live database via MCP at generation time.

Notes:
- Types follow Postgres naming (int8=bigint, int4=integer, timestamptz=timestamp with time zone).
- PKs, FKs, unique constraints, and check constraints are listed where available.

---

## Table: blockchain_configs (public)
- Size: 24 kB
- Primary key: (id)

Columns:
- id: int8, not null, default nextval('blockchain_configs_id_seq')
- user_id: int8, not null
- provider: varchar(50), not null
- api_key_encrypted: text, not null
- settings: json, nullable
- is_active: bool, not null, default true
- created_at: timestamp, nullable
- updated_at: timestamp, nullable

Foreign keys:
- user_id → users(id) [blockchain_configs_user_id_foreign]

Unique constraints:
- blockchain_configs_user_id_provider_unique (details not expanded)

---

## Table: blockchain_uploads (public)
- Size: 32 kB
- Primary key: (id)

Columns:
- id: int8, not null, default nextval('blockchain_uploads_id_seq')
- file_id: int8, not null
- provider: varchar(50), not null
- ipfs_hash: varchar(100), nullable
- upload_status: varchar(255), not null, default 'pending'
- error_message: text, nullable
- upload_cost: numeric, nullable
- provider_response: json, nullable
- created_at: timestamp, nullable
- updated_at: timestamp, nullable

Foreign keys:
- file_id → files(id) [blockchain_uploads_file_id_foreign]

Check constraints:
- blockchain_uploads_upload_status_check: CHECK (upload_status IN ('pending','success','failed'))

---

## Table: cache (public)
- Size: 64 kB
- Primary key: (key)

Columns:
- key: varchar(255), not null
- value: text, not null
- expiration: int4, not null

---

## Table: cache_locks (public)
- Size: 16 kB
- Primary key: (key)

Columns:
- key: varchar(255), not null
- owner: varchar(255), not null
- expiration: int4, not null

---

## Table: document_metadata (public)
- Size: 32 kB
- Primary key: (id)

Columns:
- id: text, not null
- title: text, nullable
- url: text, nullable
- created_at: timestamp, nullable, default now()
- schema: text, nullable
- user_id: int8, nullable

Referenced by:
- document_rows.dataset_id → document_metadata.id [document_rows_dataset_id_fkey]

---

## Table: document_rows (public)
- Size: 16 kB
- Primary key: (id)

Columns:
- id: int4, not null, default nextval('document_rows_id_seq')
- dataset_id: text, nullable
- row_data: jsonb, nullable

Foreign keys:
- dataset_id → document_metadata(id) [document_rows_dataset_id_fkey]

---

## Table: documents (public)
- Size: 88 kB
- Primary key: (id)

Columns:
- id: int8, not null, default nextval('documents_id_seq')
- content: text, nullable
- metadata: jsonb, nullable
- embedding: vector (user-defined), nullable
- user_id: int8, nullable

---

## Table: failed_jobs (public)
- Size: 24 kB
- Primary key: (id)

Columns:
- id: int8, not null, default nextval('failed_jobs_id_seq')
- uuid: varchar(255), not null, unique [failed_jobs_uuid_unique]
- connection: text, not null
- queue: text, not null
- payload: text, not null
- exception: text, not null
- failed_at: timestamp, not null, default now()

Unique constraints:
- failed_jobs_uuid_unique

---

## Table: files (public)
- Size: 80 kB
- Primary key: (id)

Columns:
- id: int8, not null
- user_id: int8, not null
- parent_id: int8, nullable
- name: varchar, not null
- path: text, not null
- size_bytes: int8, nullable
- mime_type: varchar, nullable
- is_folder: bool, not null, default false
- is_encrypted: bool, not null, default false
- created_at: timestamp, nullable
- updated_at: timestamp, nullable
- deleted_at: timestamp, nullable (soft deletes)

Foreign keys:
- user_id → users(id) [files_user_id_foreign]
- parent_id → files(id) [files_parent_id_foreign]

Note: Columns inferred from relationships and typical Laravel files table; adjust if needed.

---

## Table: job_batches (public)
- Size: 16 kB
- Primary key: (id)

Columns: (standard Laravel)
- id: varchar(255), not null
- name: varchar(255), not null
- total_jobs: int4, not null
- pending_jobs: int4, not null
- failed_jobs: int4, not null
- failed_job_ids: text, not null
- options: json, nullable
- cancelled_at: timestamp, nullable
- created_at: timestamp, nullable
- finished_at: timestamp, nullable

---

## Table: jobs (public)
- Size: 48 kB
- Primary key: (id)

Columns: (standard Laravel)
- id: int8, not null
- queue: varchar(255), not null
- payload: text, not null
- attempts: int4, not null
- reserved_at: int4, nullable
- available_at: int4, not null
- created_at: int4, not null

---

## Table: migrations (public)
- Size: 24 kB
- Primary key: (id)

Columns:
- id: int8, not null
- migration: varchar(255), not null
- batch: int4, not null

---

## Table: n8n_chat_histories (public)
- Size: 128 kB
- Primary key: (id)

Columns: (typical shape; confirm as needed)
- id: int8, not null
- user_id: int8, nullable
- session_id: varchar(255), nullable
- message: text, nullable
- role: varchar(50), nullable
- metadata: jsonb, nullable
- created_at: timestamp, nullable
- updated_at: timestamp, nullable

---

## Table: password_reset_tokens (public)
- Size: 16 kB
- Primary key: (email)

Columns:
- email: varchar(255), not null
- token: varchar(255), not null
- created_at: timestamp, nullable

---

## Table: personal_access_tokens (public)
- Size: 64 kB
- Primary key: (id)

Columns:
- id: int8, not null
- tokenable_type: varchar(255), not null
- tokenable_id: int8, not null
- name: varchar(255), not null
- token: varchar(64), not null, unique [personal_access_tokens_token_unique]
- abilities: text, nullable
- last_used_at: timestamp, nullable
- expires_at: timestamp, nullable
- created_at: timestamp, nullable
- updated_at: timestamp, nullable

Unique constraints:
- personal_access_tokens_token_unique

---

## Table: sessions (public)
- Size: 96 kB
- Primary key: (id)

Columns:
- id: varchar(255), not null
- user_id: int8, nullable
- ip_address: varchar(45), nullable
- user_agent: text, nullable
- payload: text, not null
- last_activity: int4, not null

---

## Table: users (public)
- Size: 48 kB
- Primary key: (id)

Columns:
- id: int8, not null, default nextval('users_id_seq')
- name: varchar(255), not null
- email: varchar(255), not null, unique [users_email_unique]
- email_verified_at: timestamp, nullable
- password: varchar(255), not null
- remember_token: varchar(100), nullable
- created_at: timestamp, nullable
- updated_at: timestamp, nullable
- two_factor_secret: text, nullable
- two_factor_recovery_codes: text, nullable
- two_factor_confirmed_at: timestamp, nullable
- is_approved: bool, nullable, default false
- role: varchar(255), not null, default 'user'
- is_premium: bool, not null, default false

Unique constraints:
- users_email_unique

Check constraints:
- users_role_check: CHECK (role IN ('user','record admin','admin'))

---

## Table: webauthn_credentials (public)
- Size: 128 kB
- Primary key: (id)
- Comment: Stores WebAuthn credentials for user authentication

Columns:
- id: text, not null
- raw_id: text, nullable
- response: jsonb, nullable
- type: varchar(255), not null, default 'public-key'
- transports: text[], nullable
- attestation_type: varchar(255), nullable
- trust_path: jsonb, nullable
- aaguid: uuid, nullable
- public_key: text, nullable
- counter: int8, nullable, default 0
- user_handle: text, nullable
- user_id: text, not null
- authenticatable_type: varchar(255), not null
- authenticatable_id: int8, not null
- disabled_at: timestamptz, nullable
- created_at: timestamptz, not null
- updated_at: timestamptz, not null
- rp_id: text, not null, default ''
- origin: text, not null, default ''
- alias: varchar(255), nullable
- attestation_format: varchar(255), nullable

---

# Relationships summary
- blockchain_configs.user_id → users.id
- blockchain_uploads.file_id → files.id
- document_rows.dataset_id → document_metadata.id
- files.parent_id → files.id (self-referential)
- files.user_id → users.id

# Unique constraints summary
- blockchain_configs_user_id_provider_unique (blockchain_configs)
- failed_jobs_uuid_unique (failed_jobs)
- personal_access_tokens_token_unique (personal_access_tokens)
- users_email_unique (users)

# Check constraints summary
- blockchain_uploads_upload_status_check (blockchain_uploads)
- users_role_check (users)

---

## Schema changes (2025-08-16)

- Removed columns related to sharing/comments to align with backend cleanup:
  - Dropped from `public.files`: `shared_drive_id`, `shared_drive_path`.
  - Dropped from `public.daily_activity_stats`: `files_shared`, `comments_created`.
  - Dropped from `public.security_metrics`: `files_shared`.
  - Dropped index `public.idx_files_shared_drive`.

- Updated view `public.file_activity_summary` to remove `share_count` and `comment_count`. The view now returns:
  - `file_id`, `file_name`, `owner_id`, `total_activities`, `unique_users`, `access_count`, `last_activity_at`, `activities_24h`.

- Updated function `public.update_daily_stats()` to stop referencing removed counters. It now aggregates only:
  - `files_created`, `files_updated`, `files_deleted`, `files_accessed`.

Note: These changes are codified in migration `supabase/migrations/20250816223000_remove_sharing_comments_columns.sql` and mirrored in `database/sql/activity_tracking.sql`.
