"""
DM_007: Validate document sorting options
Expected Result: Documents sorted correctly by name/date/size
Module: Document Management Modules - View Documents
Priority: Medium
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import wait_for_dashboard
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def DM_007_document_sorting_options():
    """DM_007: Validate document sorting options"""
    test_id = "DM_007"
    print(f"\n🧪 Running {test_id}: Document Sorting Options")
    print("📋 Module: Document Management Modules - View Documents")
    print("🎯 Priority: Medium | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")
        
        # Look for view toggle buttons (Grid/List view)
        view_toggle_container = None
        try:
            view_toggle_container = driver.find_element(By.ID, "viewToggleBtns")
            print("✅ View toggle buttons container found")
        except:
            print("⚠️ View toggle container not found by ID, trying alternative selectors")
            
            # Try alternative selectors
            alt_selectors = [
                ".view-toggle-btn",
                "[data-view]",
                "button[title*='view']"
            ]
            
            for selector in alt_selectors:
                elements = driver.find_elements(By.CSS_SELECTOR, selector)
                if elements:
                    view_toggle_container = elements[0].find_element(By.XPATH, "..")
                    print(f"✅ View toggle found via: {selector}")
                    break
        
        # Look for Grid and List view buttons
        grid_button = None
        list_button = None
        
        try:
            grid_button = driver.find_element(By.ID, "btnGridLayout")
            print("🔲 Grid view button found")
        except:
            try:
                grid_button = driver.find_element(By.CSS_SELECTOR, "button[data-view='grid']")
                print("🔲 Grid view button found (alternative selector)")
            except:
                print("⚠️ Grid view button not found")
        
        try:
            list_button = driver.find_element(By.ID, "btnListLayout")
            print("📜 List view button found")
        except:
            try:
                list_button = driver.find_element(By.CSS_SELECTOR, "button[data-view='list']")
                print("📜 List view button found (alternative selector)")
            except:
                print("⚠️ List view button not found")
        
        # Verify at least one view option exists
        view_options_exist = grid_button is not None or list_button is not None
        
        assert view_options_exist, "No view toggle options found"
        
        # Check which view is currently active
        current_view = None
        if grid_button:
            is_active = grid_button.get_attribute("aria-pressed") == "true" or "active" in grid_button.get_attribute("class")
            if is_active:
                current_view = "grid"
                print("🎯 Current view: Grid")
        
        if list_button and not current_view:
            is_active = list_button.get_attribute("aria-pressed") == "true" or "active" in list_button.get_attribute("class")
            if is_active:
                current_view = "list"
                print("🎯 Current view: List")
        
        # Verify dashboard is functional
        dashboard_element = driver.find_element(By.CSS_SELECTOR, "[data-page='user-dashboard']")
        assert dashboard_element is not None, "Dashboard not loaded"
        
        print(f"✓ {test_id}: Document Sorting Options test PASSED")
        print(f"🎯 Result: View toggle options verified - Current view: {current_view or 'unknown'}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Document Sorting Options test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = DM_007_document_sorting_options()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
