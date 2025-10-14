"""
Debug dropdown menu links after clicking userProfileBtn
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

    # Click the user profile button
    user_profile_btn = driver.find_element(By.ID, "userProfileBtn")
    user_profile_btn.click()
    time.sleep(2)
    print("✅ Clicked userProfileBtn")

    # Check all links on the page
    all_links = driver.find_elements(By.CSS_SELECTOR, "a")
    print(f"Found {len(all_links)} total links")

    visible_links = []
    for link in all_links:
        if link.is_displayed():
            text = link.text.strip()
            href = link.get_attribute("href") or ""
            if text:  # Only show links with text
                visible_links.append((text, href))

    print(f"Visible links with text: {len(visible_links)}")
    for i, (text, href) in enumerate(visible_links):
        print(f"  {i+1}. '{text}' -> {href}")

    # Look specifically for dropdown menu items
    dropdown_items = driver.find_elements(By.CSS_SELECTOR, ".dropdown-item, .dropdown-menu a")
    print(f"\nDropdown menu items: {len(dropdown_items)}")
    for i, item in enumerate(dropdown_items):
        if item.is_displayed():
            text = item.text.strip()
            href = item.get_attribute("href") or ""
            print(f"  {i+1}. '{text}' -> {href}")

finally:
    session.cleanup()
