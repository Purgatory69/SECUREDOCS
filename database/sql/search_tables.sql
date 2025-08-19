-- Search functionality tables for SECUREDOCS
-- Run this in your Supabase SQL editor

-- Table for tracking search queries for analytics and improving suggestions
CREATE TABLE search_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    query VARCHAR(255) NOT NULL,
    result_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT now()
);

-- Table for saved searches
CREATE TABLE saved_searches (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    query VARCHAR(255) NOT NULL,
    filters JSONB,
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now(),
    
    -- Prevent duplicate saved search names for same user
    UNIQUE(user_id, name)
);

-- Create indexes for performance
CREATE INDEX idx_search_logs_user_id ON search_logs(user_id);
CREATE INDEX idx_search_logs_created_at ON search_logs(created_at);
CREATE INDEX idx_saved_searches_user_id ON saved_searches(user_id);

-- Add updated_at trigger for saved_searches
CREATE TRIGGER update_saved_searches_updated_at BEFORE UPDATE
    ON saved_searches FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Add full-text search capability to files table (optional performance improvement)
-- This creates a searchable text index for file names and types
CREATE INDEX idx_files_search_text ON files USING gin(to_tsvector('english', 
    COALESCE(file_name, '') || ' ' || COALESCE(file_type, '') || ' ' || COALESCE(mime_type, '')
));

-- Add index for common search filters
CREATE INDEX idx_files_user_type ON files(user_id, file_type);
CREATE INDEX idx_files_user_size ON files(user_id, file_size);
CREATE INDEX idx_files_user_created ON files(user_id, created_at);
CREATE INDEX idx_files_user_updated ON files(user_id, updated_at);
CREATE INDEX idx_files_user_folder ON files(user_id, is_folder);
