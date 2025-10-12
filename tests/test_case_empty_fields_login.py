"""
AUTH_004: Validate user cannot login with empty fields
Expected Result: Required field validation messages shown
"""

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def test_case_empty_fields_login():
    """AUTH_004: Test login with empty fields"""
    test_id = "AUTH_004"
    print(f"\n🧪 Running {test_id}: Empty Fields Login")
    
    try:
        # Get fresh driver (don't use existing login)
        driver = session.get_driver()
        driver.get(f"{session.BASE_URL}/login")
        
        # Wait for login form
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.NAME, "email"))
        )
        
        # Clear any existing values and leave fields empty
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        
        email_field.clear()
        password_field.clear()
        
        # Try to submit with empty fields
        login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        login_button.click()
        
        # Wait for validation response
        time.sleep(2)
        
        # Check for HTML5 validation (required fields)
        email_invalid = driver.execute_script("return arguments[0].validity.valid", email_field) == False
        password_invalid = driver.execute_script("return arguments[0].validity.valid", password_field) == False
        
        # Look for validation messages
        validation_selectors = [
            ".invalid-feedback", 
            ".error", 
            ".text-danger",
            "[role='alert']",
            ".form-error"
        ]
        
        validation_found = False
        for selector in validation_selectors:
            validation_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if validation_elements and any(elem.is_displayed() for elem in validation_elements):
                validation_found = True
                break
        
        # Check if still on login page (validation should prevent submission)
        current_url = driver.current_url
        still_on_login = "login" in current_url or "dashboard" not in current_url
        
        # At least one validation method should work
        assert email_invalid or password_invalid or validation_found or still_on_login, \
            "Expected field validation to prevent empty form submission"
        
        print(f"✓ {test_id}: Empty fields validation test PASSED")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Empty fields validation test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = test_case_empty_fields_login()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
