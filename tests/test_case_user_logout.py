"""
AUTH_010: Validate user can logout successfully
Expected Result: User logged out and redirected to login
"""

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def test_case_user_logout():
    """AUTH_010: Test user logout functionality"""
    test_id = "AUTH_010"
    print(f"\n🧪 Running {test_id}: User Logout")
    
    try:
        # First ensure we're logged in
        driver = session.login()
        
        # Navigate to dashboard to ensure we're in logged-in state
        session.navigate_to_dashboard()
        
        # Find and click logout option
        # Try different common logout selectors
        logout_selectors = [
            "a[href*='logout']",
            ".logout-btn",
            ".dropdown-menu a[href*='logout']",
            "form[action*='logout'] button",
            "[data-action='logout']"
        ]
        
        logout_clicked = False
        
        for selector in logout_selectors:
            try:
                # First try to open user dropdown if it exists
                try:
                    user_dropdown = driver.find_element(By.CSS_SELECTOR, ".dropdown-toggle, .user-menu, .user-dropdown")
                    user_dropdown.click()
                    time.sleep(1)  # Wait for dropdown to open
                except:
                    pass  # No dropdown needed
                
                # Try to find logout element
                logout_element = driver.find_element(By.CSS_SELECTOR, selector)
                if logout_element.is_displayed():
                    logout_element.click()
                    logout_clicked = True
                    break
                    
            except:
                continue  # Try next selector
        
        if not logout_clicked:
            # Alternative: try to submit logout form if exists
            try:
                logout_form = driver.find_element(By.CSS_SELECTOR, "form[action*='logout']")
                logout_form.submit()
                logout_clicked = True
            except:
                pass
        
        assert logout_clicked, "Could not find or click logout option"
        
        # Wait for redirect to login page
        time.sleep(3)
        
        # Verify we're back on login page
        current_url = driver.current_url
        login_indicators = [
            "login" in current_url,
            driver.find_elements(By.CSS_SELECTOR, "form[action*='login']"),
            driver.find_elements(By.NAME, "email"),  # Login form field
            driver.find_elements(By.CSS_SELECTOR, ".login-form")
        ]
        
        is_on_login_page = any([
            "login" in current_url,
            len(driver.find_elements(By.CSS_SELECTOR, "form[action*='login']")) > 0,
            len(driver.find_elements(By.NAME, "email")) > 0
        ])
        
        assert is_on_login_page, f"Expected to be on login page, current URL: {current_url}"
        
        # Reset session since we logged out
        session._logged_in = False
        
        print(f"✓ {test_id}: User logout test PASSED")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: User logout test FAILED - {str(e)}")
        # Reset session on failure
        session.reset_session()
        return False

if __name__ == "__main__":
    try:
        result = test_case_user_logout()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
