-- Fix user_sessions table data types for boolean compatibility
-- Convert integer boolean fields to actual boolean type

-- Add missing columns if they don't exist and standardize data types
DO $$ 
BEGIN
    -- Add device_type if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'device_type') THEN
        ALTER TABLE user_sessions ADD COLUMN device_type VARCHAR(50);
    END IF;
    
    -- Add browser if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'browser') THEN
        ALTER TABLE user_sessions ADD COLUMN browser VARCHAR(100);
    END IF;
    
    -- Add platform if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'platform') THEN
        ALTER TABLE user_sessions ADD COLUMN platform VARCHAR(100);
    END IF;
    
    -- Add updated_at if not exists
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'updated_at') THEN
        ALTER TABLE user_sessions ADD COLUMN updated_at TIMESTAMPTZ DEFAULT now();
    END IF;
END $$;

-- Fix integer boolean columns to proper boolean type
-- Convert is_suspicious from integer to boolean if needed
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'is_suspicious' AND data_type = 'integer') THEN
        -- Convert integer values to boolean using explicit casting
        ALTER TABLE user_sessions 
        ALTER COLUMN is_suspicious TYPE BOOLEAN 
        USING (is_suspicious::integer = 1);
        
        -- Set default
        ALTER TABLE user_sessions ALTER COLUMN is_suspicious SET DEFAULT FALSE;
    END IF;
END $$;

-- Fix trusted_device from integer to boolean if needed
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'trusted_device' AND data_type = 'integer') THEN
        -- Convert integer values to boolean using explicit casting
        ALTER TABLE user_sessions 
        ALTER COLUMN trusted_device TYPE BOOLEAN 
        USING (trusted_device::integer = 1);
        
        -- Set default
        ALTER TABLE user_sessions ALTER COLUMN trusted_device SET DEFAULT FALSE;
    END IF;
END $$;

-- Add is_mobile, is_tablet, is_desktop as proper boolean columns if they exist as integers
DO $$ 
BEGIN
    -- Add is_mobile if not exists, or convert from integer
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'is_mobile') THEN
        ALTER TABLE user_sessions ADD COLUMN is_mobile BOOLEAN DEFAULT FALSE;
    ELSIF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'is_mobile' AND data_type = 'integer') THEN
        ALTER TABLE user_sessions 
        ALTER COLUMN is_mobile TYPE BOOLEAN 
        USING (is_mobile::integer = 1);
        
        ALTER TABLE user_sessions ALTER COLUMN is_mobile SET DEFAULT FALSE;
    END IF;
    
    -- Add is_tablet if not exists, or convert from integer
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'is_tablet') THEN
        ALTER TABLE user_sessions ADD COLUMN is_tablet BOOLEAN DEFAULT FALSE;
    ELSIF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'is_tablet' AND data_type = 'integer') THEN
        ALTER TABLE user_sessions 
        ALTER COLUMN is_tablet TYPE BOOLEAN 
        USING (is_tablet::integer = 1);
        
        ALTER TABLE user_sessions ALTER COLUMN is_tablet SET DEFAULT FALSE;
    END IF;
    
    -- Add is_desktop if not exists, or convert from integer
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'is_desktop') THEN
        ALTER TABLE user_sessions ADD COLUMN is_desktop BOOLEAN DEFAULT FALSE;
    ELSIF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_sessions' AND column_name = 'is_desktop' AND data_type = 'integer') THEN
        ALTER TABLE user_sessions 
        ALTER COLUMN is_desktop TYPE BOOLEAN 
        USING (is_desktop::integer = 1);
        
        ALTER TABLE user_sessions ALTER COLUMN is_desktop SET DEFAULT FALSE;
    END IF;
END $$;

-- Create updated_at trigger if it doesn't exist
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ language 'plpgsql';

DROP TRIGGER IF EXISTS update_user_sessions_updated_at ON user_sessions;
CREATE TRIGGER update_user_sessions_updated_at
    BEFORE UPDATE ON user_sessions
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_user_sessions_device_type ON user_sessions(device_type);
CREATE INDEX IF NOT EXISTS idx_user_sessions_trusted ON user_sessions(trusted_device);
CREATE INDEX IF NOT EXISTS idx_user_sessions_updated_at ON user_sessions(updated_at);
