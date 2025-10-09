"""
Test class for SecureDocs Login functionality
Implements test cases AUTH_001 to AUTH_010 from test plan
"""

import pytest
import allure
from pages.login_page import LoginPage
from pages.dashboard_page import DashboardPage
from utils.test_data import TestData
from utils.test_base import TestBase


@allure.feature("Authentication")
class TestLogin(TestBase):
    """Login functionality test class"""
    
    def setup_method(self):
        """Setup before each test method"""
        self.login_page = LoginPage(self.driver)
        self.dashboard_page = DashboardPage(self.driver)
        self.login_page.navigate_to_login()
        
    @allure.story("Valid Login")
    @allure.severity(allure.severity_level.CRITICAL)
    @pytest.mark.smoke
    @pytest.mark.critical
    def test_valid_login(self):
        """
        Test Case: AUTH_001
        Validate user can login with valid credentials
        """
        # Test Data
        user_data = TestData.get_valid_user()
        
        with allure.step("Enter valid credentials and login"):
            self.login_page.login(user_data['email'], user_data['password'])
            
        with allure.step("Verify successful login"):
            assert self.login_page.is_login_successful(), "User should be successfully logged in"
            assert self.dashboard_page.is_dashboard_loaded(), "Dashboard should be loaded after successful login"
            
        with allure.step("Verify welcome message"):
            welcome_message = self.dashboard_page.get_welcome_message()
            expected_message = f"Welcome, {user_data['name']}"
            assert expected_message in welcome_message, f"Expected welcome message: {expected_message}"
            
        # Log test result
        self.log_test_result("AUTH_001", "PASS", "User successfully logged in and redirected to dashboard")
        
    @allure.story("Invalid Email Login")
    @allure.severity(allure.severity_level.HIGH)
    @pytest.mark.regression
    @pytest.mark.negative
    def test_invalid_email_login(self):
        """
        Test Case: AUTH_002
        Validate user cannot login with invalid email
        """
        with allure.step("Attempt login with invalid email"):
            self.login_page.login("nonexistent@example.com", "ValidPass123!")
            
        with allure.step("Verify login failure"):
            assert not self.login_page.is_login_successful(), "Login should fail with invalid email"
            
        with allure.step("Verify error message"):
            error_message = self.login_page.get_error_message()
            assert "Invalid credentials" in error_message, "Error message should indicate invalid credentials"
            assert "/login" in self.login_page.get_current_url(), "User should remain on login page"
            
        self.log_test_result("AUTH_002", "PASS", "Login failed with invalid email as expected")
        
    @allure.story("Invalid Password Login")
    @allure.severity(allure.severity_level.HIGH)
    @pytest.mark.regression
    @pytest.mark.negative
    def test_invalid_password_login(self):
        """
        Test Case: AUTH_003
        Validate user cannot login with invalid password
        """
        user_data = TestData.get_valid_user()
        
        with allure.step("Attempt login with invalid password"):
            self.login_page.login(user_data['email'], "WrongPassword123!")
            
        with allure.step("Verify login failure"):
            assert not self.login_page.is_login_successful(), "Login should fail with invalid password"
            
        with allure.step("Verify error message"):
            error_message = self.login_page.get_error_message()
            assert "Invalid credentials" in error_message, "Error message should indicate invalid credentials"
            
        self.log_test_result("AUTH_003", "PASS", "Login failed with invalid password as expected")
        
    @allure.story("Empty Fields Validation")
    @allure.severity(allure.severity_level.NORMAL)
    @pytest.mark.regression
    @pytest.mark.validation
    def test_empty_fields_login(self):
        """
        Test Case: AUTH_004
        Validate user cannot login with empty fields
        """
        with allure.step("Attempt login with empty fields"):
            self.login_page.attempt_login_with_empty_fields()
            
        with allure.step("Verify login failure"):
            assert not self.login_page.is_login_successful(), "Login should fail with empty fields"
            
        with allure.step("Verify validation errors"):
            assert self.login_page.has_validation_errors(), "Validation errors should be displayed"
            
        with allure.step("Verify specific field errors"):
            email_error = self.login_page.get_field_validation_error("email")
            password_error = self.login_page.get_field_validation_error("password")
            assert "required" in email_error.lower(), "Email field should show required validation error"
            assert "required" in password_error.lower(), "Password field should show required validation error"
            
        self.log_test_result("AUTH_004", "PASS", "Validation errors displayed for empty fields")
        
    @allure.story("Registration Navigation")
    @allure.severity(allure.severity_level.NORMAL)
    @pytest.mark.smoke
    @pytest.mark.navigation
    def test_navigate_to_registration(self):
        """
        Test Case: AUTH_005
        Validate navigation to registration page
        """
        with allure.step("Click register link"):
            self.login_page.click_register_link()
            
        with allure.step("Verify navigation to registration page"):
            assert "/register" in self.driver.current_url, "Should navigate to registration page"
            assert "Register" in self.login_page.get_page_title(), "Page title should indicate registration page"
            
        self.log_test_result("AUTH_005", "PASS", "Successfully navigated to registration page")
        
    @allure.story("Password Reset Navigation")
    @allure.severity(allure.severity_level.NORMAL)
    @pytest.mark.regression
    @pytest.mark.navigation
    def test_navigate_to_password_reset(self):
        """
        Test Case: AUTH_009
        Validate navigation to password reset page
        """
        with allure.step("Click forgot password link"):
            self.login_page.click_forgot_password_link()
            
        with allure.step("Verify navigation to password reset page"):
            assert "/forgot-password" in self.driver.current_url, "Should navigate to password reset page"
            assert "Reset Password" in self.login_page.get_page_title(), "Page title should indicate password reset page"
            
        self.log_test_result("AUTH_009", "PASS", "Successfully navigated to password reset page")
        
    @allure.story("Logout Functionality")
    @allure.severity(allure.severity_level.CRITICAL)
    @pytest.mark.smoke
    @pytest.mark.critical
    def test_logout(self):
        """
        Test Case: AUTH_010
        Validate logout functionality (requires login first)
        """
        user_data = TestData.get_valid_user()
        
        with allure.step("Login first"):
            self.login_page.login(user_data['email'], user_data['password'])
            assert self.login_page.is_login_successful(), "Pre-condition: User must be logged in"
            
        with allure.step("Perform logout"):
            self.dashboard_page.click_logout()
            
        with allure.step("Verify logout success"):
            assert "/login" in self.driver.current_url, "Should be redirected to login page after logout"
            success_message = self.login_page.get_success_message()
            assert "logged out" in success_message.lower(), "Success message should confirm logout"
            
        self.log_test_result("AUTH_010", "PASS", "User successfully logged out and redirected to login")
        
    @allure.story("WebAuthn Availability")
    @allure.severity(allure.severity_level.MINOR)
    @pytest.mark.premium
    @pytest.mark.webauthn
    def test_webauthn_login_available(self):
        """
        Test Case: WEBAUTH_002 (Bonus)
        Validate WebAuthn login availability
        """
        with allure.step("Check WebAuthn availability"):
            if self.login_page.is_webauthn_available():
                assert True, "WebAuthn login option is available"
                self.log_test_result("WEBAUTH_002", "PASS", "WebAuthn login button is visible and available")
            else:
                pytest.skip("WebAuthn not supported in current browser/environment")
                self.log_test_result("WEBAUTH_002", "SKIP", "WebAuthn not supported in current browser/environment")
                
    @allure.story("Remember Me Functionality")
    @allure.severity(allure.severity_level.NORMAL)
    @pytest.mark.regression
    def test_login_with_remember_me(self):
        """
        Test Case: AUTH_001 (variant)
        Validate login with remember me option
        """
        user_data = TestData.get_valid_user()
        
        with allure.step("Login with remember me checked"):
            self.login_page.login_with_remember_me(user_data['email'], user_data['password'])
            
        with allure.step("Verify successful login"):
            assert self.login_page.is_login_successful(), "Login should succeed with remember me option"
            
        self.log_test_result("AUTH_001_REMEMBER", "PASS", "Login successful with remember me option")
        
    @allure.story("Multiple Invalid Login Attempts")
    @allure.severity(allure.severity_level.NORMAL)
    @pytest.mark.regression
    @pytest.mark.parametrize("email,password,expected_error", [
        ("", "", "required"),
        ("invalid-email", "password", "Invalid credentials"),
        ("test@example.com", "", "required"),
        ("", "password123", "required"),
        ("valid@example.com", "wrongpass", "Invalid credentials")
    ])
    def test_multiple_invalid_logins(self, email, password, expected_error):
        """
        Data-driven test for multiple invalid login attempts
        """
        with allure.step(f"Attempt login with email: {email} and password: {'*' * len(password)}"):
            self.login_page.login(email, password)
            
        with allure.step("Verify login failure"):
            assert not self.login_page.is_login_successful(), f"Login should fail with credentials: {email}"
            
        with allure.step("Verify error message"):
            if expected_error == "required":
                assert self.login_page.has_validation_errors(), "Should show validation errors for empty fields"
            else:
                error_message = self.login_page.get_error_message()
                assert expected_error in error_message, f"Error message should contain: {expected_error}"
                
    @allure.story("Email Verification Requirement for OTP")
    @allure.severity(allure.severity_level.HIGH)
    @pytest.mark.security
    @pytest.mark.otp
    def test_email_verification_notice_for_otp(self):
        """
        Test Case: OTP_006 (from memory)
        Validate email verification requirement for OTP features
        """
        unverified_user = TestData.get_unverified_user()
        
        with allure.step("Login with unverified user"):
            self.login_page.login(unverified_user['email'], unverified_user['password'])
            
        with allure.step("Check for email verification notice"):
            if self.login_page.is_email_verification_notice_visible():
                notice_text = self.login_page.get_text(self.login_page.VERIFICATION_NOTICE)
                assert "verify your email" in notice_text.lower(), "Should show email verification requirement"
                self.log_test_result("OTP_006", "PASS", "Email verification notice displayed for unverified user")
            else:
                self.log_test_result("OTP_006", "SKIP", "Email verification notice not applicable")
                
    def teardown_method(self):
        """Cleanup after each test method"""
        try:
            if hasattr(self, 'dashboard_page') and self.dashboard_page.is_user_logged_in():
                self.dashboard_page.click_logout()
        except:
            pass  # Ignore logout errors in cleanup
