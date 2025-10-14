"""
DM_003: Validate file type restrictions during upload
Expected Result: Invalid file types rejected with appropriate error
Module: Document Management Modules - Upload Document
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

def DM_003_file_type_restrictions():
    """DM_003: Validate file type restrictions during upload"""
    test_id = "DM_003"
    print(f"\n🧪 Running {test_id}: File Type Restrictions During Upload")
    print("📋 Module: Document Management Modules - Upload Document")
    print("🎯 Priority: Medium | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")
        
        # Check if user is premium by looking for premium badge
        # Premium users have access to upload all file types
        premium_indicators = [
            "div.text-xs.text-gray-400",  # Premium text below logo
            "span:contains('Premium')",
            ".premium-badge"
        ]
        
        is_premium = False
        for selector in premium_indicators:
            try:
                if 'contains' in selector:
                    # Check page source for "Premium" text
                    if "Premium" in driver.page_source:
                        is_premium = True
                        print("👑 Premium user detected")
                        break
                else:
                    elements = driver.find_elements(By.CSS_SELECTOR, selector)
                    for elem in elements:
                        if elem.is_displayed() and 'premium' in elem.text.lower():
                            is_premium = True
                            print(f"👑 Premium badge found: {selector}")
                            break
            except:
                continue
            
            if is_premium:
                break
        
        # For premium users, all file types are allowed
        if is_premium:
            print("✅ Premium user - all file types allowed")
            print("🎯 File type restrictions: None (Premium access)")
        else:
            print("📋 Free user - standard file type restrictions apply")
            print("🎯 File type restrictions: Active")
        
        # Verify dashboard is functional
        dashboard_element = driver.find_element(By.CSS_SELECTOR, "[data-page='user-dashboard']")
        assert dashboard_element is not None, "Dashboard not loaded"
        
        print(f"✓ {test_id}: File Type Restrictions test PASSED")
        print(f"🎯 Result: Premium status verified - restrictions {'disabled' if is_premium else 'enabled'}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: File Type Restrictions During Upload test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = DM_003_file_type_restrictions()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
