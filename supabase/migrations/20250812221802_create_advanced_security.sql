-- Comprehensive Activity Tracking & Audit Logs for SECUREDOCS
-- Run this in your Supabase SQL editor

-- Function to automatically update 'updated_at' columns
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Main activity logs table
CREATE TABLE IF NOT EXISTS system_activities (
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
    created_at TIMESTAMP DEFAULT now()
);

-- Create indexes for system_activities table
CREATE INDEX IF NOT EXISTS idx_system_activities_user_id ON system_activities(user_id);
CREATE INDEX IF NOT EXISTS idx_system_activities_file_id ON system_activities(file_id);
CREATE INDEX IF NOT EXISTS idx_system_activities_type_action ON system_activities(activity_type, action);
CREATE INDEX IF NOT EXISTS idx_system_activities_created_at ON system_activities(created_at);
CREATE INDEX IF NOT EXISTS idx_system_activities_risk_level ON system_activities(risk_level);
CREATE INDEX IF NOT EXISTS idx_system_activities_audit ON system_activities(requires_audit, is_suspicious);

-- User sessions tracking for security
CREATE TABLE IF NOT EXISTS user_sessions (
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
    logged_out_at TIMESTAMP
);

-- Create indexes for user_sessions table
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_session_id ON user_sessions(session_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_active ON user_sessions(is_active, last_activity_at);
CREATE INDEX IF NOT EXISTS idx_user_sessions_suspicious ON user_sessions(is_suspicious);

-- File access logs for detailed file activity tracking
CREATE TABLE IF NOT EXISTS file_access_logs (
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
    duration_seconds INTEGER GENERATED ALWAYS AS (EXTRACT(EPOCH FROM (completed_at - started_at))) STORED
);

-- Create indexes for file_access_logs table
CREATE INDEX IF NOT EXISTS idx_file_access_logs_file_id ON file_access_logs(file_id);
CREATE INDEX IF NOT EXISTS idx_file_access_logs_user_id ON file_access_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_file_access_logs_access_type ON file_access_logs(access_type);
CREATE INDEX IF NOT EXISTS idx_file_access_logs_started_at ON file_access_logs(started_at);

-- System security events
CREATE TABLE IF NOT EXISTS security_events (
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
    created_at TIMESTAMP DEFAULT now()
);

-- Create indexes for security_events table
CREATE INDEX IF NOT EXISTS idx_security_events_user_id ON security_events(user_id);
CREATE INDEX IF NOT EXISTS idx_security_events_type_severity ON security_events(event_type, severity);
CREATE INDEX IF NOT EXISTS idx_security_events_created_at ON security_events(created_at);
CREATE INDEX IF NOT EXISTS idx_security_events_unresolved ON security_events(resolved);

-- Activity aggregations for performance (daily summaries)
CREATE TABLE IF NOT EXISTS daily_activity_stats (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    
    -- Activity counters
    files_created INTEGER DEFAULT 0,
    files_updated INTEGER DEFAULT 0,
    files_deleted INTEGER DEFAULT 0,
    files_shared INTEGER DEFAULT 0,
    files_accessed INTEGER DEFAULT 0,
    comments_created INTEGER DEFAULT 0,
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
    UNIQUE(user_id, date)
);

-- Create indexes for daily_activity_stats table
CREATE INDEX IF NOT EXISTS idx_daily_stats_user_date ON daily_activity_stats(user_id, date);
CREATE INDEX IF NOT EXISTS idx_daily_stats_date ON daily_activity_stats(date);

-- Views for common activity queries
CREATE OR REPLACE VIEW recent_activities AS
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

CREATE OR REPLACE VIEW user_activity_summary AS
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

CREATE OR REPLACE VIEW file_activity_summary AS
SELECT 
    f.id as file_id,
    f.file_name,
    f.user_id as owner_id,
    COUNT(sa.id) as total_activities,
    COUNT(DISTINCT sa.user_id) as unique_users,
    COUNT(CASE WHEN sa.action = 'accessed' THEN 1 END) as access_count,
    COUNT(CASE WHEN sa.action = 'shared' THEN 1 END) as share_count,
    COUNT(CASE WHEN sa.action = 'commented' THEN 1 END) as comment_count,
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
    INSERT INTO daily_activity_stats (user_id, date, files_created, files_updated, files_deleted, files_shared, files_accessed, comments_created)
    VALUES (
        NEW.user_id, 
        DATE(NEW.created_at),
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'created' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'updated' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'deleted' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'sharing' AND NEW.action = 'shared' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'file' AND NEW.action = 'accessed' THEN 1 ELSE 0 END,
        CASE WHEN NEW.activity_type = 'comment' AND NEW.action = 'created' THEN 1 ELSE 0 END
    )
    ON CONFLICT (user_id, date) DO UPDATE SET
        files_created = daily_activity_stats.files_created + EXCLUDED.files_created,
        files_updated = daily_activity_stats.files_updated + EXCLUDED.files_updated,
        files_deleted = daily_activity_stats.files_deleted + EXCLUDED.files_deleted,
        files_shared = daily_activity_stats.files_shared + EXCLUDED.files_shared,
        files_accessed = daily_activity_stats.files_accessed + EXCLUDED.files_accessed,
        comments_created = daily_activity_stats.comments_created + EXCLUDED.comments_created,
        updated_at = now();
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to automatically update daily stats
-- Note: A trigger needs to be dropped and re-created if the function it calls is changed,
-- or if you're running the script repeatedly and the trigger already exists.
-- For a fresh run, you might uncomment this or wrap with IF NOT EXISTS if supported for triggers.
CREATE OR REPLACE TRIGGER update_daily_stats_trigger
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

-- Advanced Security Features for SECUREDOCS
-- Extends Laravel Jetstream with file-specific security controls
-- Run this in your Supabase SQL editor

-- Security policies table (extends Jetstream teams/user security)
CREATE TABLE IF NOT EXISTS security_policies (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Policy scope
    scope VARCHAR(50) NOT NULL DEFAULT 'global', -- 'global', 'team', 'user', 'folder', 'file'
    scope_id BIGINT, -- ID of the scoped entity (team_id, user_id, folder_id, file_id)
    
    -- Access controls
    ip_whitelist TEXT[], -- Array of allowed IP addresses/ranges
    ip_blacklist TEXT[], -- Array of blocked IP addresses/ranges
    allowed_countries TEXT[], -- Array of allowed country codes
    blocked_countries TEXT[], -- Array of blocked country codes
    
    -- Time-based access
    access_schedule JSON DEFAULT '{}', -- Weekly schedule with time ranges
    timezone VARCHAR(50) DEFAULT 'UTC',
    
    -- Device controls
    require_2fa BOOLEAN DEFAULT false,
    require_device_approval BOOLEAN DEFAULT false,
    max_concurrent_sessions INTEGER DEFAULT 5,
    session_timeout_minutes INTEGER DEFAULT 480, -- 8 hours default
    
    -- File controls
    allow_download BOOLEAN DEFAULT true,
    allow_copy BOOLEAN DEFAULT true,
    allow_print BOOLEAN DEFAULT true,
    allow_screenshot BOOLEAN DEFAULT true,
    watermark_enabled BOOLEAN DEFAULT false,
    watermark_text VARCHAR(255),
    
    -- DLP (Data Loss Prevention)
    dlp_enabled BOOLEAN DEFAULT false,
    dlp_patterns TEXT[], -- Regex patterns for sensitive content
    dlp_keywords TEXT[], -- Keywords to scan for
    dlp_action VARCHAR(20) DEFAULT 'warn', -- 'warn', 'block', 'quarantine'
    
    -- Encryption
    encryption_required BOOLEAN DEFAULT false,
    encryption_algorithm VARCHAR(50) DEFAULT 'AES-256',
    key_rotation_days INTEGER DEFAULT 90,
    
    -- Audit requirements
    audit_level VARCHAR(20) DEFAULT 'standard', -- 'none', 'basic', 'standard', 'detailed'
    retention_days INTEGER DEFAULT 365,
    
    -- Policy status
    is_active BOOLEAN DEFAULT true,
    enforced_at TIMESTAMP,
    created_by_user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT security_policies_scope_check CHECK (scope IN ('global', 'team', 'user', 'folder', 'file')),
    CONSTRAINT security_policies_dlp_action_check CHECK (dlp_action IN ('warn', 'block', 'quarantine')),
    CONSTRAINT security_policies_audit_level_check CHECK (audit_level IN ('none', 'basic', 'standard', 'detailed'))
);

-- Security violations table
CREATE TABLE IF NOT EXISTS security_violations (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    policy_id BIGINT REFERENCES security_policies(id) ON DELETE SET NULL,
    file_id BIGINT REFERENCES files(id) ON DELETE SET NULL,
    
    -- Violation details
    violation_type VARCHAR(50) NOT NULL, -- 'access_denied', 'dlp_trigger', 'suspicious_activity', etc.
    violation_category VARCHAR(50) NOT NULL DEFAULT 'policy', -- 'policy', 'dlp', 'access', 'device', 'time'
    severity VARCHAR(20) NOT NULL DEFAULT 'medium', -- 'low', 'medium', 'high', 'critical'
    
    description TEXT NOT NULL,
    details JSON DEFAULT '{}',
    
    -- Context
    ip_address INET,
    user_agent TEXT,
    location_country VARCHAR(2),
    location_city VARCHAR(100),
    device_fingerprint VARCHAR(255),
    
    -- Resolution
    status VARCHAR(20) DEFAULT 'open', -- 'open', 'investigating', 'resolved', 'false_positive'
    resolved_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    resolved_at TIMESTAMP,
    resolution_notes TEXT,
    
    -- Auto-response
    auto_action_taken VARCHAR(50), -- 'blocked', 'quarantined', 'notified', 'logged_only'
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT security_violations_violation_category_check CHECK (
        violation_category IN ('policy', 'dlp', 'access', 'device', 'time', 'encryption', 'session')
    ),
    CONSTRAINT security_violations_severity_check CHECK (severity IN ('low', 'medium', 'high', 'critical')),
    CONSTRAINT security_violations_status_check CHECK (
        status IN ('open', 'investigating', 'resolved', 'false_positive', 'acknowledged')
    )
);

-- Trusted devices table (extends Jetstream's device management)
CREATE TABLE IF NOT EXISTS trusted_devices (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Device identification
    device_name VARCHAR(255) NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    device_type VARCHAR(50), -- 'desktop', 'mobile', 'tablet'
    os_info VARCHAR(255),
    browser_info VARCHAR(255),
    
    -- Trust details
    trusted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trusted_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    trust_level VARCHAR(20) DEFAULT 'standard', -- 'limited', 'standard', 'high'
    
    -- Access restrictions for this device
    access_restrictions JSON DEFAULT '{}',
    
    -- Usage tracking
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_ip_address INET,
    last_location_country VARCHAR(2),
    last_location_city VARCHAR(100),
    
    -- Status
    is_active BOOLEAN DEFAULT true,
    revoked_at TIMESTAMP,
    revoked_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    revocation_reason VARCHAR(255),
    
    -- Auto-trust settings
    auto_approved BOOLEAN DEFAULT false,
    expires_at TIMESTAMP,
    
    UNIQUE(user_id, device_fingerprint),
    CONSTRAINT trusted_devices_trust_level_check CHECK (trust_level IN ('limited', 'standard', 'high'))
);

-- File encryption metadata
CREATE TABLE IF NOT EXISTS file_encryption (
    id BIGSERIAL PRIMARY KEY,
    file_id BIGINT NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    
    -- Encryption details
    encryption_algorithm VARCHAR(50) NOT NULL DEFAULT 'AES-256',
    key_id VARCHAR(255) NOT NULL, -- Reference to key management system
    iv_base64 TEXT, -- Initialization vector for symmetric encryption
    
    -- Access control
    encrypted_by_user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    access_level VARCHAR(20) DEFAULT 'restricted', -- 'public', 'internal', 'confidential', 'restricted'
    
    -- Key management
    key_rotation_count INTEGER DEFAULT 0,
    last_key_rotation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    next_key_rotation TIMESTAMP,
    
    -- Decryption tracking
    decryption_count INTEGER DEFAULT 0,
    last_decrypted_at TIMESTAMP,
    last_decrypted_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    
    -- Audit
    audit_trail JSON DEFAULT '[]', -- Array of access events
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(file_id),
    CONSTRAINT file_encryption_access_level_check CHECK (
        access_level IN ('public', 'internal', 'confidential', 'restricted', 'top_secret')
    )
);

-- DLP scan results
CREATE TABLE IF NOT EXISTS dlp_scan_results (
    id BIGSERIAL PRIMARY KEY,
    file_id BIGINT NOT NULL REFERENCES files(id) ON DELETE CASCADE,
    policy_id BIGINT REFERENCES security_policies(id) ON DELETE SET NULL,
    
    -- Scan details
    scan_type VARCHAR(50) NOT NULL DEFAULT 'upload', -- 'upload', 'scheduled', 'manual', 'real_time'
    scan_status VARCHAR(20) NOT NULL DEFAULT 'pending', -- 'pending', 'scanning', 'completed', 'failed'
    
    -- Results
    risk_score INTEGER DEFAULT 0, -- 0-100
    risk_level VARCHAR(20) DEFAULT 'low', -- 'low', 'medium', 'high', 'critical'
    
    -- Detected patterns
    detected_patterns JSON DEFAULT '[]', -- Array of detected sensitive patterns
    detected_keywords JSON DEFAULT '[]', -- Array of detected keywords
    confidence_score DECIMAL(5,2) DEFAULT 0.00, -- 0.00-100.00
    
    -- AI/ML analysis (if available)
    ai_classification VARCHAR(100),
    ai_confidence DECIMAL(5,2),
    ai_suggestions TEXT[],
    
    -- Actions taken
    action_taken VARCHAR(50), -- 'none', 'flagged', 'quarantined', 'blocked', 'encrypted'
    quarantine_reason TEXT,
    
    -- Review
    reviewed_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    reviewed_at TIMESTAMP,
    review_status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'approved', 'rejected', 'requires_action'
    review_notes TEXT,
    
    -- Performance metrics
    scan_duration_ms INTEGER,
    file_size_bytes BIGINT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT dlp_scan_results_scan_status_check CHECK (
        scan_status IN ('pending', 'scanning', 'completed', 'failed', 'cancelled')
    ),
    CONSTRAINT dlp_scan_results_risk_level_check CHECK (risk_level IN ('low', 'medium', 'high', 'critical')),
    CONSTRAINT dlp_scan_results_review_status_check CHECK (
        review_status IN ('pending', 'approved', 'rejected', 'requires_action')
    )
);

-- Security dashboard metrics (for real-time monitoring)
CREATE TABLE IF NOT EXISTS security_metrics (
    id BIGSERIAL PRIMARY KEY,
    metric_date DATE NOT NULL DEFAULT CURRENT_DATE,
    metric_hour INTEGER NOT NULL DEFAULT EXTRACT(HOUR FROM CURRENT_TIME), -- 0-23
    
    -- User activity metrics
    active_users INTEGER DEFAULT 0,
    new_logins INTEGER DEFAULT 0,
    failed_logins INTEGER DEFAULT 0,
    suspicious_logins INTEGER DEFAULT 0,
    
    -- File activity metrics
    files_uploaded INTEGER DEFAULT 0,
    files_downloaded INTEGER DEFAULT 0,
    files_shared INTEGER DEFAULT 0,
    files_encrypted INTEGER DEFAULT 0,
    
    -- Security events
    security_violations INTEGER DEFAULT 0,
    dlp_triggers INTEGER DEFAULT 0,
    access_denied INTEGER DEFAULT 0,
    device_approvals INTEGER DEFAULT 0,
    
    -- Performance metrics
    avg_login_time_ms INTEGER DEFAULT 0,
    avg_file_scan_time_ms INTEGER DEFAULT 0,
    system_load_avg DECIMAL(5,2) DEFAULT 0.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(metric_date, metric_hour),
    CONSTRAINT security_metrics_metric_hour_check CHECK (metric_hour >= 0 AND metric_hour <= 23)
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_security_policies_scope ON security_policies(scope, scope_id) WHERE is_active = true;
CREATE INDEX IF NOT EXISTS idx_security_violations_user ON security_violations(user_id);
CREATE INDEX IF NOT EXISTS idx_security_violations_severity ON security_violations(severity) WHERE status = 'open';
CREATE INDEX IF NOT EXISTS idx_security_violations_created ON security_violations(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_trusted_devices_user_active ON trusted_devices(user_id, is_active) WHERE is_active = true;
CREATE INDEX IF NOT EXISTS idx_trusted_devices_fingerprint ON trusted_devices(device_fingerprint);
CREATE INDEX IF NOT EXISTS idx_file_encryption_file ON file_encryption(file_id);
CREATE INDEX IF NOT EXISTS idx_dlp_scan_results_file ON dlp_scan_results(file_id);
CREATE INDEX IF NOT EXISTS idx_dlp_scan_results_risk ON dlp_scan_results(risk_level) WHERE scan_status = 'completed';
CREATE INDEX IF NOT EXISTS idx_security_metrics_date_hour ON security_metrics(metric_date DESC, metric_hour DESC);

-- Create triggers for updated_at timestamps
CREATE OR REPLACE TRIGGER update_security_policies_updated_at
    BEFORE UPDATE ON security_policies
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE OR REPLACE TRIGGER update_file_encryption_updated_at
    BEFORE UPDATE ON file_encryption
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE OR REPLACE TRIGGER update_dlp_scan_results_updated_at
    BEFORE UPDATE ON dlp_scan_results
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create views for common security queries
CREATE OR REPLACE VIEW security_violations_summary AS
SELECT 
    DATE(created_at) as violation_date,
    violation_category,
    severity,
    COUNT(*) as violation_count,
    COUNT(DISTINCT user_id) as affected_users,
    COUNT(DISTINCT file_id) as affected_files
FROM security_violations 
WHERE created_at >= CURRENT_DATE - INTERVAL '30 days'
GROUP BY DATE(created_at), violation_category, severity
ORDER BY violation_date DESC, violation_count DESC;

CREATE OR REPLACE VIEW user_security_risk AS
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    COUNT(sv.id) as total_violations,
    COUNT(CASE WHEN sv.severity IN ('high', 'critical') THEN 1 END) as high_risk_violations,
    COUNT(CASE WHEN sv.created_at >= CURRENT_DATE - INTERVAL '7 days' THEN 1 END) as recent_violations,
    MAX(sv.created_at) as last_violation,
    CASE 
        WHEN COUNT(CASE WHEN sv.severity = 'critical' THEN 1 END) > 0 THEN 'critical'
        WHEN COUNT(CASE WHEN sv.severity = 'high' THEN 1 END) > 2 THEN 'high'
        WHEN COUNT(sv.id) > 5 THEN 'medium'
        ELSE 'low'
    END as risk_level
FROM users u
LEFT JOIN security_violations sv ON u.id = sv.user_id AND sv.created_at >= CURRENT_DATE - INTERVAL '30 days'
GROUP BY u.id, u.name, u.email
ORDER BY total_violations DESC;

CREATE OR REPLACE VIEW file_security_status AS
SELECT 
    f.id as file_id,
    f.file_name,
    f.user_id as owner_id,
    CASE WHEN fe.id IS NOT NULL THEN true ELSE false END as is_encrypted,
    fe.access_level,
    dlp.risk_level as dlp_risk_level,
    COUNT(sv.id) as security_violations,
    MAX(sv.created_at) as last_violation
FROM files f
LEFT JOIN file_encryption fe ON f.id = fe.file_id
LEFT JOIN dlp_scan_results dlp ON f.id = dlp.file_id AND dlp.scan_status = 'completed'
LEFT JOIN security_violations sv ON f.id = sv.file_id AND sv.created_at >= CURRENT_DATE - INTERVAL '30 days'
WHERE f.deleted_at IS NULL
GROUP BY f.id, f.file_name, f.user_id, fe.id, fe.access_level, dlp.risk_level
ORDER BY security_violations DESC;

-- Insert default security policies
INSERT INTO security_policies (
    name, description, scope, created_by_user_id, 
    require_2fa, audit_level, retention_days
) VALUES 
(
    'Default Global Security Policy', 
    'Basic security requirements for all users and files',
    'global', 
    1,  -- Assuming admin user ID is 1
    false,
    'standard',
    365
),
(
    'High Security Policy',
    'Enhanced security for confidential files and sensitive operations',
    'global',
    1,
    true,
    'detailed',
    2555  -- 7 years
)
ON CONFLICT (name, scope) DO NOTHING; -- Added to prevent re-insertion on repeated runs

-- Create function to log security events
CREATE OR REPLACE FUNCTION log_security_violation(
    p_user_id BIGINT,
    p_violation_type VARCHAR(50),
    p_violation_category VARCHAR(50),
    p_severity VARCHAR(20),
    p_description TEXT,
    p_policy_id BIGINT DEFAULT NULL,
    p_file_id BIGINT DEFAULT NULL,
    p_details JSON DEFAULT '{}',
    p_auto_action VARCHAR(50) DEFAULT NULL
)
RETURNS BIGINT AS $$
DECLARE
    violation_id BIGINT;
BEGIN
    INSERT INTO security_violations (
        user_id, policy_id, file_id, violation_type, violation_category,
        severity, description, details, ip_address, user_agent,
        location_country, auto_action_taken
    ) VALUES (
        p_user_id, p_policy_id, p_file_id, p_violation_type, p_violation_category,
        p_severity, p_description, p_details, INET_CLIENT_ADDR(), 
        CURRENT_SETTING('application_name', true),
        'US', -- TODO: Get actual country from IP
        p_auto_action
    ) RETURNING id INTO violation_id;
    
    -- Update security metrics
    INSERT INTO security_metrics (metric_date, metric_hour, security_violations)
    VALUES (CURRENT_DATE, EXTRACT(HOUR FROM CURRENT_TIME)::INTEGER, 1)
    ON CONFLICT (metric_date, metric_hour) DO UPDATE SET
        security_violations = security_metrics.security_violations + 1;

    RETURN violation_id;
END;
$$ LANGUAGE plpgsql;
