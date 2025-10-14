"""
Debug what happens when clicking Profile Settings link
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

    # Find and click Profile Settings link
    all_links = driver.find_elements(By.CSS_SELECTOR, "a")
    profile_link = None
    for link in all_links:
        if link.is_displayed() and "profile settings" in link.text.lower():
            profile_link = link
            print(f"🎯 Found Profile Settings link: {link.text}")
            break

    if profile_link:
        # Check URL before clicking
        before_url = driver.current_url
        print(f"🌐 URL before click: {before_url}")
        
        # Click the link
        profile_link.click()
        time.sleep(3)
        print("⚙️ Clicked Profile Settings link")
        
        # Check URL after clicking
        after_url = driver.current_url
        print(f"🌐 URL after click: {after_url}")
        
        # Check page content
        page_title = driver.title
        print(f"📄 Page title: '{page_title}'")
        
        # Check for common elements
        body_text = driver.find_element(By.TAG_NAME, "body").text[:200]
        print(f"📝 Page content preview: '{body_text}'")
        
        # Check for modal overlays
        modals = driver.find_elements(By.CSS_SELECTOR, ".modal, .overlay, .popup, [role='dialog']")
        print(f"🔍 Modals found: {len(modals)}")
        
        for i, modal in enumerate(modals):
            if modal.is_displayed():
                modal_text = modal.text[:100] if modal.text else "no text"
                print(f"  Modal {i+1}: '{modal_text}'")
        
        # Check for profile-related content in visible elements
        profile_elements = driver.find_elements(By.CSS_SELECTOR, 
            ".profile-settings, .settings-form, input[name='name'], input[name='email'], .user-settings")
        print(f"👤 Profile elements found: {len(profile_elements)}")
        
        visible_profile_elements = []
        for elem in profile_elements:
            if elem.is_displayed():
                tag = elem.tag_name
                name = elem.get_attribute("name") or ""
                class_attr = elem.get_attribute("class") or ""
                visible_profile_elements.append(f"{tag}[{name}].{class_attr}")
        
        if visible_profile_elements:
            print(f"  Visible profile elements: {', '.join(visible_profile_elements[:5])}")
        
        # Check if dropdown is still open
        dropdown_items = driver.find_elements(By.CSS_SELECTOR, "a[href*='profile'], a[href*='webauthn']")
        print(f"📋 Dropdown links still visible: {len(dropdown_items)}")
        
        for link in dropdown_items:
            if link.is_displayed():
                text = link.text
                href = link.get_attribute("href")
                print(f"  '{text}' -> {href}")
                
        # Check page state
        if len(modals) > 0 and any(m.is_displayed() for m in modals):
            print("✅ Modal opened - Profile Settings likely in modal")
        elif len(visible_profile_elements) > 0:
            print("✅ Profile elements visible - inline settings")
        elif len(dropdown_items) > 0:
            print("⚠️ Dropdown still open - link may not have worked")
        else:
            print("❓ Unclear state - need more investigation")
            
    else:
        print("❌ Profile Settings link not found")

finally:
    session.cleanup()
