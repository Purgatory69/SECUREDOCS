"""
Logging utility for test automation
"""

import os
import logging
import logging.config
from datetime import datetime
from utils.config import Config


class Logger:
    """Logger utility class"""
    
    _loggers = {}
    _config = Config()
    
    @classmethod
    def get_logger(cls, name='selenium_tests'):
        """Get logger instance"""
        if name not in cls._loggers:
            cls._loggers[name] = cls._create_logger(name)
        return cls._loggers[name]
    
    @classmethod
    def _create_logger(cls, name):
        """Create and configure logger"""
        logger = logging.getLogger(name)
        
        # Avoid duplicate handlers
        if logger.handlers:
            return logger
            
        logger.setLevel(cls._get_log_level())
        
        # Create logs directory
        log_dir = 'logs'
        if not os.path.exists(log_dir):
            os.makedirs(log_dir)
            
        # Create formatters
        detailed_formatter = logging.Formatter(
            fmt='%(asctime)s | %(levelname)-8s | %(name)s | %(funcName)s:%(lineno)d | %(message)s',
            datefmt='%Y-%m-%d %H:%M:%S'
        )
        
        simple_formatter = logging.Formatter(
            fmt='%(asctime)s | %(levelname)-8s | %(message)s',
            datefmt='%H:%M:%S'
        )
        
        # File handler for detailed logging
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        file_handler = logging.FileHandler(
            filename=os.path.join(log_dir, f'test_execution_{timestamp}.log'),
            mode='a',
            encoding='utf-8'
        )
        file_handler.setLevel(logging.DEBUG)
        file_handler.setFormatter(detailed_formatter)
        
        # Console handler for immediate feedback
        console_handler = logging.StreamHandler()
        console_handler.setLevel(logging.INFO)
        console_handler.setFormatter(simple_formatter)
        
        # Add handlers to logger
        logger.addHandler(file_handler)
        logger.addHandler(console_handler)
        
        return logger
    
    @classmethod
    def _get_log_level(cls):
        """Get logging level from config"""
        level_str = cls._config.get_log_level().upper()
        return getattr(logging, level_str, logging.INFO)
    
    @classmethod
    def create_test_logger(cls, test_name):
        """Create logger for specific test"""
        return cls.get_logger(f'test_{test_name}')
    
    @classmethod
    def log_test_start(cls, test_name, test_description=''):
        """Log test start"""
        logger = cls.get_logger()
        logger.info(f"{'='*80}")
        logger.info(f"STARTING TEST: {test_name}")
        if test_description:
            logger.info(f"DESCRIPTION: {test_description}")
        logger.info(f"TIMESTAMP: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        logger.info(f"{'='*80}")
    
    @classmethod
    def log_test_end(cls, test_name, result='UNKNOWN', duration=None):
        """Log test end"""
        logger = cls.get_logger()
        logger.info(f"{'='*80}")
        logger.info(f"FINISHED TEST: {test_name}")
        logger.info(f"RESULT: {result}")
        if duration:
            logger.info(f"DURATION: {duration:.2f} seconds")
        logger.info(f"TIMESTAMP: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        logger.info(f"{'='*80}")
    
    @classmethod
    def log_test_step(cls, step_description):
        """Log test step"""
        logger = cls.get_logger()
        logger.info(f"STEP: {step_description}")
    
    @classmethod
    def log_assertion(cls, assertion_description, result=True):
        """Log assertion result"""
        logger = cls.get_logger()
        status = "PASS" if result else "FAIL"
        logger.info(f"ASSERTION [{status}]: {assertion_description}")
    
    @classmethod
    def log_error(cls, error_message, exception=None):
        """Log error with optional exception details"""
        logger = cls.get_logger()
        logger.error(f"ERROR: {error_message}")
        if exception:
            logger.error(f"EXCEPTION: {str(exception)}")
            logger.exception("Exception details:")
    
    @classmethod
    def log_warning(cls, warning_message):
        """Log warning message"""
        logger = cls.get_logger()
        logger.warning(f"WARNING: {warning_message}")
    
    @classmethod
    def log_debug(cls, debug_message):
        """Log debug message"""
        logger = cls.get_logger()
        logger.debug(f"DEBUG: {debug_message}")
    
    @classmethod
    def log_api_request(cls, method, url, headers=None, data=None):
        """Log API request details"""
        logger = cls.get_logger()
        logger.info(f"API REQUEST: {method} {url}")
        if headers:
            logger.debug(f"HEADERS: {headers}")
        if data:
            logger.debug(f"DATA: {data}")
    
    @classmethod
    def log_api_response(cls, status_code, response_data=None, duration=None):
        """Log API response details"""
        logger = cls.get_logger()
        logger.info(f"API RESPONSE: Status {status_code}")
        if duration:
            logger.info(f"RESPONSE TIME: {duration:.3f}s")
        if response_data:
            logger.debug(f"RESPONSE DATA: {response_data}")
    
    @classmethod
    def log_browser_action(cls, action, element=None, value=None):
        """Log browser action"""
        logger = cls.get_logger()
        message = f"BROWSER ACTION: {action}"
        if element:
            message += f" on element: {element}"
        if value:
            message += f" with value: {value}"
        logger.info(message)
    
    @classmethod
    def log_page_load(cls, page_name, url, load_time=None):
        """Log page load"""
        logger = cls.get_logger()
        message = f"PAGE LOAD: {page_name} ({url})"
        if load_time:
            message += f" in {load_time:.2f}s"
        logger.info(message)
    
    @classmethod
    def log_file_operation(cls, operation, file_path, result=True):
        """Log file operation"""
        logger = cls.get_logger()
        status = "SUCCESS" if result else "FAILED"
        logger.info(f"FILE {operation.upper()} [{status}]: {file_path}")
    
    @classmethod
    def log_database_operation(cls, operation, table=None, query=None, result=True):
        """Log database operation"""
        logger = cls.get_logger()
        status = "SUCCESS" if result else "FAILED"
        message = f"DATABASE {operation.upper()} [{status}]"
        if table:
            message += f" on table: {table}"
        if query:
            logger.debug(f"QUERY: {query}")
        logger.info(message)
    
    @classmethod
    def log_performance_metric(cls, metric_name, value, unit='ms', threshold=None):
        """Log performance metric"""
        logger = cls.get_logger()
        message = f"PERFORMANCE: {metric_name} = {value}{unit}"
        if threshold:
            status = "PASS" if value <= threshold else "FAIL"
            message += f" (threshold: {threshold}{unit}) [{status}]"
        logger.info(message)
    
    @classmethod
    def log_security_event(cls, event_type, details):
        """Log security-related events"""
        logger = cls.get_logger()
        logger.warning(f"SECURITY EVENT: {event_type} - {details}")
    
    @classmethod
    def log_configuration(cls, config_dict):
        """Log configuration details"""
        logger = cls.get_logger()
        logger.info("CONFIGURATION:")
        for key, value in config_dict.items():
            # Mask sensitive values
            if any(sensitive in key.lower() for sensitive in ['password', 'key', 'secret', 'token']):
                value = '*' * len(str(value))
            logger.info(f"  {key}: {value}")
    
    @classmethod
    def log_test_data(cls, data_description, data):
        """Log test data"""
        logger = cls.get_logger()
        logger.debug(f"TEST DATA - {data_description}: {data}")
    
    @classmethod
    def log_screenshot(cls, screenshot_path, context=''):
        """Log screenshot capture"""
        logger = cls.get_logger()
        message = f"SCREENSHOT CAPTURED: {screenshot_path}"
        if context:
            message += f" ({context})"
        logger.info(message)
    
    @classmethod
    def create_test_report_log(cls, test_results):
        """Create test execution summary log"""
        logger = cls.get_logger()
        
        total_tests = len(test_results)
        passed = sum(1 for result in test_results if result.get('status') == 'PASS')
        failed = sum(1 for result in test_results if result.get('status') == 'FAIL')
        skipped = sum(1 for result in test_results if result.get('status') == 'SKIP')
        
        logger.info("="*100)
        logger.info("TEST EXECUTION SUMMARY")
        logger.info("="*100)
        logger.info(f"Total Tests: {total_tests}")
        logger.info(f"Passed: {passed}")
        logger.info(f"Failed: {failed}")
        logger.info(f"Skipped: {skipped}")
        logger.info(f"Pass Rate: {(passed/total_tests)*100:.1f}%")
        logger.info("="*100)
        
        if failed > 0:
            logger.info("FAILED TESTS:")
            for result in test_results:
                if result.get('status') == 'FAIL':
                    logger.info(f"  - {result.get('name', 'Unknown')}: {result.get('reason', 'No reason provided')}")
    
    @classmethod
    def setup_logging_for_parallel_execution(cls):
        """Setup logging for parallel test execution"""
        # Use process-safe logging configuration
        import multiprocessing
        
        # Create separate log files for each process
        process_id = multiprocessing.current_process().pid
        
        logger = cls.get_logger(f'parallel_process_{process_id}')
        return logger
