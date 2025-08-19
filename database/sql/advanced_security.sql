-- Advanced Security Features for SECUREDOCS
-- Extends Laravel Jetstream with file-specific security controls
-- Run this in your Supabase SQL editor

-- Security policies table (extends Jetstream teams/user security)
CREATE TABLE security_policies (
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
CREATE TABLE security_violations (
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
CREATE TABLE trusted_devices (
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
CREATE TABLE file_encryption (
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
CREATE TABLE dlp_scan_results (
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
CREATE TABLE security_metrics (
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
CREATE INDEX idx_security_policies_scope ON security_policies(scope, scope_id) WHERE is_active = true;
CREATE INDEX idx_security_violations_user ON security_violations(user_id);
CREATE INDEX idx_security_violations_severity ON security_violations(severity) WHERE status = 'open';
CREATE INDEX idx_security_violations_created ON security_violations(created_at DESC);
CREATE INDEX idx_trusted_devices_user_active ON trusted_devices(user_id, is_active) WHERE is_active = true;
CREATE INDEX idx_trusted_devices_fingerprint ON trusted_devices(device_fingerprint);
CREATE INDEX idx_file_encryption_file ON file_encryption(file_id);
CREATE INDEX idx_dlp_scan_results_file ON dlp_scan_results(file_id);
CREATE INDEX idx_dlp_scan_results_risk ON dlp_scan_results(risk_level) WHERE scan_status = 'completed';
CREATE INDEX idx_security_metrics_date_hour ON security_metrics(metric_date DESC, metric_hour DESC);

-- Create triggers for updated_at timestamps
CREATE TRIGGER update_security_policies_updated_at
    BEFORE UPDATE ON security_policies
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_file_encryption_updated_at
    BEFORE UPDATE ON file_encryption
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_dlp_scan_results_updated_at
    BEFORE UPDATE ON dlp_scan_results
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create views for common security queries
CREATE VIEW security_violations_summary AS
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

CREATE VIEW user_security_risk AS
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

CREATE VIEW file_security_status AS
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
);

-- Create function to log security events
CREATE OR REPLACE FUNCTION log_security_violation(
    p_user_id BIGINT,
    p_policy_id BIGINT DEFAULT NULL,
    p_file_id BIGINT DEFAULT NULL,
    p_violation_type VARCHAR(50),
    p_violation_category VARCHAR(50),
    p_severity VARCHAR(20),
    p_description TEXT,
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
    VALUES (CURRENT_DATE, EXTRACT(HOUR FROM CURRENT_TIME), 1)
    ON CONFLICT (metric_date, metric_hour) 
    DO UPDATE SET security_violations = security_metrics.security_violations + 1;
    
    RETURN violation_id;
END;
$$ LANGUAGE plpgsql;

-- Create function to update security metrics
CREATE OR REPLACE FUNCTION update_security_metrics(
    p_metric_type VARCHAR(50),
    p_increment INTEGER DEFAULT 1
)
RETURNS VOID AS $$
BEGIN
    INSERT INTO security_metrics (
        metric_date, metric_hour,
        active_users, new_logins, failed_logins, suspicious_logins,
        files_uploaded, files_downloaded, files_shared, files_encrypted,
        security_violations, dlp_triggers, access_denied, device_approvals
    ) VALUES (
        CURRENT_DATE, EXTRACT(HOUR FROM CURRENT_TIME),
        CASE WHEN p_metric_type = 'active_users' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'new_logins' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'failed_logins' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'suspicious_logins' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'files_uploaded' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'files_downloaded' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'files_shared' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'files_encrypted' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'security_violations' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'dlp_triggers' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'access_denied' THEN p_increment ELSE 0 END,
        CASE WHEN p_metric_type = 'device_approvals' THEN p_increment ELSE 0 END
    )
    ON CONFLICT (metric_date, metric_hour) 
    DO UPDATE SET
        active_users = CASE WHEN p_metric_type = 'active_users' THEN security_metrics.active_users + p_increment ELSE security_metrics.active_users END,
        new_logins = CASE WHEN p_metric_type = 'new_logins' THEN security_metrics.new_logins + p_increment ELSE security_metrics.new_logins END,
        failed_logins = CASE WHEN p_metric_type = 'failed_logins' THEN security_metrics.failed_logins + p_increment ELSE security_metrics.failed_logins END,
        suspicious_logins = CASE WHEN p_metric_type = 'suspicious_logins' THEN security_metrics.suspicious_logins + p_increment ELSE security_metrics.suspicious_logins END,
        files_uploaded = CASE WHEN p_metric_type = 'files_uploaded' THEN security_metrics.files_uploaded + p_increment ELSE security_metrics.files_uploaded END,
        files_downloaded = CASE WHEN p_metric_type = 'files_downloaded' THEN security_metrics.files_downloaded + p_increment ELSE security_metrics.files_downloaded END,
        files_shared = CASE WHEN p_metric_type = 'files_shared' THEN security_metrics.files_shared + p_increment ELSE security_metrics.files_shared END,
        files_encrypted = CASE WHEN p_metric_type = 'files_encrypted' THEN security_metrics.files_encrypted + p_increment ELSE security_metrics.files_encrypted END,
        security_violations = CASE WHEN p_metric_type = 'security_violations' THEN security_metrics.security_violations + p_increment ELSE security_metrics.security_violations END,
        dlp_triggers = CASE WHEN p_metric_type = 'dlp_triggers' THEN security_metrics.dlp_triggers + p_increment ELSE security_metrics.dlp_triggers END,
        access_denied = CASE WHEN p_metric_type = 'access_denied' THEN security_metrics.access_denied + p_increment ELSE security_metrics.access_denied END,
        device_approvals = CASE WHEN p_metric_type = 'device_approvals' THEN security_metrics.device_approvals + p_increment ELSE security_metrics.device_approvals END;
END;
$$ LANGUAGE plpgsql;
