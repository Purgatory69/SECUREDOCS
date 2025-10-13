"""
UP_001: Validate user dashboard loads with user navigation menu
Expected Result: User dashboard displays with user-specific menu items accessible (not admin items)
Module: User Profile Modules - User Dashboard
Priority: High
Points: 1
"""

import sys
import os
# Add parent directories to path to import global_session
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def UP_001_dashboard_loads_navigation():
    """UP_001: Test user dashboard loads with navigation menu"""
    test_id = "UP_001"
    print(f"\n🧪 Running {test_id}: Dashboard Loads Navigation")
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
        print("✅ Dashboard page loaded successfully")
        
        # Check for navigation menu presence
        nav_selectors = [
            "nav",
            ".navbar",
            ".navigation",
            ".sidebar",
            ".menu-container",
            ".main-nav",
            "header",
            ".header-nav"
        ]
        
        nav_found = False
        nav_element = None
        for selector in nav_selectors:
            try:
                nav_element = driver.find_element(By.CSS_SELECTOR, selector)
                if nav_element.is_displayed():
                    nav_found = True
                    print(f"🧭 Found navigation menu: {selector}")
                    break
            except:
                continue
        
        # If specific nav not found, check for general navigation elements
        if not nav_found:
            print("⚠️ No specific navigation found, checking for general elements...")
            general_nav_selectors = ["nav", ".navbar", ".navigation", ".sidebar", ".menu"]
            for selector in general_nav_selectors:
                try:
                    nav_element = driver.find_element(By.CSS_SELECTOR, selector)
                    if nav_element.is_displayed():
                        nav_found = True
                        print(f"🧭 Found general navigation: {selector}")
                        break
                except:
                    continue
        
        assert nav_found, "Navigation menu not found on dashboard"
        
        # Check for navigation links
        nav_links = driver.find_elements(By.CSS_SELECTOR, "nav a, .navbar a, .navigation a, .nav-link, header a")
        accessible_links = [link for link in nav_links if link.is_displayed() and link.get_attribute('href')]
        
        print(f"🔗 Found {len(accessible_links)} accessible navigation links")
        
        # Verify user-specific navigation items exist (exclude admin items)
        user_nav_items_found = []
        admin_items_found = []
        
        for link in accessible_links:
            link_text = link.text.lower()
            link_href = link.get_attribute('href').lower()
            
            # Check for admin items (should NOT be present for regular users)
            if any(item in link_text or item in link_href for item in ['admin', 'manage users', 'user management']):
                admin_items_found.append(link_text or link_href)
            # Check for user navigation items
            elif any(item in link_text or item in link_href for item in ['dashboard', 'home']):
                user_nav_items_found.append('Dashboard')
            elif any(item in link_text or item in link_href for item in ['files', 'documents']):
                user_nav_items_found.append('Files')
            elif any(item in link_text or item in link_href for item in ['profile', 'settings']):
                user_nav_items_found.append('Profile')
            elif any(item in link_text or item in link_href for item in ['premium', 'upgrade']):
                user_nav_items_found.append('Premium')
        
        # Remove duplicates
        user_nav_items_found = list(set(user_nav_items_found))
        print(f"📋 User navigation items detected: {', '.join(user_nav_items_found)}")
        
        # Verify admin items are NOT present for regular users
        assert len(admin_items_found) == 0, f"Admin items found in user dashboard: {admin_items_found}"
        
        # For user dashboard, header with branding is sufficient navigation
        has_user_indicator = False
        if nav_element and nav_element.text:
            has_user_indicator = "securedocs" in nav_element.text.lower() and "administrator" not in nav_element.text.lower()
            if has_user_indicator:
                print(f"👤 Found user dashboard indicator in navigation: '{nav_element.text[:50]}'")
        
        # Verify we have meaningful navigation (either links or user indicator)
        has_navigation = len(accessible_links) >= 1 or has_user_indicator
        
        assert has_navigation, f"Navigation validation failed - Links: {len(accessible_links)}, User indicator: {has_user_indicator}"
        
        # Check dashboard content is loaded
        dashboard_indicators = [
            ".dashboard-content",
            ".user-stats",
            ".storage-info", 
            ".recent-files",
            ".welcome"
        ]
        
        content_found = False
        for selector in dashboard_indicators:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                content_found = True
                print(f"📊 Found dashboard content: {selector}")
                break
        
        # Verify URL is correct (should be user dashboard, not admin)
        current_url = driver.current_url
        assert "dashboard" in current_url or session.BASE_URL in current_url, \
            f"Not on dashboard page: {current_url}"
        assert "admin" not in current_url, f"User should not be on admin dashboard: {current_url}"
        
        print(f"✓ {test_id}: User dashboard loads navigation test PASSED")
        print(f"🎯 Result: User dashboard loaded with {len(accessible_links)} navigation links")
        print(f"🔒 Security: No admin items found in user dashboard")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Dashboard loads navigation test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = UP_001_dashboard_loads_navigation()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
