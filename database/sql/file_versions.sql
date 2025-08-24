-- File Versions and History tables for SECUREDOCS
-- Run this in your Supabase SQL editor

-- Table for storing file version history
CREATE TABLE file_versions (
    id BIGSERIAL PRIMARY KEY,
    file_id BIGINT NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    version_number INTEGER NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL, -- Path to the version in storage
    file_size BIGINT DEFAULT 0,
    file_type VARCHAR(50),
    mime_type VARCHAR(100),
    checksum VARCHAR(64), -- Hash of file content for deduplication
    upload_source VARCHAR(50) DEFAULT 'web', -- web, api, mobile, etc.
    version_comment TEXT, -- Optional comment when creating version
    is_current BOOLEAN DEFAULT false, -- Mark the current active version
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now(),
    
    -- Ensure version numbers are sequential per file
    UNIQUE(file_id, version_number),
    
    -- Index for performance
    INDEX idx_file_versions_file_id (file_id),
    INDEX idx_file_versions_current (file_id, is_current),
    INDEX idx_file_versions_checksum (checksum)
);

-- Table for tracking file activities/changes
CREATE TABLE file_activities (
    id BIGSERIAL PRIMARY KEY,
    file_id BIGINT NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    action VARCHAR(50) NOT NULL, -- created, updated, deleted, restored, shared, renamed, moved
    details JSONB, -- Additional details about the action
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT now(),
    
    -- Indexes for performance
    INDEX idx_file_activities_file_id (file_id),
    INDEX idx_file_activities_user_id (user_id),
    INDEX idx_file_activities_action (action),
    INDEX idx_file_activities_created_at (created_at)
);

-- Add updated_at trigger for file_versions
CREATE TRIGGER update_file_versions_updated_at BEFORE UPDATE
    ON file_versions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to automatically create file versions when files are updated
CREATE OR REPLACE FUNCTION create_file_version()
RETURNS TRIGGER AS $$
DECLARE
    next_version INTEGER;
    old_path VARCHAR(500);
BEGIN
    -- Only create versions for actual files, not folders
    IF NEW.is_folder = false AND OLD.file_path != NEW.file_path THEN
        -- Get the next version number
        SELECT COALESCE(MAX(version_number), 0) + 1 
        INTO next_version
        FROM file_versions 
        WHERE file_id = NEW.id;
        
        -- Mark previous current version as not current
        UPDATE file_versions 
        SET is_current = false 
        WHERE file_id = NEW.id AND is_current = true;
        
        -- Create new version record
        INSERT INTO file_versions (
            file_id, user_id, version_number, file_name, file_path,
            file_size, file_type, mime_type, is_current
        ) VALUES (
            NEW.id, NEW.user_id, next_version, NEW.file_name, NEW.file_path,
            NEW.file_size, NEW.file_type, NEW.mime_type, true
        );
        
        -- Log the activity
        INSERT INTO file_activities (file_id, user_id, action, details)
        VALUES (NEW.id, NEW.user_id, 'version_created', 
                json_build_object('version_number', next_version, 'file_size', NEW.file_size));
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for automatic version creation
CREATE TRIGGER file_version_trigger
    AFTER UPDATE ON files
    FOR EACH ROW
    EXECUTE FUNCTION create_file_version();

-- Function to clean up old versions (keep last N versions)
CREATE OR REPLACE FUNCTION cleanup_old_versions(file_id_param BIGINT, keep_versions INTEGER DEFAULT 10)
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER := 0;
BEGIN
    -- Delete versions beyond the keep limit
    WITH versions_to_delete AS (
        SELECT id, file_path
        FROM file_versions
        WHERE file_id = file_id_param
        AND is_current = false
        ORDER BY version_number DESC
        OFFSET keep_versions
    )
    DELETE FROM file_versions
    WHERE id IN (SELECT id FROM versions_to_delete);
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

-- View for easy access to file version history with user info
CREATE VIEW file_version_history AS
SELECT 
    fv.*,
    u.name as user_name,
    u.email as user_email,
    f.file_name as current_file_name
FROM file_versions fv
JOIN users u ON fv.user_id = u.id
JOIN files f ON fv.file_id = f.id
ORDER BY fv.file_id, fv.version_number DESC;
