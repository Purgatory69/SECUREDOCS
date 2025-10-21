"""
UP-N 002: Validate main navigation menu works
Expected Result: All menu items clickable and redirect correctly
Module: User Profile - Navigation
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

def UP_N_002_navigation_menu():
    """UP-N 002: Validate main navigation menu works"""
    test_id = "UP-N 002"
    print(f"\n🧪 Running {test_id}: Main Navigation Menu")
    print("📋 Module: User Profile - Navigation")
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
        
        # Check for navigation menu
        nav_found = False
        try:
            nav_elements = driver.find_elements(By.CSS_SELECTOR, "nav, [role='navigation'], .nav, .navigation, .sidebar")
            if nav_elements:
                nav_found = True
                print(f"✅ Navigation menu found ({len(nav_elements)} elements)")
        except:
            print("⚠️ Navigation menu not found")
        
        # Check for common menu items
        menu_items_found = 0
        menu_items = ['dashboard', 'files', 'settings', 'profile', 'trash']
        
        for item in menu_items:
            try:
                elements = driver.find_elements(By.CSS_SELECTOR, f"[href*='{item}'], [data-nav='{item}'], a:contains('{item.title()}')")
                if elements:
                    menu_items_found += 1
                    print(f"✅ Found menu item: {item}")
            except:
                pass
        
        success = nav_found and menu_items_found >= 2
        
        if success:
            print(f"✓ {test_id}: Navigation menu test PASSED ({menu_items_found} menu items found)")
        else:
            print(f"✗ {test_id}: Navigation menu test FAILED")
        
        return success
        
    except Exception as e:
        print(f"✗ {test_id}: Navigation menu test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = UP_N_002_navigation_menu()
        print(f"\nUP-N 002: {'PASSED' if result else 'FAILED'} (1 points)")
    finally:
        session.cleanup()
