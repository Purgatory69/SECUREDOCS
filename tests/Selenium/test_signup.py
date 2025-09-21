from selenium.webdriver.common.by import By
from base_test import BaseTest
import time
import random
import string

class TestSignup(BaseTest):
    def __init__(self):
        super().__init__()
        self.test_name = "Test User"
        self.test_password = "SecurePassword123!"
        
    def generate_random_email(self):
        """Generate a random email for testing"""
        random_string = ''.join(random.choices(string.ascii_lowercase + string.digits, k=8))
        return f"testuser_{random_string}@example.com"
        
    def test_successful_signup(self):
        """Test successful user registration"""
        print("Starting successful signup test...")
        
        try:
            self.setup_driver(headless=False)  # Set to True for headless mode
            
            # Navigate to signup page
            self.navigate_to("/register")
            self.wait_for_page_load()
            
            # Verify we're on signup page
            assert "Register" in self.get_page_title()
            print("✓ Signup page loaded successfully")
            
            # Take screenshot
            self.take_screenshot("signup_page_loaded")
            
            # Generate unique email
            test_email = self.generate_random_email()
            
            # Fill signup form
            self.fill_input((By.ID, "name"), self.test_name)
            self.fill_input((By.ID, "email"), test_email)
            self.fill_input((By.ID, "password"), self.test_password)
            self.fill_input((By.ID, "password_confirmation"), self.test_password)
            
            print(f"✓ Filled signup form with email: {test_email}")
            self.take_screenshot("signup_form_filled")
            
            # Submit form
            self.click_element((By.XPATH, '//button[@type="submit"]'))
            print("✓ Clicked signup button")
            
            # Wait for processing
            time.sleep(3)
            
            # Take screenshot after signup
            self.take_screenshot("after_signup")
            
            # Verify successful signup
            current_url = self.get_current_url()
            print(f"Current URL after signup: {current_url}")
            
            # Check if redirected away from register page
            success = "/register" not in current_url or any(path in current_url for path in ["/email/verify", "/login", "/dashboard"])
            
            if success:
                print("✓ Signup successful - redirected away from register page")
            else:
                print(f"✗ Signup may have failed - still on register page: {current_url}")
                
            return success
            
        except Exception as e:
            print(f"✗ Signup test failed: {str(e)}")
            self.take_screenshot("signup_test_error")
            return False
        finally:
            self.teardown_driver()
            
    def test_existing_email_signup(self):
        """Test signup with existing email"""
        print("Starting existing email signup test...")
        
        try:
            self.setup_driver(headless=False)
            
            # Navigate to signup page
            self.navigate_to("/register")
            self.wait_for_page_load()
            
            # Fill form with existing email
            self.fill_input((By.ID, "name"), "Another User")
            self.fill_input((By.ID, "email"), "fool@gmail.com")  # Existing email
            self.fill_input((By.ID, "password"), self.test_password)
            self.fill_input((By.ID, "password_confirmation"), self.test_password)
            
            print("✓ Filled signup form with existing email")
            self.take_screenshot("existing_email_signup_form")
            
            # Submit form
            self.click_element((By.XPATH, '//button[@type="submit"]'))
            
            # Wait for validation
            time.sleep(2)
            self.take_screenshot("after_existing_email_signup")
            
            # Should stay on register page
            current_url = self.get_current_url()
            success = "/register" in current_url
            
            if success:
                print("✓ Existing email test passed - stayed on register page")
                
                # Check for validation errors
                error_elements = self.driver.find_elements(By.CLASS_NAME, "text-red-600")
                if error_elements:
                    print("✓ Validation error displayed")
                else:
                    print("? No validation error found (might be handled differently)")
            else:
                print(f"✗ Existing email test failed - redirected to: {current_url}")
                
            return success
            
        except Exception as e:
            print(f"✗ Existing email test failed: {str(e)}")
            self.take_screenshot("existing_email_error")
            return False
        finally:
            self.teardown_driver()
            
    def test_password_mismatch(self):
        """Test signup with mismatched passwords"""
        print("Starting password mismatch test...")
        
        try:
            self.setup_driver(headless=False)
            
            # Navigate to signup page
            self.navigate_to("/register")
            self.wait_for_page_load()
            
            # Fill form with mismatched passwords
            test_email = self.generate_random_email()
            self.fill_input((By.ID, "name"), self.test_name)
            self.fill_input((By.ID, "email"), test_email)
            self.fill_input((By.ID, "password"), "Password123!")
            self.fill_input((By.ID, "password_confirmation"), "DifferentPassword123!")
            
            print("✓ Filled signup form with mismatched passwords")
            self.take_screenshot("password_mismatch_form")
            
            # Submit form
            self.click_element((By.XPATH, '//button[@type="submit"]'))
            
            # Wait for validation
            time.sleep(2)
            self.take_screenshot("after_password_mismatch")
            
            # Should stay on register page
            current_url = self.get_current_url()
            success = "/register" in current_url
            
            if success:
                print("✓ Password mismatch test passed - stayed on register page")
            else:
                print(f"✗ Password mismatch test failed - redirected to: {current_url}")
                
            return success
            
        except Exception as e:
            print(f"✗ Password mismatch test failed: {str(e)}")
            self.take_screenshot("password_mismatch_error")
            return False
        finally:
            self.teardown_driver()
            
    def test_password_toggle(self):
        """Test password visibility toggle in signup form"""
        print("Starting signup password toggle test...")
        
        try:
            self.setup_driver(headless=False)
            
            # Navigate to signup page
            self.navigate_to("/register")
            self.wait_for_page_load()
            
            # Find password fields and toggle buttons
            password_field = self.wait_for_element((By.ID, "password"))
            confirm_field = self.wait_for_element((By.ID, "password_confirmation"))
            toggle_buttons = self.driver.find_elements(By.CLASS_NAME, "toggle-both")
            
            # Initially should be password type
            assert password_field.get_attribute("type") == "password"
            assert confirm_field.get_attribute("type") == "password"
            print("✓ Password fields initially hidden")
            
            # Click first toggle (should toggle both)
            toggle_buttons[0].click()
            time.sleep(0.5)
            
            # Both should now be text type
            assert password_field.get_attribute("type") == "text"
            assert confirm_field.get_attribute("type") == "text"
            print("✓ Password fields now visible")
            
            # Click again to hide
            toggle_buttons[1].click()
            time.sleep(0.5)
            
            # Both should be password type again
            assert password_field.get_attribute("type") == "password"
            assert confirm_field.get_attribute("type") == "password"
            print("✓ Password fields hidden again")
            
            self.take_screenshot("signup_password_toggle_test")
            return True
            
        except Exception as e:
            print(f"✗ Signup password toggle test failed: {str(e)}")
            self.take_screenshot("signup_password_toggle_error")
            return False
        finally:
            self.teardown_driver()
            
    def test_navigation_to_login(self):
        """Test navigation from signup to login page"""
        print("Starting navigation to login test...")
        
        try:
            self.setup_driver(headless=False)
            
            # Navigate to signup page
            self.navigate_to("/register")
            self.wait_for_page_load()
            
            # Find and click login link
            login_link = self.wait_for_element((By.XPATH, f'//a[@href="{self.base_url}/login"]'))
            login_link.click()
            
            # Wait for navigation
            self.wait_for_page_load()
            
            # Verify we're on login page
            current_url = self.get_current_url()
            success = "/login" in current_url
            
            if success:
                print("✓ Successfully navigated to login page")
            else:
                print(f"✗ Navigation failed - current URL: {current_url}")
                
            self.take_screenshot("navigated_to_login")
            return success
            
        except Exception as e:
            print(f"✗ Navigation test failed: {str(e)}")
            self.take_screenshot("navigation_error")
            return False
        finally:
            self.teardown_driver()

if __name__ == "__main__":
    # Run tests
    signup_test = TestSignup()
    
    print("=" * 50)
    print("RUNNING SIGNUP TESTS")
    print("=" * 50)
    
    # Test successful signup
    result1 = signup_test.test_successful_signup()
    print()
    
    # Test existing email
    result2 = signup_test.test_existing_email_signup()
    print()
    
    # Test password mismatch
    result3 = signup_test.test_password_mismatch()
    print()
    
    # Test password toggle
    result4 = signup_test.test_password_toggle()
    print()
    
    # Test navigation
    result5 = signup_test.test_navigation_to_login()
    print()
    
    # Summary
    print("=" * 50)
    print("TEST RESULTS SUMMARY")
    print("=" * 50)
    print(f"Successful Signup: {'PASS' if result1 else 'FAIL'}")
    print(f"Existing Email: {'PASS' if result2 else 'FAIL'}")
    print(f"Password Mismatch: {'PASS' if result3 else 'FAIL'}")
    print(f"Password Toggle: {'PASS' if result4 else 'FAIL'}")
    print(f"Navigation to Login: {'PASS' if result5 else 'FAIL'}")
    print("=" * 50)
