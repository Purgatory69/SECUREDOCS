-- File Sharing Tables for SECUREDOCS
-- Run this in your Supabase SQL editor

-- Create enum for sharing roles
CREATE TYPE sharing_role AS ENUM ('viewer', 'commenter', 'editor');

-- Table for individual file shares (user-to-user)
CREATE TABLE file_shares (
    id BIGSERIAL PRIMARY KEY,
    file_id BIGINT NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    shared_with_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    role sharing_role NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now(),
    
    -- Prevent duplicate shares to same user
    UNIQUE(file_id, shared_with_id)
);

-- Table for shareable links (anyone with link)
CREATE TABLE file_share_links (
    id BIGSERIAL PRIMARY KEY,
    file_id BIGINT NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role sharing_role NOT NULL DEFAULT 'viewer',
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP,
    max_accesses INTEGER,
    current_accesses INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now()
);

-- Create indexes for performance
CREATE INDEX idx_file_shares_file_id ON file_shares(file_id);
CREATE INDEX idx_file_shares_shared_with_id ON file_shares(shared_with_id);
CREATE INDEX idx_file_share_links_token ON file_share_links(token);
CREATE INDEX idx_file_share_links_file_id ON file_share_links(file_id);

-- Add updated_at triggers
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_file_shares_updated_at BEFORE UPDATE
    ON file_shares FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_file_share_links_updated_at BEFORE UPDATE
    ON file_share_links FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
