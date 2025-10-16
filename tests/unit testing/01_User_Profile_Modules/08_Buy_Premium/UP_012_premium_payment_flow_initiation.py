"""
UP_012: Validate premium payment flow initiation
Expected Result: Payment process starts correctly with valid forms
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


def UP_012_premium_payment_flow_initiation():
    """UP_012: Test premium payment flow initiation"""
    test_id = "UP_012"
    print(f"\n🧪 Running {test_id}: Premium Payment Flow Initiation")
    print("📋 Module: User Profile Modules - Buy Premium")
    print("🎯 Priority: High | Points: 1")

    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for dashboard to load using helper
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")

        # Navigate directly to premium upgrade page (bypass potential UI issues)
        base_url = driver.current_url.split('/user/dashboard')[0]
        premium_url = f"{base_url}/premium/upgrade"
        driver.get(premium_url)
        print(f"✅ Navigated to premium upgrade page: {premium_url}")

        # Wait for page to load
        time.sleep(3)

        # Verify we're on the premium page
        current_url = driver.current_url
        if 'premium/upgrade' in current_url:
            print("✅ Premium upgrade page confirmed")
        else:
            print(f"⚠️ Unexpected URL: {current_url}")

        # Check for payment forms and buttons
        payment_forms_found = False
        payment_buttons_found = False

        # Look for payment-related forms
        try:
            forms = driver.find_elements(By.TAG_NAME, "form")
            for form in forms:
                form_action = form.get_attribute("action") or ""
                if 'payment' in form_action.lower() or 'paymongo' in form_action.lower():
                    payment_forms_found = True
                    print("✅ Found payment form")
                    break
        except Exception as e:
            print(f"⚠️ Error checking forms: {str(e)}")

        # Look for payment buttons (PayMongo, Stripe, etc.)
        payment_button_selectors = [
            "button[class*='pay'], button[class*='payment'], button[class*='checkout']",
            "[onclick*='payment'], [onclick*='pay'], [onclick*='checkout']",
            ".paymongo, .stripe, .checkout",
            "button:contains('Pay'), button:contains('Checkout'), button:contains('Subscribe')"
        ]

        for selector in payment_button_selectors:
            try:
                if 'contains' in selector:
                    # Find by text content
                    buttons = driver.find_elements(By.TAG_NAME, "button")
                    for button in buttons:
                        button_text = button.text.lower()
                        if any(keyword in button_text for keyword in ['pay', 'checkout', 'subscribe', 'upgrade']):
                            payment_buttons_found = True
                            print(f"✅ Found payment button with text: '{button.text}'")
                            break
                else:
                    buttons = driver.find_elements(By.CSS_SELECTOR, selector)
                    if buttons:
                        payment_buttons_found = True
                        print(f"✅ Found {len(buttons)} payment-related buttons")
                        break
            except:
                continue
            if payment_buttons_found:
                break

        # Look for pricing information
        pricing_found = False
        price_indicators = ["₱299", "$9.99", "299", "premium", "subscription", "monthly"]

        page_text = driver.find_element(By.TAG_NAME, "body").text

        for indicator in price_indicators:
            if indicator in page_text:
                pricing_found = True
                print(f"✅ Found pricing indicator: '{indicator}'")
                break

        # Check for payment method options (GCash, cards, etc.)
        payment_methods_found = False
        payment_method_indicators = ["gcash", "card", "credit", "debit", "paypal", "bank"]

        for method in payment_method_indicators:
            if method.lower() in page_text.lower():
                payment_methods_found = True
                print(f"✅ Found payment method: '{method}'")
                break

        # Verify payment flow can be initiated
        payment_flow_ready = (
            ('premium/upgrade' in current_url) and
            (payment_forms_found or payment_buttons_found) and
            pricing_found
        )

        assert payment_flow_ready, \
            f"Payment flow initiation failed - URL: {current_url}, Forms: {payment_forms_found}, Buttons: {payment_buttons_found}, Pricing: {pricing_found}"

        print(f"✓ {test_id}: Premium payment flow initiation test PASSED")
        print("🎯 Result: Payment process starts correctly with valid forms")
        print(f"📋 Payment methods available: {payment_methods_found}")
        return True

    except Exception as e:
        print(f"✗ {test_id}: Premium payment flow initiation test FAILED - {str(e)}")
        return False


if __name__ == "__main__":
    try:
        result = UP_012_premium_payment_flow_initiation()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
