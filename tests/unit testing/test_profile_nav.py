"""
Test profile navigation directly
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
    print(f"🌐 Current URL: {driver.current_url}")
    
    # Try navigating directly to /user/profile
    print("\n🔗 Navigating directly to /user/profile...")
    driver.get("https://securedocs.live/user/profile")
    time.sleep(3)
    
    print(f"🌐 New URL: {driver.current_url}")
    print(f"📄 Page title: {driver.title}")
    
    # Check for profile elements
    try:
        name_input = driver.find_element(By.ID, "name")
        print(f"✅ Found name input: {name_input.get_attribute('value')}")
    except:
        print("❌ Name input not found")
    
    # Check page content
    if "profile" in driver.page_source.lower():
        print("✅ Page contains 'profile' text")
    else:
        print("❌ Page doesn't contain 'profile' text")
        
finally:
    session.cleanup()
