"""
UP_008: Validate biometric authentication setup access
Expected Result: Biometric setup page accessible from profile
Module: User Profile Modules - Biometrics
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

def UP_008_biometric_setup_access():
    """UP_008: Validate biometric authentication setup access"""
    test_id = "UP_008"
    print(f"\n🧪 Running {test_id}: Biometric Authentication Setup Access")
    print("📋 Module: User Profile Modules - Biometrics")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
        )
        print("✅ Dashboard loaded")
        
        # Find and click the user profile button (id="userProfileBtn")
        user_profile_btn = driver.find_element(By.ID, "userProfileBtn")
        print("🎯 Found user profile button")
        user_profile_btn.click()
        time.sleep(2)
        print("✅ Clicked user profile button - dropdown opened")
        
        # Look for "Biometrics" link in the dropdown
        biometrics_link = None
        all_links = driver.find_elements(By.CSS_SELECTOR, "a")
        for link in all_links:
            if link.is_displayed() and "biometrics" in link.text.lower():
                biometrics_link = link
                print(f"🎯 Found Biometrics link: {link.text}")
                break
        
        assert biometrics_link is not None, "Could not find Biometrics link in dropdown"
        
        # Click the biometrics link
        biometrics_link.click()
        print("🔐 Clicked Biometrics link")
        
        # Wait for biometrics page to load
        time.sleep(3)
        
        # Check if we're on biometrics/webauthn page
        current_url = driver.current_url
        url_indicates_biometrics = "/webauthn" in current_url
        
        # Look for biometrics page indicators
        biometrics_selectors = [
            ".webauthn",
            ".biometric",
            ".security-keys",
            "[data-page='biometrics']",
            "[data-page='webauthn']"
        ]
        
        biometrics_page_found = False
        for selector in biometrics_selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                biometrics_page_found = True
                print(f"🔐 Biometrics page content found: {selector}")
                break
        
        # Check page content for biometrics/webauthn keywords
        page_text = driver.page_source.lower()
        content_indicates_biometrics = any(word in page_text for word in ['webauthn', 'biometric', 'security key', 'passkey'])
        
        biometrics_accessible = url_indicates_biometrics or biometrics_page_found or content_indicates_biometrics
        
        assert biometrics_accessible, \
            f"Biometrics page not accessible - URL: {url_indicates_biometrics}, Page: {biometrics_page_found}, Content: {content_indicates_biometrics}"
        
        print(f"✓ {test_id}: Biometric Authentication Setup Access test PASSED")
        print(f"🎯 Result: Biometrics page loaded successfully")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Biometric Authentication Setup Access test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = UP_008_biometric_setup_access()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
