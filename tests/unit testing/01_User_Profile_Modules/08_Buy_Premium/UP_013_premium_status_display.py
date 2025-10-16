"""
UP_013: Validate premium status display after purchase
Expected Result: Premium status reflected in user interface
Module: User Profile Modules - Buy Premium
Priority: Medium
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


def UP_013_premium_status_display():
    """UP_013: Test premium status display in user interface"""
    test_id = "UP_013"
    print(f"\n🧪 Running {test_id}: Premium Status Display")
    print("📋 Module: User Profile Modules - Buy Premium")
    print("🎯 Priority: Medium | Points: 1")

    try:
        # Login and navigate to dashboard (using premium account)
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for dashboard to load using helper
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")

        # Check for premium status indicators throughout the UI
        premium_indicators_found = 0
        total_indicators_checked = 0

        # 1. Check sidebar premium badge (crown icon)
        total_indicators_checked += 1
        try:
            crown_icon = driver.find_element(By.CSS_SELECTOR, "#blockchain-storage-link .crown, #blockchain-storage-link img[alt*='Premium'], #blockchain-storage-link [class*='premium']")
            if crown_icon.is_displayed():
                premium_indicators_found += 1
                print("✅ Found premium crown icon in sidebar")
            else:
                print("⚠️ Crown icon exists but not visible")
        except:
            print("❌ Crown icon not found in sidebar")

        # 2. Check for "PREMIUM" badges on various elements
        total_indicators_checked += 1
        try:
            premium_badges = driver.find_elements(By.CSS_SELECTOR, "[class*='premium'], .badge-premium, [class*='PREMIUM']")
            visible_badges = [badge for badge in premium_badges if badge.is_displayed()]
            if visible_badges:
                premium_indicators_found += 1
                print(f"✅ Found {len(visible_badges)} premium badges")
            else:
                print("⚠️ Premium badges exist but none visible")
        except:
            print("❌ Premium badges not found")

        # 3. Check upload modal for premium options (AI Vectorize)
        total_indicators_checked += 1
        try:
            # Open upload modal
            upload_modal_opened = open_upload_modal(driver)
            if upload_modal_opened:
                time.sleep(1)
                # Check if AI Vectorize option is enabled (not disabled/opacity-60)
                ai_option = driver.find_element(By.ID, "vectorizeUpload")
                if ai_option.is_enabled() and not ai_option.get_attribute("disabled"):
                    premium_indicators_found += 1
                    print("✅ AI Vectorize option enabled (premium feature)")
                else:
                    print("⚠️ AI Vectorize option disabled")
            else:
                print("⚠️ Could not open upload modal to check premium options")
        except Exception as e:
            print(f"⚠️ Error checking upload modal: {str(e)}")

        # 4. Check profile/settings area for premium indicators
        total_indicators_checked += 1
        try:
            # Look for premium status in profile or settings areas
            profile_elements = driver.find_elements(By.CSS_SELECTOR, ".profile-status, .user-status, [data-premium], [class*='premium']")
            premium_profile_elements = []
            for elem in profile_elements:
                text = elem.text.lower()
                if any(keyword in text for keyword in ['premium', 'pro', 'paid', 'subscription']):
                    premium_profile_elements.append(elem)

            if premium_profile_elements:
                premium_indicators_found += 1
                print(f"✅ Found premium status in profile area")
            else:
                print("⚠️ No premium status indicators in profile area")
        except Exception as e:
            print(f"⚠️ Error checking profile area: {str(e)}")

        # 5. Check for unlimited storage indicators
        total_indicators_checked += 1
        try:
            storage_text = driver.find_element(By.ID, "storageUsageText").text
            if any(keyword in storage_text.lower() for keyword in ['unlimited', 'premium', 'no limit']):
                premium_indicators_found += 1
                print("✅ Found unlimited storage indicator")
            else:
                print("⚠️ No unlimited storage indicator found")
        except:
            print("⚠️ Could not check storage usage text")

        # Calculate premium status visibility
        premium_visibility_ratio = premium_indicators_found / total_indicators_checked if total_indicators_checked > 0 else 0

        print(f"📊 Premium indicators: {premium_indicators_found}/{total_indicators_checked} found ({premium_visibility_ratio:.1%})")

        # Premium status should be clearly visible (at least 60% of indicators)
        premium_status_visible = premium_visibility_ratio >= 0.6

        assert premium_status_visible, \
            f"Premium status not sufficiently visible - Found {premium_indicators_found}/{total_indicators_checked} indicators"

        print(f"✓ {test_id}: Premium status display test PASSED")
        print("🎯 Result: Premium status reflected in user interface")
        return True

    except Exception as e:
        print(f"✗ {test_id}: Premium status display test FAILED - {str(e)}")
        return False


if __name__ == "__main__":
    try:
        result = UP_013_premium_status_display()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
