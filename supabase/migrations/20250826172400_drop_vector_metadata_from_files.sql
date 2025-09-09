-- 2025-08-26: Drop vector_metadata from public.files
-- Reason: keep vector details in document_metadata; reduce exposure on files table.

ALTER TABLE public.files
DROP COLUMN IF EXISTS vector_metadata;
