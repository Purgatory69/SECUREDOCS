"""
Base Page Object Model class
Provides common functionality for all page objects
"""

import os
import time
from datetime import datetime
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
from selenium.common.exceptions import TimeoutException, NoSuchElementException


class BasePage:
    """Base page class with common functionality"""
    
    def __init__(self, driver):
        self.driver = driver
        self.wait = WebDriverWait(driver, 10)
        self.base_url = self.get_base_url()
        
    def get_base_url(self):
        """Get base URL from environment or config"""
        return os.getenv('BASE_URL', 'http://localhost:8000')
        
    def find_element(self, locator, timeout=10):
        """Find element with wait"""
        try:
            return WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located(locator)
            )
        except TimeoutException:
            raise NoSuchElementException(f"Element not found: {locator}")
            
    def find_elements(self, locator, timeout=10):
        """Find multiple elements with wait"""
        try:
            WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located(locator)
            )
            return self.driver.find_elements(*locator)
        except TimeoutException:
            return []
            
    def click_element(self, locator, timeout=10):
        """Click element with wait for clickable"""
        element = WebDriverWait(self.driver, timeout).until(
            EC.element_to_be_clickable(locator)
        )
        element.click()
        
    def clear_and_type(self, locator, text, timeout=10):
        """Clear field and type text"""
        element = self.find_element(locator, timeout)
        element.clear()
        element.send_keys(text)
        
    def get_text(self, locator, timeout=10):
        """Get text from element"""
        element = self.find_element(locator, timeout)
        return element.text
        
    def is_element_visible(self, locator, timeout=5):
        """Check if element is visible"""
        try:
            WebDriverWait(self.driver, timeout).until(
                EC.visibility_of_element_located(locator)
            )
            return True
        except TimeoutException:
            return False
            
    def is_element_present(self, locator, timeout=5):
        """Check if element is present in DOM"""
        try:
            WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located(locator)
            )
            return True
        except TimeoutException:
            return False
            
    def wait_for_element_visible(self, locator, timeout=10):
        """Wait for element to be visible"""
        return WebDriverWait(self.driver, timeout).until(
            EC.visibility_of_element_located(locator)
        )
        
    def wait_for_element_clickable(self, locator, timeout=10):
        """Wait for element to be clickable"""
        return WebDriverWait(self.driver, timeout).until(
            EC.element_to_be_clickable(locator)
        )
        
    def wait_for_url_contains(self, url_fragment, timeout=10):
        """Wait for URL to contain specific fragment"""
        try:
            WebDriverWait(self.driver, timeout).until(
                EC.url_contains(url_fragment)
            )
            return True
        except TimeoutException:
            return False
            
    def scroll_to_element(self, locator):
        """Scroll element into view"""
        element = self.find_element(locator)
        self.driver.execute_script("arguments[0].scrollIntoView(true);", element)
        
    def get_current_url(self):
        """Get current page URL"""
        return self.driver.current_url
        
    def get_page_title(self):
        """Get page title"""
        return self.driver.title
        
    def take_screenshot(self, name=None):
        """Take screenshot with timestamp"""
        if name is None:
            name = f"screenshot_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
            
        screenshot_dir = "screenshots"
        if not os.path.exists(screenshot_dir):
            os.makedirs(screenshot_dir)
            
        screenshot_path = os.path.join(screenshot_dir, f"{name}.png")
        self.driver.save_screenshot(screenshot_path)
        return screenshot_path
        
    def execute_script(self, script, *args):
        """Execute JavaScript"""
        return self.driver.execute_script(script, *args)
        
    def switch_to_frame(self, locator):
        """Switch to iframe"""
        frame = self.find_element(locator)
        self.driver.switch_to.frame(frame)
        
    def switch_to_default_content(self):
        """Switch back to main content"""
        self.driver.switch_to.default_content()
        
    def refresh_page(self):
        """Refresh current page"""
        self.driver.refresh()
        
    def go_back(self):
        """Navigate back"""
        self.driver.back()
        
    def wait_for_page_load(self, timeout=30):
        """Wait for page to fully load"""
        WebDriverWait(self.driver, timeout).until(
            lambda driver: driver.execute_script("return document.readyState") == "complete"
        )
        
    def hover_over_element(self, locator):
        """Hover over element"""
        from selenium.webdriver.common.action_chains import ActionChains
        element = self.find_element(locator)
        ActionChains(self.driver).move_to_element(element).perform()
        
    def double_click_element(self, locator):
        """Double click element"""
        from selenium.webdriver.common.action_chains import ActionChains
        element = self.find_element(locator)
        ActionChains(self.driver).double_click(element).perform()
        
    def right_click_element(self, locator):
        """Right click element"""
        from selenium.webdriver.common.action_chains import ActionChains
        element = self.find_element(locator)
        ActionChains(self.driver).context_click(element).perform()
        
    def drag_and_drop(self, source_locator, target_locator):
        """Drag and drop elements"""
        from selenium.webdriver.common.action_chains import ActionChains
        source = self.find_element(source_locator)
        target = self.find_element(target_locator)
        ActionChains(self.driver).drag_and_drop(source, target).perform()
        
    def select_dropdown_by_text(self, locator, text):
        """Select dropdown option by visible text"""
        from selenium.webdriver.support.ui import Select
        dropdown = Select(self.find_element(locator))
        dropdown.select_by_visible_text(text)
        
    def select_dropdown_by_value(self, locator, value):
        """Select dropdown option by value"""
        from selenium.webdriver.support.ui import Select
        dropdown = Select(self.find_element(locator))
        dropdown.select_by_value(value)
        
    def get_dropdown_options(self, locator):
        """Get all dropdown options"""
        from selenium.webdriver.support.ui import Select
        dropdown = Select(self.find_element(locator))
        return [option.text for option in dropdown.options]
        
    def upload_file(self, locator, file_path):
        """Upload file using file input"""
        if not os.path.exists(file_path):
            raise FileNotFoundError(f"File not found: {file_path}")
            
        file_input = self.find_element(locator)
        file_input.send_keys(os.path.abspath(file_path))
        
    def wait_for_text_in_element(self, locator, text, timeout=10):
        """Wait for specific text to appear in element"""
        try:
            WebDriverWait(self.driver, timeout).until(
                EC.text_to_be_present_in_element(locator, text)
            )
            return True
        except TimeoutException:
            return False
            
    def wait_for_element_to_disappear(self, locator, timeout=10):
        """Wait for element to disappear"""
        try:
            WebDriverWait(self.driver, timeout).until_not(
                EC.presence_of_element_located(locator)
            )
            return True
        except TimeoutException:
            return False
            
    def get_element_attribute(self, locator, attribute, timeout=10):
        """Get element attribute value"""
        element = self.find_element(locator, timeout)
        return element.get_attribute(attribute)
        
    def is_element_enabled(self, locator, timeout=10):
        """Check if element is enabled"""
        element = self.find_element(locator, timeout)
        return element.is_enabled()
        
    def is_element_selected(self, locator, timeout=10):
        """Check if element is selected (for checkboxes/radio buttons)"""
        element = self.find_element(locator, timeout)
        return element.is_selected()
        
    def get_window_handles(self):
        """Get all window handles"""
        return self.driver.window_handles
        
    def switch_to_window(self, window_handle):
        """Switch to specific window"""
        self.driver.switch_to.window(window_handle)
        
    def close_current_window(self):
        """Close current window"""
        self.driver.close()
        
    def accept_alert(self):
        """Accept JavaScript alert"""
        alert = self.driver.switch_to.alert
        alert.accept()
        
    def dismiss_alert(self):
        """Dismiss JavaScript alert"""
        alert = self.driver.switch_to.alert
        alert.dismiss()
        
    def get_alert_text(self):
        """Get alert text"""
        alert = self.driver.switch_to.alert
        return alert.text
