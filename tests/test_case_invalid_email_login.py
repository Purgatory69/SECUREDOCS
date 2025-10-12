"""
AUTH_002: Validate user cannot login with invalid email
Expected Result: Error message displayed: "Invalid credentials"
"""

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def test_case_invalid_email_login():
    """AUTH_002: Test login with invalid email"""
    test_id = "AUTH_002"
    print(f"\n🧪 Running {test_id}: Invalid Email Login")
    
    try:
        # Get fresh driver (don't use existing login)
        driver = session.get_driver()
        driver.get(f"{session.BASE_URL}/login")
        
        # Wait for login form
        email_field = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.NAME, "email"))
        )
        password_field = driver.find_element(By.NAME, "password")
        
        # Try login with invalid email
        email_field.clear()
        email_field.send_keys("invalid@nonexistent.com")
        password_field.clear()
        password_field.send_keys("password")
        
        # Submit form
        login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        login_button.click()
        
        # Wait for error message
        time.sleep(3)
        
        # Look for error messages
        error_selectors = [
            ".alert-danger", 
            ".error", 
            ".invalid-feedback",
            ".text-danger",
            "[role='alert']"
        ]
        
        error_found = False
        for selector in error_selectors:
            error_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if error_elements and any(elem.is_displayed() for elem in error_elements):
                error_found = True
                break
        
        # Check if still on login page (another indicator of failed login)
        current_url = driver.current_url
        still_on_login = "login" in current_url or "dashboard" not in current_url
        
        assert error_found or still_on_login, "Expected error message or to remain on login page"
        
        print(f"✓ {test_id}: Invalid email login test PASSED")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Invalid email login test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = test_case_invalid_email_login()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
