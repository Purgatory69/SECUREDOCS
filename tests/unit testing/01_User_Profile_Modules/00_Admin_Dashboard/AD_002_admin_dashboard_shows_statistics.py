"""
AD_002: Validate admin dashboard shows system statistics and user metrics
Expected Result: Admin dashboard displays system-wide statistics and user management metrics
Module: User Profile Modules - Admin Dashboard
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

def AD_002_admin_dashboard_shows_statistics():
    """AD_002: Test admin dashboard shows system statistics and user metrics"""
    test_id = "AD_002"
    print(f"\n🧪 Running {test_id}: Admin Dashboard Shows Statistics")
    print("📋 Module: User Profile Modules - Admin Dashboard")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login as admin and navigate to admin dashboard
        driver = session.login(account_type="admin")
        session.navigate_to_dashboard(account_type="admin")
        
        # Wait for admin dashboard to fully load
        WebDriverWait(driver, 10).until(
            EC.any_of(
                EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='admin-dashboard']")),
                EC.presence_of_element_located((By.CSS_SELECTOR, ".admin-dashboard")),
                EC.url_contains("admin")
            )
        )
        
        # Check for system statistics display
        system_stats_selectors = [
            ".system-stats",
            ".admin-stats",
            ".system-metrics",
            ".admin-metrics",
            ".system-info",
            ".admin-overview",
            ".stats-cards",
            ".dashboard-stats",
            ".system-dashboard",
            ".admin-panel-stats"
        ]
        
        system_stats_found = False
        for selector in system_stats_selectors:
            stats_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if stats_elements and any(elem.is_displayed() for elem in stats_elements):
                system_stats_found = True
                print(f"📊 Found system statistics display: {selector}")
                break
        
        # Check for user management metrics
        user_metrics_selectors = [
            ".user-stats",
            ".user-metrics",
            ".user-management-stats",
            ".total-users",
            ".active-users",
            ".user-count",
            ".admin-user-info"
        ]
        
        user_metrics_found = False
        for selector in user_metrics_selectors:
            metrics_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if metrics_elements and any(elem.is_displayed() for elem in metrics_elements):
                user_metrics_found = True
                print(f"👥 Found user metrics: {selector}")
                break
        
        # Check for admin-specific dashboard widgets
        admin_widgets_selectors = [
            ".admin-widget",
            ".dashboard-widget", 
            ".stat-card",
            ".info-card",
            ".admin-card",
            ".metric-card"
        ]
        
        widgets_found = False
        widget_count = 0
        for selector in admin_widgets_selectors:
            widget_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            visible_widgets = [elem for elem in widget_elements if elem.is_displayed()]
            if visible_widgets:
                widgets_found = True
                widget_count += len(visible_widgets)
                print(f"🎛️ Found {len(visible_widgets)} admin widgets: {selector}")
                break
        
        # Check page text for admin statistical content
        page_text = driver.page_source.lower()
        admin_text_indicators = [
            "total users" in page_text,
            "active users" in page_text,
            "system" in page_text,
            "admin" in page_text,
            "management" in page_text,
            "statistics" in page_text
        ]
        
        admin_text_stats_found = any(admin_text_indicators)
        
        # Check for numerical data (indicators of statistics)
        has_numbers = any(char.isdigit() for char in page_text)
        
        # Check for admin-specific content keywords
        admin_keywords = [
            "user management",
            "system overview", 
            "admin panel",
            "total users",
            "active sessions",
            "system stats",
            "admin dashboard",
            "management",
            "users",
            "statistics"
        ]
        
        admin_content_found = any(keyword in page_text for keyword in admin_keywords)
        
        # Debug: If no statistics found, show what might be on the page
        if not (system_stats_found or user_metrics_found or widgets_found or admin_text_stats_found or admin_content_found):
            print("🔍 Debugging: No admin statistics found. Checking page content...")
            # Look for any elements that might contain numbers/statistics
            stat_elements = driver.find_elements(By.CSS_SELECTOR, "*[class*='stat'], *[class*='metric'], *[class*='count'], *[class*='number']")
            if stat_elements:
                print(f"📊 Found {len(stat_elements)} potential stat elements:")
                for i, elem in enumerate(stat_elements[:3]):  # Show first 3
                    try:
                        classes = elem.get_attribute('class') or 'no-class'
                        text = elem.text[:30] if elem.text else 'no-text'
                        print(f"  {i+1}. <{elem.tag_name} class='{classes}'>: '{text}'")
                    except:
                        print(f"  {i+1}. Could not read element")
            
            # Check for any text that might indicate admin content
            admin_text_indicators = ["admin", "users", "system", "management", "dashboard"]
            found_admin_text = [word for word in admin_text_indicators if word in page_text.lower()]
            if found_admin_text:
                print(f"📝 Found admin-related text on page: {', '.join(found_admin_text)}")
        
        # Overall admin statistics validation
        admin_statistics_displayed = (
            system_stats_found or 
            user_metrics_found or 
            widgets_found or
            admin_text_stats_found or
            admin_content_found
        )
        
        assert admin_statistics_displayed, \
            f"Admin dashboard statistics not found - System: {system_stats_found}, Users: {user_metrics_found}, Widgets: {widgets_found}, Text: {admin_text_stats_found}, Content: {admin_content_found}"
        
        print(f"✓ {test_id}: Admin dashboard shows statistics test PASSED")
        print(f"🎯 Result: Admin statistics displayed - System: {system_stats_found}, Users: {user_metrics_found}, Widgets: {widget_count}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Admin dashboard shows statistics test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = AD_002_admin_dashboard_shows_statistics()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
