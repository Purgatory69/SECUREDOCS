-- Comprehensive Activity Tracking & Audit Logs for SECUREDOCS
-- Run this in your Supabase SQL editor

-- Main activity logs table
CREATE TABLE system_activities (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    file_id BIGINT REFERENCES files(id) ON DELETE CASCADE,
    target_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL, -- For activities involving another user
    
    -- Activity classification
    activity_type VARCHAR(50) NOT NULL, -- 'file', 'sharing', 'comment', 'system', 'auth', 'collaboration'
    action VARCHAR(50) NOT NULL, -- 'created', 'updated', 'deleted', 'shared', 'accessed', etc.
    
    -- Activity details
    entity_type VARCHAR(50), -- 'file', 'folder', 'comment', 'share', 'user', 'system'
    entity_id BIGINT, -- ID of the entity being acted upon
    
    -- Contextual information
    description TEXT NOT NULL, -- Human-readable description
    metadata JSONB, -- Additional structured data
    
    -- Request/System context
    ip_address INET,
    user_agent TEXT,
    session_id VARCHAR(255),
    
    -- Geographic and device info
    location_country VARCHAR(2),
    location_city VARCHAR(100),
    device_type VARCHAR(50), -- 'desktop', 'mobile', 'tablet', 'api'
    browser VARCHAR(100),
    
    -- Security and compliance
    risk_level VARCHAR(20) DEFAULT 'low' CHECK (risk_level IN ('low', 'medium', 'high', 'critical')),
    requires_audit BOOLEAN DEFAULT FALSE,
    is_suspicious BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT now(),
    
    -- Indexes for performance
    INDEX idx_system_activities_user_id (user_id),
    INDEX idx_system_activities_file_id (file_id),
    INDEX idx_system_activities_type_action (activity_type, action),
    INDEX idx_system_activities_created_at (created_at),
    INDEX idx_system_activities_risk_level (risk_level),
    INDEX idx_system_activities_audit (requires_audit, is_suspicious)
);

-- Create indexes for system_activities table
CREATE INDEX idx_system_activities_user_id ON system_activities(user_id);
CREATE INDEX idx_system_activities_file_id ON system_activities(file_id);
CREATE INDEX idx_system_activities_type_action ON system_activities(activity_type, action);
CREATE INDEX idx_system_activities_created_at ON system_activities(created_at);
CREATE INDEX idx_system_activities_risk_level ON system_activities(risk_level);
CREATE INDEX idx_system_activities_audit ON system_activities(requires_audit, is_suspicious);

-- User sessions tracking for security
CREATE TABLE user_sessions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    
    -- Session info
    ip_address INET NOT NULL,
    user_agent TEXT,
    device_fingerprint TEXT,
    
    -- Geographic data
    location_country VARCHAR(2),
    location_city VARCHAR(100),
    location_timezone VARCHAR(50),
    
    -- Session status
    is_active BOOLEAN DEFAULT TRUE,
    last_activity_at TIMESTAMP DEFAULT now(),
    login_method VARCHAR(50), -- 'password', 'webauthn', 'oauth', 'api'
    
    -- Security flags
    is_suspicious BOOLEAN DEFAULT FALSE,
    trusted_device BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT now(),
    expires_at TIMESTAMP,
    logged_out_at TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_sessions_user_id (user_id),
    INDEX idx_user_sessions_session_id (session_id),
    INDEX idx_user_sessions_active (is_active, last_activity_at),
    INDEX idx_user_sessions_suspicious (is_suspicious)
);

-- Create indexes for user_sessions table
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_session_id ON user_sessions(session_id);
CREATE INDEX idx_user_sessions_active ON user_sessions(is_active, last_activity_at);
CREATE INDEX idx_user_sessions_suspicious ON user_sessions(is_suspicious);

-- File access logs for detailed file activity tracking
CREATE TABLE file_access_logs (
    id BIGSERIAL PRIMARY KEY,
    file_id BIGINT NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255) REFERENCES user_sessions(session_id) ON DELETE SET NULL,
    
    -- Access details
    access_type VARCHAR(50) NOT NULL, -- 'view', 'download', 'edit', 'preview', 'share', 'comment'
    access_method VARCHAR(50), -- 'web', 'api', 'mobile', 'preview'
    
    -- File state at access time
    file_size_at_access BIGINT,
    file_version_at_access VARCHAR(50),
    
    -- Request details
    ip_address INET,
    user_agent TEXT,
    referrer TEXT,
    
    -- Performance metrics
    response_time_ms INTEGER,
    bytes_transferred BIGINT,
    
    -- Timestamps
    started_at TIMESTAMP DEFAULT now(),
    completed_at TIMESTAMP,
    duration_seconds INTEGER GENERATED ALWAYS AS (EXTRACT(EPOCH FROM (completed_at - started_at))) STORED,
    
    -- Indexes
    INDEX idx_file_access_logs_file_id (file_id),
    INDEX idx_file_access_logs_user_id (user_id),
    INDEX idx_file_access_logs_access_type (access_type),
    INDEX idx_file_access_logs_started_at (started_at)
);

-- Create indexes for file_access_logs table
CREATE INDEX idx_file_access_logs_file_id ON file_access_logs(file_id);
CREATE INDEX idx_file_access_logs_user_id ON file_access_logs(user_id);
CREATE INDEX idx_file_access_logs_access_type ON file_access_logs(access_type);
CREATE INDEX idx_file_access_logs_started_at ON file_access_logs(started_at);

-- System security events
CREATE TABLE security_events (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    
    -- Event classification
    event_type VARCHAR(50) NOT NULL, -- 'login_failed', 'suspicious_activity', 'permission_denied', 'brute_force', etc.
    severity VARCHAR(20) NOT NULL CHECK (severity IN ('info', 'warning', 'error', 'critical')),
    
    -- Event details
    description TEXT NOT NULL,
    details JSONB,
    
    -- Context
    ip_address INET,
    user_agent TEXT,
    endpoint VARCHAR(255),
    
    -- Status
    resolved BOOLEAN DEFAULT FALSE,
    resolved_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    resolved_at TIMESTAMP,
    resolution_notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT now(),
    
    -- Indexes
    INDEX idx_security_events_user_id (user_id),
    INDEX idx_security_events_type_severity (event_type, severity),
    INDEX idx_security_events_created_at (created_at),
    INDEX idx_security_events_unresolved (resolved)
);

-- Create indexes for security_events table
CREATE INDEX idx_security_events_user_id ON security_events(user_id);
CREATE INDEX idx_security_events_type_severity ON security_events(event_type, severity);
CREATE INDEX idx_security_events_created_at ON security_events(created_at);
CREATE INDEX idx_security_events_unresolved ON security_events(resolved);

-- Activity aggregations for performance (daily summaries)
CREATE TABLE daily_activity_stats (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    
    -- Activity counters
    files_created INTEGER DEFAULT 0,
    files_updated INTEGER DEFAULT 0,
    files_deleted INTEGER DEFAULT 0,
    files_accessed INTEGER DEFAULT 0,
    login_count INTEGER DEFAULT 0,
    
    -- Storage metrics
    storage_used_bytes BIGINT DEFAULT 0,
    bandwidth_used_bytes BIGINT DEFAULT 0,
    
    -- Engagement metrics
    session_count INTEGER DEFAULT 0,
    active_time_minutes INTEGER DEFAULT 0,
    unique_files_accessed INTEGER DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now(),
    
    -- Unique constraint
    UNIQUE(user_id, date),
    
    -- Indexes
    INDEX idx_daily_stats_user_date (user_id, date),
    INDEX idx_daily_stats_date (date)
);

-- Create indexes for daily_activity_stats table
CREATE INDEX idx_daily_stats_user_date ON daily_activity_stats(user_id, date);
CREATE INDEX idx_daily_stats_date ON daily_activity_stats(date);

-- Views for common activity queries
CREATE VIEW recent_activities AS
SELECT 
    sa.*,
    u.name as user_name,
    u.email as user_email,
    f.file_name,
    target_user.name as target_user_name
FROM system_activities sa
JOIN users u ON sa.user_id = u.id
LEFT JOIN files f ON sa.file_id = f.id
LEFT JOIN users target_user ON sa.target_user_id = target_user.id
ORDER BY sa.created_at DESC;

CREATE VIEW user_activity_summary AS
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    COUNT(sa.id) as total_activities,
    COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '24 hours' THEN 1 END) as activities_24h,
    COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '7 days' THEN 1 END) as activities_7d,
    COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '30 days' THEN 1 END) as activities_30d,
    MAX(sa.created_at) as last_activity_at,
    COUNT(CASE WHEN sa.risk_level = 'high' OR sa.risk_level = 'critical' THEN 1 END) as high_risk_activities
FROM users u
LEFT JOIN system_activities sa ON u.id = sa.user_id
GROUP BY u.id, u.name, u.email;

CREATE VIEW file_activity_summary AS
SELECT 
    f.id as file_id,
    f.file_name,
    f.user_id as owner_id,
    COUNT(sa.id) as total_activities,
    COUNT(DISTINCT sa.user_id) as unique_users,
    COUNT(CASE WHEN sa.action = 'accessed' THEN 1 END) as access_count,
    MAX(sa.created_at) as last_activity_at,
    COUNT(CASE WHEN sa.created_at >= now() - INTERVAL '24 hours' THEN 1 END) as activities_24h
FROM files f
LEFT JOIN system_activities sa ON f.id = sa.file_id
GROUP BY f.id, f.file_name, f.user_id;

-- Function to log activity
CREATE OR REPLACE FUNCTION log_activity(
    p_user_id BIGINT,
    p_activity_type VARCHAR(50),
    p_action VARCHAR(50),
    p_description TEXT,
    p_file_id BIGINT DEFAULT NULL,
    p_entity_type VARCHAR(50) DEFAULT NULL,
    p_entity_id BIGINT DEFAULT NULL,
    p_metadata JSONB DEFAULT NULL,
    p_risk_level VARCHAR(20) DEFAULT 'low',
    p_ip_address INET DEFAULT NULL,
    p_user_agent TEXT DEFAULT NULL
)
RETURNS BIGINT AS $$
DECLARE
    activity_id BIGINT;
BEGIN
    INSERT INTO system_activities (
        user_id, activity_type, action, description, file_id,
        entity_type, entity_id, metadata, risk_level, ip_address, user_agent
    ) VALUES (
        p_user_id, p_activity_type, p_action, p_description, p_file_id,
        p_entity_type, p_entity_id, p_metadata, p_risk_level, p_ip_address, p_user_agent
    ) RETURNING id INTO activity_id;
    
    RETURN activity_id;
END;
$$ LANGUAGE plpgsql;

-- Function to update daily stats
CREATE OR REPLACE FUNCTION update_daily_stats()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO daily_activity_stats (user_id, date, files_created, files_updated, files_deleted, files_accessed)
    VALUES (
        NEW.user_id, 
        DATE(NEW.created_at),
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'created' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'updated' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'deleted' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'accessed' THEN 1 ELSE 0 END
    )
    ON CONFLICT (user_id, date) DO UPDATE SET
        files_created = daily_activity_stats.files_created + EXCLUDED.files_created,
        files_updated = daily_activity_stats.files_updated + EXCLUDED.files_updated,
        files_deleted = daily_activity_stats.files_deleted + EXCLUDED.files_deleted,
        files_accessed = daily_activity_stats.files_accessed + EXCLUDED.files_accessed,
        updated_at = now();
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to automatically update daily stats
CREATE TRIGGER update_daily_stats_trigger
    AFTER INSERT ON system_activities
    FOR EACH ROW
    EXECUTE FUNCTION update_daily_stats();

-- Function to clean old activity logs (data retention)
CREATE OR REPLACE FUNCTION cleanup_old_activities(retention_days INTEGER DEFAULT 365)
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    -- Delete old system activities (except high-risk ones)
    DELETE FROM system_activities 
    WHERE created_at < now() - INTERVAL '1 day' * retention_days 
      AND risk_level NOT IN ('high', 'critical')
      AND requires_audit = FALSE;
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    
    -- Delete old file access logs
    DELETE FROM file_access_logs 
    WHERE started_at < now() - INTERVAL '1 day' * retention_days;
    
    -- Delete old inactive sessions
    DELETE FROM user_sessions 
    WHERE last_activity_at < now() - INTERVAL '30 days'
      AND is_active = FALSE;
    
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

-- Function to detect suspicious activity patterns
CREATE OR REPLACE FUNCTION detect_suspicious_activity()
RETURNS TABLE(user_id BIGINT, suspicion_reason TEXT, activity_count BIGINT) AS $$
BEGIN
    -- Multiple failed logins
    RETURN QUERY
    SELECT se.user_id, 'Multiple failed login attempts' as suspicion_reason, COUNT(*) as activity_count
    FROM security_events se
    WHERE se.event_type = 'login_failed'
      AND se.created_at >= now() - INTERVAL '1 hour'
      AND se.user_id IS NOT NULL
    GROUP BY se.user_id
    HAVING COUNT(*) >= 5;
    
    -- Unusual download patterns
    RETURN QUERY
    SELECT sa.user_id, 'Excessive file downloads' as suspicion_reason, COUNT(*) as activity_count
    FROM system_activities sa
    WHERE sa.activity_type = 'file'
      AND sa.action = 'downloaded'
      AND sa.created_at >= now() - INTERVAL '1 hour'
    GROUP BY sa.user_id
    HAVING COUNT(*) >= 50;
    
    -- Unusual sharing patterns
    RETURN QUERY
    SELECT sa.user_id, 'Excessive file sharing' as suspicion_reason, COUNT(*) as activity_count
    FROM system_activities sa
    WHERE sa.activity_type = 'sharing'
      AND sa.created_at >= now() - INTERVAL '1 hour'
    GROUP BY sa.user_id
    HAVING COUNT(*) >= 20;
END;
$$ LANGUAGE plpgsql;
