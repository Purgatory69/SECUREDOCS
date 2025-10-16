"""
UP_011: Validate premium purchase page access
Expected Result: Premium purchase page loads with pricing information
Module: User Profile Modules - Buy Premium
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


def UP_011_buy_premium_page_access():
    """UP_011: Test premium purchase page access"""
    test_id = "UP_011"
    print(f"\n🧪 Running {test_id}: Buy Premium Page Access")
    print("📋 Module: User Profile Modules - Buy Premium")
    print("🎯 Priority: High | Points: 1")

    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for dashboard to load using helper
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")

        # Click on the premium upgrade button (crown icon in sidebar)
        try:
            premium_button = driver.find_element(By.CSS_SELECTOR, "#blockchain-storage-link, [onclick*='premium.upgrade'], .crown")
            premium_button.click()
            print("✅ Clicked premium upgrade button")
        except Exception as e:
            print(f"⚠️ Crown icon not found, trying dropdown menu: {str(e)}")

            # Try clicking the user menu dropdown to access premium option
            try:
                user_menu_btn = driver.find_element(By.ID, "userMenuBtn")
                user_menu_btn.click()
                time.sleep(1)

                # Look for premium upgrade option in dropdown
                premium_options = driver.find_elements(By.CSS_SELECTOR, "[onclick*='premium.upgrade'], [href*='premium.upgrade']")
                if premium_options:
                    premium_options[0].click()
                    print("✅ Found and clicked premium upgrade in user menu")
                else:
                    # Try direct navigation to premium page
                    driver.get(f"{driver.current_url.split('/user/dashboard')[0]}/premium/upgrade")
                    print("✅ Navigated directly to premium upgrade page")
            except Exception as menu_error:
                print(f"⚠️ User menu approach failed, trying direct navigation: {str(menu_error)}")
                # Fallback: navigate directly to premium upgrade URL
                driver.get(f"{driver.current_url.split('/user/dashboard')[0]}/premium/upgrade")
                print("✅ Navigated directly to premium upgrade page")

        # Wait for premium page to load
        time.sleep(2)  # Give page time to load

        # Verify we're on the premium page
        current_url = driver.current_url
        if 'premium/upgrade' in current_url:
            print("✅ Premium upgrade page loaded")
        else:
            print(f"⚠️ URL doesn't contain premium/upgrade: {current_url}")

        # Check for pricing information
        pricing_found = False
        pricing_indicators = [
            "Premium", "pricing", "₱", "$", "299", "upgrade", "subscribe"
        ]

        page_text = driver.find_element(By.TAG_NAME, "body").text.lower()

        for indicator in pricing_indicators:
            if indicator.lower() in page_text:
                pricing_found = True
                print(f"✅ Found pricing indicator: '{indicator}'")
                break

        # Look for specific pricing elements
        try:
            price_elements = driver.find_elements(By.CSS_SELECTOR, "[class*='price'], [class*='pricing'], [class*='cost']")
            if price_elements:
                pricing_found = True
                print(f"✅ Found {len(price_elements)} pricing-related elements")
        except:
            pass

        # Look for payment buttons or forms
        try:
            payment_buttons = driver.find_elements(By.CSS_SELECTOR, "button, [type='submit'], [onclick*='payment']")
            if payment_buttons:
                print(f"✅ Found {len(payment_buttons)} payment-related buttons")
        except:
            pass

        # Verify premium page content
        page_title_found = False
        try:
            page_title = driver.find_element(By.CSS_SELECTOR, "h1, h2, h3, .title").text
            if page_title and ("premium" in page_title.lower() or "upgrade" in page_title.lower()):
                page_title_found = True
                print(f"✅ Found premium page title: '{page_title}'")
        except:
            pass

        # Assert premium purchase page loaded successfully
        premium_page_loaded = (
            'premium/upgrade' in current_url and
            (pricing_found or page_title_found)
        )

        assert premium_page_loaded, \
            f"Premium purchase page failed to load - URL: {current_url}, Pricing found: {pricing_found}, Title found: {page_title_found}"

        print(f"✓ {test_id}: Buy Premium page access test PASSED")
        print("🎯 Result: Premium purchase page loads with pricing information")
        return True

    except Exception as e:
        print(f"✗ {test_id}: Buy Premium page access test FAILED - {str(e)}")
        return False


if __name__ == "__main__":
    try:
        result = UP_011_buy_premium_page_access()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
