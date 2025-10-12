"""
AUTH_001: Validate user can login with valid credentials
Expected Result: User successfully logged in and redirected to dashboard
"""

from global_session import session
from selenium.webdriver.common.by import By

def test_case_successful_login():
    """AUTH_001: Test successful login with valid credentials"""
    test_id = "AUTH_001"
    print(f"\n🧪 Running {test_id}: Successful Login")
    
    try:
        # Use global session to login
        driver = session.login("test@example.com", "password")
        
        # Verify we're on dashboard
        dashboard_element = driver.find_element(By.CSS_SELECTOR, "[data-page='user-dashboard']")
        assert dashboard_element is not None, "Dashboard element not found"
        
        # Verify URL contains dashboard
        current_url = driver.current_url
        assert "dashboard" in current_url, f"Expected dashboard URL, got: {current_url}"
        
        print(f"✓ {test_id}: Successful login test PASSED")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Successful login test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = test_case_successful_login()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
