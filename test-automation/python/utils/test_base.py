"""
Base test class providing common test functionality
"""

import os
import pytest
import allure
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.firefox.service import Service as FirefoxService
from selenium.webdriver.edge.service import Service as EdgeService
from webdriver_manager.chrome import ChromeDriverManager
from webdriver_manager.firefox import GeckoDriverManager
from webdriver_manager.microsoft import EdgeChromiumDriverManager
from utils.config import Config
from utils.logger import Logger


class TestBase:
    """Base test class with common setup and teardown"""
    
    driver = None
    config = None
    logger = None
    
    @classmethod
    def setup_class(cls):
        """Setup before all tests in class"""
        cls.config = Config()
        cls.logger = Logger.get_logger()
        cls.logger.info(f"Starting test class: {cls.__name__}")
        
    @classmethod
    def teardown_class(cls):
        """Teardown after all tests in class"""
        cls.logger.info(f"Finished test class: {cls.__name__}")
        
    def setup_method(self):
        """Setup before each test method"""
        self.driver = self.get_driver()
        self.driver.maximize_window()
        self.driver.implicitly_wait(self.config.get_implicit_wait())
        self.logger.info(f"Starting test method: {self._get_test_method_name()}")
        
    def teardown_method(self):
        """Teardown after each test method"""
        test_method = self._get_test_method_name()
        
        # Take screenshot on test failure
        if hasattr(self, '_pytest_current_test') and 'FAILED' in str(self._pytest_current_test):
            self.take_screenshot_on_failure()
            
        if self.driver:
            self.driver.quit()
            
        self.logger.info(f"Finished test method: {test_method}")
        
    def get_driver(self):
        """Initialize and return WebDriver instance"""
        browser = self.config.get_browser().lower()
        headless = self.config.is_headless()
        
        if browser == 'chrome':
            return self._get_chrome_driver(headless)
        elif browser == 'firefox':
            return self._get_firefox_driver(headless)
        elif browser == 'edge':
            return self._get_edge_driver(headless)
        else:
            raise ValueError(f"Unsupported browser: {browser}")
            
    def _get_chrome_driver(self, headless=False):
        """Get Chrome WebDriver"""
        options = webdriver.ChromeOptions()
        
        if headless:
            options.add_argument('--headless')
            
        # Common Chrome options
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--disable-gpu')
        options.add_argument('--disable-extensions')
        options.add_argument('--disable-notifications')
        options.add_argument('--disable-popup-blocking')
        options.add_argument('--start-maximized')
        
        # Performance optimizations
        options.add_argument('--disable-logging')
        options.add_argument('--disable-default-apps')
        options.add_argument('--disable-background-timer-throttling')
        options.add_argument('--disable-backgrounding-occluded-windows')
        options.add_argument('--disable-renderer-backgrounding')
        
        service = ChromeService(ChromeDriverManager().install())
        return webdriver.Chrome(service=service, options=options)
        
    def _get_firefox_driver(self, headless=False):
        """Get Firefox WebDriver"""
        options = webdriver.FirefoxOptions()
        
        if headless:
            options.add_argument('--headless')
            
        # Firefox preferences
        options.set_preference('dom.webnotifications.enabled', False)
        options.set_preference('dom.push.enabled', False)
        
        service = FirefoxService(GeckoDriverManager().install())
        return webdriver.Firefox(service=service, options=options)
        
    def _get_edge_driver(self, headless=False):
        """Get Edge WebDriver"""
        options = webdriver.EdgeOptions()
        
        if headless:
            options.add_argument('--headless')
            
        # Edge options
        options.add_argument('--disable-notifications')
        options.add_argument('--disable-popup-blocking')
        options.add_argument('--start-maximized')
        
        service = EdgeService(EdgeChromiumDriverManager().install())
        return webdriver.Edge(service=service, options=options)
        
    def take_screenshot_on_failure(self):
        """Take screenshot when test fails"""
        test_name = self._get_test_method_name()
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        screenshot_name = f"FAILED_{test_name}_{timestamp}"
        
        screenshot_path = self.take_screenshot(screenshot_name)
        
        # Attach to Allure report
        if screenshot_path:
            allure.attach.file(
                screenshot_path,
                name=f"Screenshot - {test_name}",
                attachment_type=allure.attachment_type.PNG
            )
            
    def take_screenshot(self, name=None):
        """Take screenshot with custom name"""
        if not self.driver:
            return None
            
        if name is None:
            name = f"screenshot_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
            
        screenshot_dir = self.config.get_screenshot_dir()
        if not os.path.exists(screenshot_dir):
            os.makedirs(screenshot_dir)
            
        screenshot_path = os.path.join(screenshot_dir, f"{name}.png")
        
        try:
            self.driver.save_screenshot(screenshot_path)
            self.logger.info(f"Screenshot saved: {screenshot_path}")
            return screenshot_path
        except Exception as e:
            self.logger.error(f"Failed to take screenshot: {str(e)}")
            return None
            
    def log_test_result(self, test_case_id, result, description):
        """Log test result for tracking"""
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        log_message = f"Test Case: {test_case_id} | Result: {result} | Description: {description} | Time: {timestamp}"
        
        if result.upper() == "PASS":
            self.logger.info(log_message)
        elif result.upper() == "FAIL":
            self.logger.error(log_message)
        else:
            self.logger.warning(log_message)
            
        # Write to CSV for test tracking
        self._write_result_to_csv(test_case_id, result, description, timestamp)
        
        # Attach to Allure
        allure.attach(
            log_message,
            name=f"Test Result - {test_case_id}",
            attachment_type=allure.attachment_type.TEXT
        )
        
    def _write_result_to_csv(self, test_case_id, result, description, timestamp):
        """Write test result to CSV file"""
        try:
            results_dir = "test-results"
            if not os.path.exists(results_dir):
                os.makedirs(results_dir)
                
            results_file = os.path.join(results_dir, "test_execution_results.csv")
            
            # Create header if file doesn't exist
            if not os.path.exists(results_file):
                with open(results_file, 'w') as f:
                    f.write("Test Case ID,Date Tested,Result,Description,Timestamp\n")
                    
            # Append result
            with open(results_file, 'a') as f:
                f.write(f"{test_case_id},{timestamp.split()[0]},{result},{description},{timestamp}\n")
                
        except Exception as e:
            self.logger.error(f"Failed to write result to CSV: {str(e)}")
            
    def _get_test_method_name(self):
        """Get current test method name"""
        import inspect
        return inspect.stack()[2].function
        
    def assert_with_screenshot(self, condition, message="Assertion failed"):
        """Assert with automatic screenshot on failure"""
        if not condition:
            self.take_screenshot(f"assertion_failed_{self._get_test_method_name()}")
            
        assert condition, message
        
    def wait_for_page_load(self, timeout=30):
        """Wait for page to fully load"""
        from selenium.webdriver.support.ui import WebDriverWait
        WebDriverWait(self.driver, timeout).until(
            lambda driver: driver.execute_script("return document.readyState") == "complete"
        )
        
    def get_browser_name(self):
        """Get current browser name"""
        return self.config.get_browser()
        
    def get_base_url(self):
        """Get base URL for testing"""
        return self.config.get_base_url()
        
    def add_test_step(self, step_description):
        """Add test step to Allure report"""
        allure.step(step_description)
        self.logger.info(f"Test Step: {step_description}")
        
    def skip_test(self, reason):
        """Skip test with reason"""
        pytest.skip(reason)
        
    def mark_test_as_xfail(self, reason):
        """Mark test as expected to fail"""
        pytest.xfail(reason)
        
    @pytest.fixture(autouse=True)
    def capture_screenshot_on_failure(self, request):
        """Pytest fixture to capture screenshot on failure"""
        self._pytest_current_test = request.node.name
        yield
        if request.node.rep_call.failed:
            self.take_screenshot_on_failure()
            
    @pytest.hookimpl(hookwrapper=True)
    def pytest_runtest_makereport(self, item, call):
        """Pytest hook to capture test results"""
        outcome = yield
        rep = outcome.get_result()
        setattr(item, f"rep_{rep.when}", rep)
