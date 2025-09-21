from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time
import os
from datetime import datetime

class BaseTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8000"
        
    def setup_driver(self, headless=True):
        """Setup Chrome WebDriver with options"""
        chrome_options = Options()
        if headless:
            chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--disable-web-security")
        chrome_options.add_argument("--ignore-certificate-errors")
        
        # Use webdriver-manager to automatically handle ChromeDriver
        service = Service(ChromeDriverManager().install())
        self.driver = webdriver.Chrome(service=service, options=chrome_options)
        self.wait = WebDriverWait(self.driver, 10)
        
    def teardown_driver(self):
        """Close the browser"""
        if self.driver:
            self.driver.quit()
            
    def navigate_to(self, path):
        """Navigate to a specific URL"""
        url = f"{self.base_url}{path}"
        self.driver.get(url)
        
    def wait_for_element(self, locator, timeout=10):
        """Wait for element to be present and visible"""
        wait = WebDriverWait(self.driver, timeout)
        return wait.until(EC.visibility_of_element_located(locator))
        
    def wait_for_clickable(self, locator, timeout=10):
        """Wait for element to be clickable"""
        wait = WebDriverWait(self.driver, timeout)
        return wait.until(EC.element_to_be_clickable(locator))
        
    def fill_input(self, locator, value):
        """Fill an input field"""
        element = self.wait_for_element(locator)
        element.clear()
        element.send_keys(value)
        
    def click_element(self, locator):
        """Click an element"""
        element = self.wait_for_clickable(locator)
        element.click()
        
    def get_current_url(self):
        """Get current page URL"""
        return self.driver.current_url
        
    def get_page_title(self):
        """Get current page title"""
        return self.driver.title
        
    def element_exists(self, locator):
        """Check if element exists"""
        try:
            self.driver.find_element(*locator)
            return True
        except:
            return False
            
    def take_screenshot(self, filename):
        """Take a screenshot for debugging"""
        screenshots_dir = os.path.join(os.path.dirname(__file__), "screenshots")
        if not os.path.exists(screenshots_dir):
            os.makedirs(screenshots_dir)
            
        timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
        screenshot_path = os.path.join(screenshots_dir, f"{filename}_{timestamp}.png")
        self.driver.save_screenshot(screenshot_path)
        print(f"Screenshot saved: {screenshot_path}")
        
    def wait_for_page_load(self, timeout=10):
        """Wait for page to load completely"""
        wait = WebDriverWait(self.driver, timeout)
        wait.until(lambda driver: driver.execute_script("return document.readyState") == "complete")
