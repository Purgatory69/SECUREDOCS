"""
Page Object Model for SecureDocs Login Page
Covers test cases: AUTH_001 to AUTH_010
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from .base_page import BasePage


class LoginPage(BasePage):
    """Login page object model class"""
    
    # Page URL
    LOGIN_URL = "/login"
    
    # Locators
    EMAIL_FIELD = (By.ID, "email")
    PASSWORD_FIELD = (By.ID, "password")
    LOGIN_BUTTON = (By.CSS_SELECTOR, "button[type='submit']")
    REGISTER_LINK = (By.LINK_TEXT, "Register")
    FORGOT_PASSWORD_LINK = (By.LINK_TEXT, "Forgot Your Password?")
    ERROR_MESSAGE = (By.CSS_SELECTOR, ".alert-danger")
    SUCCESS_MESSAGE = (By.CSS_SELECTOR, ".alert-success")
    REMEMBER_ME_CHECKBOX = (By.ID, "remember")
    LANGUAGE_SELECTOR = (By.CSS_SELECTOR, "select[name='language']")
    WEBAUTHN_LOGIN_BTN = (By.ID, "webauthn-login-btn")
    VERIFICATION_NOTICE = (By.CSS_SELECTOR, ".verification-notice")
    
    def __init__(self, driver):
        super().__init__(driver)
        
    def navigate_to_login(self):
        """Navigate to login page"""
        self.driver.get(self.base_url + self.LOGIN_URL)
        self.wait_for_page_load()
        
    def wait_for_page_load(self):
        """Wait for login page to load"""
        self.wait_for_element_visible(self.EMAIL_FIELD)
        
    def login(self, email, password):
        """
        Perform login with credentials
        Test Cases: AUTH_001, AUTH_002, AUTH_003, AUTH_004
        """
        self.clear_and_type(self.EMAIL_FIELD, email)
        self.clear_and_type(self.PASSWORD_FIELD, password)
        self.click_element(self.LOGIN_BUTTON)
        
    def login_with_remember_me(self, email, password):
        """
        Perform login with remember me option
        Test Case: AUTH_001 (variant)
        """
        self.clear_and_type(self.EMAIL_FIELD, email)
        self.clear_and_type(self.PASSWORD_FIELD, password)
        
        remember_checkbox = self.find_element(self.REMEMBER_ME_CHECKBOX)
        if not remember_checkbox.is_selected():
            self.click_element(self.REMEMBER_ME_CHECKBOX)
            
        self.click_element(self.LOGIN_BUTTON)
        
    def attempt_login_with_empty_fields(self):
        """
        Attempt login with empty fields
        Test Case: AUTH_004
        """
        self.click_element(self.LOGIN_BUTTON)
        
    def click_register_link(self):
        """
        Navigate to registration page
        Test Case: AUTH_005
        """
        self.click_element(self.REGISTER_LINK)
        
    def click_forgot_password_link(self):
        """
        Navigate to password reset page
        Test Case: AUTH_009
        """
        self.click_element(self.FORGOT_PASSWORD_LINK)
        
    def switch_language(self, language):
        """
        Switch language
        Related to: DASH_004
        """
        language_select = Select(self.find_element(self.LANGUAGE_SELECTOR))
        language_select.select_by_visible_text(language)
        
    def click_webauthn_login(self):
        """
        Initiate WebAuthn login
        Test Cases: WEBAUTH_002
        """
        self.click_element(self.WEBAUTHN_LOGIN_BTN)
        
    # Validation Methods
    
    def is_login_successful(self, timeout=10):
        """
        Check if login was successful by verifying redirect
        Test Case: AUTH_001
        """
        try:
            WebDriverWait(self.driver, timeout).until(
                EC.url_contains("/dashboard")
            )
            return True
        except TimeoutException:
            return False
            
    def get_error_message(self, timeout=5):
        """
        Get error message text
        Test Cases: AUTH_002, AUTH_003, AUTH_004
        """
        try:
            error_element = WebDriverWait(self.driver, timeout).until(
                EC.visibility_of_element_located(self.ERROR_MESSAGE)
            )
            return error_element.text
        except TimeoutException:
            return ""
            
    def get_success_message(self, timeout=5):
        """
        Get success message text
        Test Case: AUTH_005
        """
        try:
            success_element = WebDriverWait(self.driver, timeout).until(
                EC.visibility_of_element_located(self.SUCCESS_MESSAGE)
            )
            return success_element.text
        except TimeoutException:
            return ""
            
    def is_email_verification_notice_visible(self):
        """
        Check if email verification notice is displayed
        Test Case: OTP_006
        """
        try:
            return self.find_element(self.VERIFICATION_NOTICE).is_displayed()
        except NoSuchElementException:
            return False
            
    def has_validation_errors(self):
        """
        Check if login form has validation errors
        Test Case: AUTH_004
        """
        try:
            email_error = self.driver.find_element(By.CSS_SELECTOR, "#email + .error-message")
            password_error = self.driver.find_element(By.CSS_SELECTOR, "#password + .error-message")
            return email_error.is_displayed() or password_error.is_displayed()
        except NoSuchElementException:
            return False
            
    def get_field_validation_error(self, field_id):
        """
        Verify specific field validation error
        Test Case: AUTH_004
        """
        try:
            error_element = self.driver.find_element(
                By.CSS_SELECTOR, f"#{field_id} + .error-message"
            )
            return error_element.text
        except NoSuchElementException:
            return ""
            
    def is_webauthn_available(self):
        """
        Check if WebAuthn is supported/available
        Test Case: WEBAUTH_004
        """
        try:
            return self.find_element(self.WEBAUTHN_LOGIN_BTN).is_displayed()
        except NoSuchElementException:
            return False
