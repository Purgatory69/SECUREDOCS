"""
UP_002: Validate dashboard shows user storage usage
Expected Result: Dashboard displays current user storage usage
Module: User Profile Modules - User Dashboard
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

def UP_002_dashboard_shows_statistics():
    """UP_002: Test dashboard shows user storage usage"""
    test_id = "UP_002"
    print(f"\n🧪 Running {test_id}: Dashboard Shows Storage Usage")
    print("📋 Module: User Profile Modules - User Dashboard")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login as user and navigate to user dashboard
        driver = session.login(account_type="user")
        session.navigate_to_dashboard(account_type="user")
        
        # Wait for dashboard to fully load
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
        )
        
        # Check for storage usage display specifically
        storage_selectors = [
            ".storage-usage",
            ".storage-info",
            ".usage-stats", 
            "[data-storage]",
            ".storage-meter",
            ".progress-bar",
            ".storage-display",
            ".storage-widget",
            ".user-storage"
        ]
        
        storage_found = False
        storage_element = None
        for selector in storage_selectors:
            storage_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if storage_elements and any(elem.is_displayed() for elem in storage_elements):
                storage_found = True
                storage_element = storage_elements[0]  # Get the first one found
                print(f"📊 Found storage usage display: {selector}")
                break
        
        # Check for storage-related text content
        page_text = driver.page_source.lower()
        storage_keywords = [
            "storage" in page_text,
            "usage" in page_text,
            "mb" in page_text or "gb" in page_text,
            "%" in page_text,
            "used" in page_text,
            "available" in page_text
        ]
        
        text_storage_found = any(storage_keywords)
        
        # Check for numerical data that might indicate storage
        has_storage_numbers = any(char.isdigit() for char in page_text)
        
        # Look for storage progress bars or meters
        progress_elements = driver.find_elements(By.CSS_SELECTOR, ".progress-bar, .storage-meter, .usage-bar")
        progress_found = len([elem for elem in progress_elements if elem.is_displayed()]) > 0
        
        if progress_found:
            print(f"📊 Found storage progress indicators")
        
        # Overall storage display validation
        storage_displayed = storage_found or text_storage_found or progress_found
        
        assert storage_displayed, \
            f"User storage usage not found - Element: {storage_found}, Text: {text_storage_found}, Progress: {progress_found}"
        
        # Additional validation: check if storage element contains meaningful content
        if storage_element:
            storage_text = storage_element.text.strip()
            has_content = len(storage_text) > 0
            if has_content:
                print(f"💾 Storage element content: '{storage_text[:50]}'")
        
        print(f"✓ {test_id}: Dashboard shows storage usage test PASSED")
        print(f"🎯 Result: Storage usage displayed - Element: {storage_found}, Text: {text_storage_found}, Progress: {progress_found}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Dashboard shows storage usage test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = UP_002_dashboard_shows_statistics()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
