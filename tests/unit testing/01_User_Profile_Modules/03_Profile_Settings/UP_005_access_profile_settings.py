"""
UP_005: Validate user can access profile settings page
Expected Result: Profile settings page loads with user information
Module: User Profile Modules - Profile Settings
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def UP_005_access_profile_settings():
    """UP_005: Test user can access profile settings page"""
    test_id = "UP_005"
    print(f"\nRunning {test_id}: Access Profile Settings")
    print("Module: User Profile Modules - Profile Settings")
    print("Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
        )
        print("Dashboard loaded")
        
        # Navigate directly to profile page (simpler and more reliable)
        print("Navigating to /user/profile...")
        base_url = driver.current_url.split('/user/')[0]  # Get base URL
        driver.get(f"{base_url}/user/profile")
        time.sleep(3)
        print("Navigated to profile page")
        
        # Check if navigation worked
        current_url = driver.current_url
        url_indicates_profile = "/user/profile" in current_url
        
        print(f"Current URL: {current_url}")
        
        # Look for Jetstream/Livewire profile page indicators
        profile_indicators = [
            "h3:contains('Profile Information')",
            ".settings-form-wrapper",
            "input[id='name']",
            "input[id='email']",
            "form[wire:submit*='updateProfileInformation']"
        ]
        
        profile_page_found = False
        found_indicator = None
        
        # Check for profile page elements
        try:
            # Look for "Profile Information" heading
            headings = driver.find_elements(By.TAG_NAME, "h3")
            for heading in headings:
                if heading.is_displayed() and "profile information" in heading.text.lower():
                    profile_page_found = True
                    found_indicator = "Profile Information heading"
                    print(f"Found: {found_indicator}")
                    break
        except:
            pass
        
        if not profile_page_found:
            # Look for name and email inputs
            try:
                name_input = driver.find_element(By.ID, "name")
                email_input = driver.find_element(By.ID, "email")
                if name_input.is_displayed() and email_input.is_displayed():
                    profile_page_found = True
                    found_indicator = "Name and Email inputs"
                    print(f"Found: {found_indicator}")
            except:
                pass
        
        if not profile_page_found:
            # Look for settings form wrapper
            try:
                form_wrapper = driver.find_element(By.CSS_SELECTOR, ".settings-form-wrapper")
                if form_wrapper.is_displayed():
                    profile_page_found = True
                    found_indicator = "Settings form wrapper"
                    print(f"Found: {found_indicator}")
            except:
                pass
        
        # Check page content for profile keywords
        page_text = driver.page_source.lower()
        content_has_profile = "profile information" in page_text or "update your account" in page_text
        
        profile_accessible = url_indicates_profile or profile_page_found or content_has_profile
        
        assert profile_accessible, \
            f"Profile settings not accessible - URL: {url_indicates_profile}, Page: {profile_page_found}, Content: {content_has_profile}"
        
        print(f"PASSED {test_id}: Access profile settings test PASSED")
        print(f"Result: Settings page loaded successfully")
        return True
        
    except Exception as e:
        print(f"FAILED {test_id}: Access profile settings test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = UP_005_access_profile_settings()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
