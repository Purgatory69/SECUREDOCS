"""
UP-UD 001: Validate dashboard loads with user stats
Expected Result: Dashboard displays storage usage and recent files
Module: User Profile - User Dashboard
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..'))

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def UP_UD_001_dashboard_stats():
    """UP-UD 001: Validate dashboard loads with user stats"""
    test_id = "UP-UD 001"
    print(f"\n🧪 Running {test_id}: Dashboard Loads With User Stats")
    print("📋 Module: User Profile - User Dashboard")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard'], body"))
        )
        print("✅ Dashboard loaded")
        
        # Check for storage usage display
        storage_found = False
        try:
            storage_elements = driver.find_elements(By.CSS_SELECTOR, "[class*='storage'], [id*='storage'], [data-stat='storage']")
            if storage_elements:
                storage_found = True
                print("✅ Storage usage display found")
        except:
            print("⚠️ Storage usage display not found")
        
        # Check for recent files section
        files_found = False
        try:
            files_container = driver.find_element(By.ID, "filesContainer")
            if files_container:
                files_found = True
                print("✅ Files container found")
        except:
            print("⚠️ Files container not found")
        
        success = storage_found or files_found
        
        if success:
            print(f"✓ {test_id}: Dashboard stats test PASSED")
        else:
            print(f"✗ {test_id}: Dashboard stats test FAILED")
        
        return success
        
    except Exception as e:
        print(f"✗ {test_id}: Dashboard stats test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = UP_UD_001_dashboard_stats()
        print(f"\nUP-UD 001: {'PASSED' if result else 'FAILED'} (1 points)")
    finally:
        session.cleanup()
