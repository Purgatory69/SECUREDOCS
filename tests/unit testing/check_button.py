"""
Quick test to check if userProfileBtn exists
"""

import sys
import os
sys.path.append(os.path.dirname(__file__))

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

try:
    driver = session.login()
    session.navigate_to_dashboard()

    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
    )
    print("✅ Dashboard loaded")

    # Check if userProfileBtn exists
    try:
        btn = driver.find_element(By.ID, "userProfileBtn")
        print("✅ userProfileBtn found!")
        print(f"Text: '{btn.text}'")
        print(f"Visible: {btn.is_displayed()}")
        print(f"Enabled: {btn.is_enabled()}")
    except Exception as e:
        print(f"❌ userProfileBtn not found: {str(e)}")

        # Show all buttons
        buttons = driver.find_elements(By.CSS_SELECTOR, "button")
        print(f"Found {len(buttons)} buttons:")
        for i, btn in enumerate(buttons):
            if i < 10:  # Show first 10
                try:
                    btn_id = btn.get_attribute("id") or ""
                    btn_class = btn.get_attribute("class") or ""
                    btn_text = btn.text or ""
                    visible = btn.is_displayed()
                    print(f"  {i+1}. ID:'{btn_id}' Class:'{btn_class}' Text:'{btn_text}' Visible:{visible}")
                except:
                    print(f"  {i+1}. Could not read button info")

finally:
    session.cleanup()
