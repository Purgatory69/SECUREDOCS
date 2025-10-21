"""
Global Session Manager for SecureDocs Tests
Manages a single login session that can be shared across all test cases
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_utils import web_driver
import time

class GlobalSession:
    _instance = None
    _driver = None
    _logged_in = False
    
    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(GlobalSession, cls).__new__(cls)
        return cls._instance
    
    def __init__(self):
        self.BASE_URL = "https://securedocs.live"
        # User account credentials
        self.test_user_email = "premium@gmail.com"
        self.test_user_password = "password"
        # Admin account credentials
        self.test_admin_email = "admin@gmail.com"
        self.test_admin_password = "admin123"
        self.current_account_type = None
    
    def get_driver(self):
        """Get or create the shared driver instance"""
        if self._driver is None:
            self._driver = web_driver()
            print("Created new browser session")
        return self._driver
    
    def login(self, email=None, password=None, account_type="user"):
        """Login once and maintain session across tests"""
        if self._logged_in and self.current_account_type == account_type:
            print(f"Already logged in as {account_type}, using existing session")
            return self._driver
        elif self._logged_in and self.current_account_type != account_type:
            print(f"Switching from {self.current_account_type} to {account_type} account")
            self.reset_session()
            
        driver = self.get_driver()
        
        # Set credentials based on account type
        if account_type == "admin":
            email = email or self.test_admin_email
            password = password or self.test_admin_password
        else:
            email = email or self.test_user_email
            password = password or self.test_user_password
        
        try:
            print(f"Logging in as {email}...")
            driver.get(f"{self.BASE_URL}/login")
            
            # Wait for login form to load
            email_field = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.NAME, "email"))
            )
            password_field = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.NAME, "password"))
            )
            
            # Fill credentials
            email_field.clear()
            email_field.send_keys(email)
            password_field.clear()
            password_field.send_keys(password)
            
            # Submit login
            login_button = WebDriverWait(driver, 10).until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']"))
            )
            login_button.click()
            
            # Wait for dashboard based on account type
            if account_type == "admin":
                WebDriverWait(driver, 20).until(
                    EC.any_of(
                        EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='admin-dashboard']")),
                        EC.presence_of_element_located((By.CSS_SELECTOR, ".admin-dashboard")),
                        EC.url_contains("admin")
                    )
                )
            else:
                # Wait for user dashboard with multiple success indicators
                WebDriverWait(driver, 20).until(
                    EC.any_of(
                        EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']")),
                        EC.presence_of_element_located((By.ID, "filesContainer")),
                        EC.url_contains("dashboard")
                    )
                )
            
            self._logged_in = True
            self.current_account_type = account_type
            print(f"Login successful as {account_type} - session established")
            return driver
            
        except Exception as e:
            print(f"Login failed: {str(e)}")
            self.cleanup()
            raise e
    
    def navigate_to_dashboard(self, account_type=None):
        """Navigate to dashboard using existing session"""
        account_type = account_type or self.current_account_type or "user"
        
        if not self._logged_in:
            self.login(account_type=account_type)
            
        driver = self.get_driver()
        current_url = driver.current_url
        
        if "dashboard" not in current_url:
            if account_type == "admin":
                driver.get(f"{self.BASE_URL}/admin/dashboard")
                WebDriverWait(driver, 10).until(
                    EC.any_of(
                        EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='admin-dashboard']")),
                        EC.presence_of_element_located((By.CSS_SELECTOR, ".admin-dashboard")),
                        EC.url_contains("admin")
                    )
                )
            else:
                driver.get(f"{self.BASE_URL}/dashboard")
                WebDriverWait(driver, 10).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
                )
        
        return driver
    
    def is_logged_in(self):
        """Check if currently logged in"""
        return self._logged_in and self._driver is not None
    
    def cleanup(self):
        """Close browser and reset session"""
        if self._driver:
            try:
                self._driver.quit()
                print("Browser session closed")
            except:
                pass
            
        self._driver = None
        self._logged_in = False
        self.current_account_type = None
    
    def reset_session(self):
        """Force logout and cleanup for fresh session"""
        print("Resetting session...")
        self.cleanup()

# Global session instance
session = GlobalSession()
