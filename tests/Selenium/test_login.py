from selenium.webdriver.common.by import By
from base_test import BaseTest
import time

class TestLogin(BaseTest):
    def __init__(self):
        super().__init__()
        self.test_email = "fool@gmail.com"
        self.test_password = "password"
        
    def test_successful_login(self):
        """Test successful login with valid credentials"""
        print("Starting successful login test...")
        
        try:
            self.setup_driver(headless=False)  # Set to True for headless mode
            
            # Navigate to login page
            self.navigate_to("/login")
            self.wait_for_page_load()
            
            # Verify we're on login page
            assert "Login" in self.get_page_title()
            print("✓ Login page loaded successfully")
            
            # Take screenshot
            self.take_screenshot("login_page_loaded")
            
            # Fill login form
            self.fill_input((By.ID, "email"), self.test_email)
            self.fill_input((By.ID, "password"), self.test_password)
            
            print(f"✓ Filled login form with email: {self.test_email}")
            self.take_screenshot("login_form_filled")
            
            # Submit form
            self.click_element((By.XPATH, '//button[@type="submit"]'))
            print("✓ Clicked login button")
            
            # Wait for redirect
            time.sleep(3)
            
            # Take screenshot after login
            self.take_screenshot("after_login")
            
            # Verify successful login
            current_url = self.get_current_url()
            print(f"Current URL after login: {current_url}")
            
            # Check if redirected to dashboard or redirect page
            success = any(path in current_url for path in ["/user/dashboard", "/admin/dashboard", "/redirect-after-login"])
            
            if success:
                print("✓ Login successful - redirected to dashboard")
            else:
                print(f"✗ Login may have failed - current URL: {current_url}")
                
            return success
            
        except Exception as e:
            print(f"✗ Login test failed: {str(e)}")
            self.take_screenshot("login_test_error")
            return False
        finally:
            self.teardown_driver()
            
    def test_invalid_login(self):
        """Test login with invalid credentials"""
        print("Starting invalid login test...")
        
        try:
            self.setup_driver(headless=False)
            
            # Navigate to login page
            self.navigate_to("/login")
            self.wait_for_page_load()
            
            # Fill with invalid credentials
            self.fill_input((By.ID, "email"), "invalid@email.com")
            self.fill_input((By.ID, "password"), "wrongpassword")
            
            print("✓ Filled login form with invalid credentials")
            self.take_screenshot("invalid_login_form")
            
            # Submit form
            self.click_element((By.XPATH, '//button[@type="submit"]'))
            
            # Wait for response
            time.sleep(2)
            self.take_screenshot("after_invalid_login")
            
            # Should stay on login page
            current_url = self.get_current_url()
            success = "/login" in current_url
            
            if success:
                print("✓ Invalid login test passed - stayed on login page")
            else:
                print(f"✗ Invalid login test failed - redirected to: {current_url}")
                
            return success
            
        except Exception as e:
            print(f"✗ Invalid login test failed: {str(e)}")
            self.take_screenshot("invalid_login_error")
            return False
        finally:
            self.teardown_driver()
            
    def test_password_toggle(self):
        """Test password visibility toggle"""
        print("Starting password toggle test...")
        
        try:
            self.setup_driver(headless=False)
            
            # Navigate to login page
            self.navigate_to("/login")
            self.wait_for_page_load()
            
            # Find password field and toggle button
            password_field = self.wait_for_element((By.ID, "password"))
            toggle_button = self.wait_for_element((By.ID, "togglePassword"))
            
            # Initially should be password type
            initial_type = password_field.get_attribute("type")
            assert initial_type == "password"
            print("✓ Password field initially hidden")
            
            # Click toggle
            toggle_button.click()
            time.sleep(0.5)
            
            # Should now be text type
            new_type = password_field.get_attribute("type")
            assert new_type == "text"
            print("✓ Password field now visible")
            
            # Click again to hide
            toggle_button.click()
            time.sleep(0.5)
            
            # Should be password type again
            final_type = password_field.get_attribute("type")
            assert final_type == "password"
            print("✓ Password field hidden again")
            
            self.take_screenshot("password_toggle_test")
            return True
            
        except Exception as e:
            print(f"✗ Password toggle test failed: {str(e)}")
            self.take_screenshot("password_toggle_error")
            return False
        finally:
            self.teardown_driver()

if __name__ == "__main__":
    # Run tests
    login_test = TestLogin()
    
    print("=" * 50)
    print("RUNNING LOGIN TESTS")
    print("=" * 50)
    
    # Test successful login
    result1 = login_test.test_successful_login()
    print()
    
    # Test invalid login
    result2 = login_test.test_invalid_login()
    print()
    
    # Test password toggle
    result3 = login_test.test_password_toggle()
    print()
    
    # Summary
    print("=" * 50)
    print("TEST RESULTS SUMMARY")
    print("=" * 50)
    print(f"Successful Login: {'PASS' if result1 else 'FAIL'}")
    print(f"Invalid Login: {'PASS' if result2 else 'FAIL'}")
    print(f"Password Toggle: {'PASS' if result3 else 'FAIL'}")
    print("=" * 50)
