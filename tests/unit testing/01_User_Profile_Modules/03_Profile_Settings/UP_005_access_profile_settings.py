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
    print(f"\n🧪 Running {test_id}: Access Profile Settings")
    print("📋 Module: User Profile Modules - Profile Settings")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Look for profile/settings link
        settings_selectors = [
            "a[href*='profile']",
            "a[href*='settings']", 
            ".profile-link",
            ".settings-link",
            "[data-action='profile']",
            "[data-action='settings']"
        ]
        
        settings_link = None
        
        # First try direct links
        for selector in settings_selectors:
            try:
                settings_link = driver.find_element(By.CSS_SELECTOR, selector)
                if settings_link.is_displayed():
                    break
            except:
                continue
        
        # If no direct link, try user dropdown menu
        if settings_link is None:
            try:
                user_menu = driver.find_element(By.CSS_SELECTOR, ".dropdown-toggle, .user-menu, .user-dropdown")
                user_menu.click()
                time.sleep(1)
                
                for selector in settings_selectors:
                    try:
                        settings_link = driver.find_element(By.CSS_SELECTOR, selector)
                        if settings_link.is_displayed():
                            break
                    except:
                        continue
            except:
                pass
        
        # Try navigation menu
        if settings_link is None:
            nav_links = driver.find_elements(By.CSS_SELECTOR, "nav a, .navbar a, .navigation a")
            for link in nav_links:
                if any(text in link.text.lower() for text in ['profile', 'settings', 'account']):
                    settings_link = link
                    break
        
        assert settings_link is not None, "Could not find profile/settings link"
        
        # Click the settings link
        settings_link.click()
        print("⚙️ Clicked profile/settings link")
        
        # Wait for settings page to load
        time.sleep(3)
        
        # Check if we're on settings/profile page
        current_url = driver.current_url
        url_indicates_settings = any(word in current_url for word in ['profile', 'settings', 'account'])
        
        # Look for settings page indicators
        settings_page_selectors = [
            ".profile-settings",
            ".user-settings",
            ".settings-form",
            ".profile-form",
            ".account-settings",
            "form[action*='profile']",
            "form[action*='settings']"
        ]
        
        settings_page_found = False
        for selector in settings_page_selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                settings_page_found = True
                print(f"⚙️ Settings page content found: {selector}")
                break
        
        # Look for user information fields
        user_info_selectors = [
            "input[name='name']",
            "input[name='email']",
            ".user-name",
            ".user-email",
            ".profile-info"
        ]
        
        user_info_found = False
        for selector in user_info_selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                user_info_found = True
                print(f"👤 User information found: {selector}")
                break
        
        # Check page title or heading
        page_text = driver.page_source.lower()
        title_indicates_settings = any(word in page_text for word in ['profile', 'settings', 'account'])
        
        settings_accessible = url_indicates_settings or settings_page_found or user_info_found
        
        assert settings_accessible, \
            f"Profile settings not accessible - URL: {url_indicates_settings}, Page: {settings_page_found}, Info: {user_info_found}"
        
        print(f"✓ {test_id}: Access profile settings test PASSED")
        print(f"🎯 Result: Settings page loaded successfully")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Access profile settings test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = UP_005_access_profile_settings()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
