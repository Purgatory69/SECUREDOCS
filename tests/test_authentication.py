from webdriver_utils import web_driver
from common_functions import login, logout, register_user, BASE_URL
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
import time

def test_auth_001_valid_login():
    """AUTH_001: Validate user can login with valid credentials"""
    driver = web_driver()
    try:
        result = login(driver, "test@example.com", "password")
        assert result == True
        
        # Verify we're on dashboard
        dashboard_element = driver.find_element(By.CSS_SELECTOR, "[data-page='user-dashboard']")
        assert dashboard_element is not None
        
        print("✓ AUTH_001: Valid login test passed")
        return True
    except Exception as e:
        print(f"✗ AUTH_001: Valid login test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_auth_002_invalid_email():
    """AUTH_002: Validate user cannot login with invalid email"""
    driver = web_driver()
    try:
        driver.get(f"{BASE_URL}/login")
        
        # Try login with invalid email
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        
        email_field.send_keys("invalid@email.com")
        password_field.send_keys("password")
        
        login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        login_button.click()
        
        # Wait for error message
        time.sleep(2)
        error_messages = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .invalid-feedback")
        assert len(error_messages) > 0
        
        print("✓ AUTH_002: Invalid email test passed")
        return True
    except Exception as e:
        print(f"✗ AUTH_002: Invalid email test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_auth_003_invalid_password():
    """AUTH_003: Validate user cannot login with invalid password"""
    driver = web_driver()
    try:
        driver.get(f"{BASE_URL}/login")
        
        # Try login with invalid password
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        
        email_field.send_keys("test@example.com")
        password_field.send_keys("wrongpassword")
        
        login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        login_button.click()
        
        # Wait for error message
        time.sleep(2)
        error_messages = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .invalid-feedback")
        assert len(error_messages) > 0
        
        print("✓ AUTH_003: Invalid password test passed")
        return True
    except Exception as e:
        print(f"✗ AUTH_003: Invalid password test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_auth_004_empty_fields():
    """AUTH_004: Validate user cannot login with empty fields"""
    driver = web_driver()
    try:
        driver.get(f"{BASE_URL}/login")
        
        # Try login with empty fields
        login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        login_button.click()
        
        # Wait for validation messages
        time.sleep(2)
        validation_messages = driver.find_elements(By.CSS_SELECTOR, ".invalid-feedback, .error, [required]:invalid")
        assert len(validation_messages) > 0
        
        print("✓ AUTH_004: Empty fields test passed")
        return True
    except Exception as e:
        print(f"✗ AUTH_004: Empty fields test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_auth_010_logout():
    """AUTH_010: Validate user can logout successfully"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        
        # Then logout
        result = logout(driver)
        assert result == True
        
        # Verify we're back on login page
        login_form = driver.find_element(By.CSS_SELECTOR, "form[action*='login']")
        assert login_form is not None
        
        print("✓ AUTH_010: Logout test passed")
        return True
    except Exception as e:
        print(f"✗ AUTH_010: Logout test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def run_authentication_tests():
    """Run all authentication tests"""
    print("Running Authentication Tests...")
    tests = [
        test_auth_001_valid_login,
        test_auth_002_invalid_email,
        test_auth_003_invalid_password,
        test_auth_004_empty_fields,
        test_auth_010_logout
    ]
    
    passed = 0
    total = len(tests)
    
    for test in tests:
        if test():
            passed += 1
    
    print(f"\nAuthentication Tests Summary: {passed}/{total} passed")
    return passed == total

if __name__ == "__main__":
    run_authentication_tests()
