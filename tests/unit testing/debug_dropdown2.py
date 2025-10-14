"""
Debug dropdown visibility after clicking
"""

import sys
import os
sys.path.append(os.path.dirname(__file__))

from global_session import session
from selenium.webdriver.common.by import By
import time

try:
    driver = session.login()
    session.navigate_to_dashboard()
    time.sleep(2)
    
    print("✅ Dashboard loaded")
    
    # Click user profile button
    btn = driver.find_element(By.ID, "userProfileBtn")
    btn.click()
    print("✅ Clicked userProfileBtn")
    time.sleep(4)  # Wait longer
    
    # Check ALL elements for visibility
    all_elements = driver.find_elements(By.CSS_SELECTOR, "*")
    print(f"\n📊 Total elements on page: {len(all_elements)}")
    
    # Look for any element containing "Profile Settings"
    profile_elements = []
    for elem in all_elements:
        try:
            text = elem.text
            if text and ("profile" in text.lower() or "biometric" in text.lower()):
                tag = elem.tag_name
                visible = elem.is_displayed()
                classes = elem.get_attribute("class") or ""
                profile_elements.append((tag, text[:30], visible, classes[:30]))
        except:
            continue
    
    print(f"\n🔍 Elements with 'profile' or 'biometric' text: {len(profile_elements)}")
    for i, (tag, text, visible, classes) in enumerate(profile_elements[:10]):
        print(f"  {i+1}. <{tag}> '{text}' visible:{visible} class:'{classes}'")
    
    # Check for dropdown div
    dropdowns = driver.find_elements(By.CSS_SELECTOR, "[id*='dropdown'], [class*='dropdown'], [role='menu']")
    print(f"\n📋 Dropdown elements found: {len(dropdowns)}")
    for i, dd in enumerate(dropdowns):
        visible = dd.is_displayed()
        dd_id = dd.get_attribute("id") or "no-id"
        dd_class = dd.get_attribute("class") or "no-class"
        print(f"  {i+1}. ID:'{dd_id}' Class:'{dd_class}' Visible:{visible}")
        
finally:
    session.cleanup()
