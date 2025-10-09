"""
Configuration management for test automation
"""

import os
from configparser import ConfigParser


class Config:
    """Configuration class for test settings"""
    
    def __init__(self, config_file='config/test.ini'):
        self.config = ConfigParser()
        self.config_file = config_file
        self._load_config()
        
    def _load_config(self):
        """Load configuration from file and environment variables"""
        # Load from INI file if exists
        if os.path.exists(self.config_file):
            self.config.read(self.config_file)
        else:
            # Create default config
            self._create_default_config()
            
    def _create_default_config(self):
        """Create default configuration"""
        self.config['DEFAULT'] = {
            'base_url': 'http://localhost:8000',
            'browser': 'chrome',
            'headless': 'false',
            'implicit_wait': '10',
            'explicit_wait': '30',
            'screenshot_dir': 'screenshots',
            'report_dir': 'reports',
            'test_data_dir': 'test-data',
            'log_level': 'INFO'
        }
        
        self.config['DATABASE'] = {
            'host': 'localhost',
            'port': '5432',
            'database': 'securedocs_test',
            'username': 'test_user',
            'password': 'test_pass'
        }
        
        self.config['EMAIL'] = {
            'smtp_server': 'smtp.gmail.com',
            'smtp_port': '587',
            'email': 'test@example.com',
            'password': 'test_password'
        }
        
        # Save default config
        os.makedirs(os.path.dirname(self.config_file), exist_ok=True)
        with open(self.config_file, 'w') as f:
            self.config.write(f)
            
    def get_base_url(self):
        """Get base URL for testing"""
        return os.getenv('BASE_URL', self.config.get('DEFAULT', 'base_url'))
        
    def get_browser(self):
        """Get browser name"""
        return os.getenv('BROWSER', self.config.get('DEFAULT', 'browser'))
        
    def is_headless(self):
        """Check if headless mode is enabled"""
        headless = os.getenv('HEADLESS', self.config.get('DEFAULT', 'headless'))
        return headless.lower() in ['true', '1', 'yes']
        
    def get_implicit_wait(self):
        """Get implicit wait timeout"""
        wait = os.getenv('IMPLICIT_WAIT', self.config.get('DEFAULT', 'implicit_wait'))
        return int(wait)
        
    def get_explicit_wait(self):
        """Get explicit wait timeout"""
        wait = os.getenv('EXPLICIT_WAIT', self.config.get('DEFAULT', 'explicit_wait'))
        return int(wait)
        
    def get_screenshot_dir(self):
        """Get screenshot directory"""
        return os.getenv('SCREENSHOT_DIR', self.config.get('DEFAULT', 'screenshot_dir'))
        
    def get_report_dir(self):
        """Get report directory"""
        return os.getenv('REPORT_DIR', self.config.get('DEFAULT', 'report_dir'))
        
    def get_test_data_dir(self):
        """Get test data directory"""
        return os.getenv('TEST_DATA_DIR', self.config.get('DEFAULT', 'test_data_dir'))
        
    def get_log_level(self):
        """Get logging level"""
        return os.getenv('LOG_LEVEL', self.config.get('DEFAULT', 'log_level'))
        
    # Database configuration
    def get_db_host(self):
        """Get database host"""
        return os.getenv('DB_HOST', self.config.get('DATABASE', 'host'))
        
    def get_db_port(self):
        """Get database port"""
        return int(os.getenv('DB_PORT', self.config.get('DATABASE', 'port')))
        
    def get_db_name(self):
        """Get database name"""
        return os.getenv('DB_NAME', self.config.get('DATABASE', 'database'))
        
    def get_db_username(self):
        """Get database username"""
        return os.getenv('DB_USERNAME', self.config.get('DATABASE', 'username'))
        
    def get_db_password(self):
        """Get database password"""
        return os.getenv('DB_PASSWORD', self.config.get('DATABASE', 'password'))
        
    # Email configuration
    def get_smtp_server(self):
        """Get SMTP server"""
        return os.getenv('SMTP_SERVER', self.config.get('EMAIL', 'smtp_server'))
        
    def get_smtp_port(self):
        """Get SMTP port"""
        return int(os.getenv('SMTP_PORT', self.config.get('EMAIL', 'smtp_port')))
        
    def get_test_email(self):
        """Get test email"""
        return os.getenv('TEST_EMAIL', self.config.get('EMAIL', 'email'))
        
    def get_test_email_password(self):
        """Get test email password"""
        return os.getenv('TEST_EMAIL_PASSWORD', self.config.get('EMAIL', 'password'))
        
    # Test environment settings
    def get_environment(self):
        """Get test environment"""
        return os.getenv('TEST_ENVIRONMENT', 'local')
        
    def is_parallel_execution(self):
        """Check if parallel execution is enabled"""
        parallel = os.getenv('PARALLEL_EXECUTION', 'false')
        return parallel.lower() in ['true', '1', 'yes']
        
    def get_thread_count(self):
        """Get thread count for parallel execution"""
        return int(os.getenv('THREAD_COUNT', '1'))
        
    def get_retry_count(self):
        """Get retry count for failed tests"""
        return int(os.getenv('RETRY_COUNT', '0'))
        
    # Test data configuration
    def get_valid_user_email(self):
        """Get valid test user email"""
        return os.getenv('VALID_USER_EMAIL', 'testuser@example.com')
        
    def get_valid_user_password(self):
        """Get valid test user password"""
        return os.getenv('VALID_USER_PASSWORD', 'SecurePass123!')
        
    def get_admin_email(self):
        """Get admin user email"""
        return os.getenv('ADMIN_EMAIL', 'admin@example.com')
        
    def get_admin_password(self):
        """Get admin user password"""
        return os.getenv('ADMIN_PASSWORD', 'AdminPass123!')
        
    def get_premium_user_email(self):
        """Get premium user email"""
        return os.getenv('PREMIUM_USER_EMAIL', 'premium@example.com')
        
    def get_premium_user_password(self):
        """Get premium user password"""
        return os.getenv('PREMIUM_USER_PASSWORD', 'PremiumPass123!')
        
    # File upload settings
    def get_max_file_size_mb(self):
        """Get maximum file size for upload in MB"""
        return int(os.getenv('MAX_FILE_SIZE_MB', '10'))
        
    def get_allowed_file_types(self):
        """Get allowed file types for upload"""
        types = os.getenv('ALLOWED_FILE_TYPES', 'pdf,jpg,png,txt,doc,docx')
        return [t.strip() for t in types.split(',')]
        
    # Performance settings
    def get_page_load_timeout(self):
        """Get page load timeout"""
        return int(os.getenv('PAGE_LOAD_TIMEOUT', '30'))
        
    def get_api_timeout(self):
        """Get API timeout"""
        return int(os.getenv('API_TIMEOUT', '30'))
        
    # Security settings
    def get_otp_wait_time(self):
        """Get OTP wait time in seconds"""
        return int(os.getenv('OTP_WAIT_TIME', '60'))
        
    def get_session_timeout(self):
        """Get session timeout in minutes"""
        return int(os.getenv('SESSION_TIMEOUT', '30'))
        
    # Reporting settings
    def is_allure_enabled(self):
        """Check if Allure reporting is enabled"""
        allure = os.getenv('ALLURE_ENABLED', 'true')
        return allure.lower() in ['true', '1', 'yes']
        
    def is_html_report_enabled(self):
        """Check if HTML report is enabled"""
        html = os.getenv('HTML_REPORT_ENABLED', 'true')
        return html.lower() in ['true', '1', 'yes']
        
    def is_video_recording_enabled(self):
        """Check if video recording is enabled"""
        video = os.getenv('VIDEO_RECORDING', 'false')
        return video.lower() in ['true', '1', 'yes']
        
    # Debug settings
    def is_debug_mode(self):
        """Check if debug mode is enabled"""
        debug = os.getenv('DEBUG_MODE', 'false')
        return debug.lower() in ['true', '1', 'yes']
        
    def is_save_page_source(self):
        """Check if page source should be saved on failure"""
        save = os.getenv('SAVE_PAGE_SOURCE', 'false')
        return save.lower() in ['true', '1', 'yes']
        
    def get_config_value(self, section, key, default=None):
        """Get custom configuration value"""
        env_key = f"{section.upper()}_{key.upper()}"
        return os.getenv(env_key, self.config.get(section, key, fallback=default))
        
    def update_config(self, section, key, value):
        """Update configuration value"""
        if section not in self.config:
            self.config.add_section(section)
        self.config.set(section, key, str(value))
        
    def save_config(self):
        """Save current configuration to file"""
        with open(self.config_file, 'w') as f:
            self.config.write(f)
