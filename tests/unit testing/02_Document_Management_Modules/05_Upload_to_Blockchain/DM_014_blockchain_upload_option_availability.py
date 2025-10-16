"""
DM_014: Validate blockchain upload option availability
Expected Result: Blockchain upload option visible for premium users
Module: Document Management Modules - Upload to Blockchain
Priority: High
Points: 1
"""

import sys
import os
import time
# Add parent directories to path to import global_session
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    open_upload_modal,
    find_file_input,
    wait_for_upload_complete,
    check_success_message,
    count_files_on_dashboard,
    find_file_by_name
)
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


def DM_014_blockchain_upload_option_availability():
    """DM_014: Test blockchain upload option availability for premium users"""
    test_id = "DM_014"
    print(f"\n🧪 Running {test_id}: Blockchain Upload Option Availability")
    print("📋 Module: Document Management Modules - Upload to Blockchain")
    print("🎯 Priority: High | Points: 1")

    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for dashboard to load using helper
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")

        # Check if user is premium (should be using premium@gmail.com)
        # The Arweave option should be visible for premium users
        dropdown_visible = False
        try:
            dropdown_element = driver.find_element(By.ID, "newDropdown")
            dropdown_visible = dropdown_element.is_displayed()
            if dropdown_visible:
                print("📂 Upload dropdown visible")
        except Exception as dropdown_error:
            print(f"⚠️ Upload dropdown not detected: {str(dropdown_error)}")

        assert dropdown_visible, "Upload dropdown did not appear after clicking New button"

        # Look for Arweave Storage option in dropdown
        arweave_option_selectors = [
            "#openClientArweaveBtn",
            "[id='openClientArweaveBtn']",
            "div:contains('Arweave Storage')",
            ".flex.items-center.px-5.py-4.text-sm:contains('Arweave Storage')"
        ]

        arweave_option_found = False
        arweave_option_visible = False

        for selector in arweave_option_selectors:
            try:
                if 'contains' in selector:
                    # Find by text content
                    dropdown_items = driver.find_elements(By.CSS_SELECTOR, "#newDropdown div")
                    for item in dropdown_items:
                        if item.is_displayed() and 'Arweave Storage' in item.text:
                            arweave_option_found = True
                            arweave_option_visible = item.is_displayed()
                            break
                else:
                    arweave_option = driver.find_element(By.CSS_SELECTOR, selector)
                    arweave_option_found = True
                    arweave_option_visible = arweave_option.is_displayed()
                if arweave_option_found:
                    break
            except:
                continue

        if arweave_option_found:
            print("✅ Arweave Storage option found in dropdown")
            if arweave_option_visible:
                print("✅ Arweave Storage option is visible (Premium user)")
            else:
                print("⚠️ Arweave Storage option found but not visible")
        else:
            print("❌ Arweave Storage option not found in dropdown")

        # Verify premium status by checking for other premium indicators
        premium_indicators = [
            ".bg-gradient-to-r.from-purple-500.to-pink-500",  # Premium badges
            "#openClientArweaveBtn",  # Arweave option itself
        ]

        premium_features_visible = False
        for indicator in premium_indicators:
            try:
                elements = driver.find_elements(By.CSS_SELECTOR, indicator)
                if elements and any(elem.is_displayed() for elem in elements):
                    premium_features_visible = True
                    break
            except:
                continue

        if premium_features_visible:
            print("✅ Premium features detected (user is premium)")
        else:
            print("⚠️ No premium features detected (user may not be premium)")

        # Assert blockchain upload option availability
        # For premium users, Arweave option should be visible
        blockchain_option_available = arweave_option_found and arweave_option_visible

        assert blockchain_option_available, \
            f"Blockchain upload option not available for premium user - Option found: {arweave_option_found}, Option visible: {arweave_option_visible}"

        print(f"✓ {test_id}: Blockchain upload option availability test PASSED")
        print("🎯 Result: Arweave Storage option visible for premium users")
        return True

    except Exception as e:
        print(f"✗ {test_id}: Blockchain upload option availability test FAILED - {str(e)}")
        return False


if __name__ == "__main__":
    try:
        result = DM_014_blockchain_upload_option_availability()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
