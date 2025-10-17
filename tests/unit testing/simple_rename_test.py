#!/usr/bin/env python3
"""
Simple rename modal test - just verify the modal opens and elements exist
"""
import sys
import os
sys.path.append(os.path.dirname(__file__))

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time

def test_rename_modal():
    """Test if rename modal opens and elements are present"""
    print("🧪 Testing Rename Modal Functionality")
    print("="*50)

    # Setup Chrome options
    chrome_options = Options()
    # chrome_options.add_argument("--headless")  # Remove headless for manual login
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")

    driver = webdriver.Chrome(options=chrome_options)

    try:
        # Navigate to the app
        driver.get("https://securedocs.live")

        # Wait for page to load
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )

        print("✅ Page loaded")

        # Check if we're on login page
        current_url = driver.current_url
        if "login" in current_url.lower() or "signin" in current_url.lower():
            print("🔐 On login page - please login manually first")
            print("📝 Test URL: https://securedocs.live")
            print("👤 Login as: premium@gmail.com (or your test account)")
            print("⏳ Waiting for you to login...")

            # Wait for user to login (check every 5 seconds for 2 minutes)
            for i in range(24):
                time.sleep(5)
                if "dashboard" in driver.current_url.lower() or "files" in driver.current_url.lower():
                    print("✅ Detected login - proceeding with test")
                    break
                if i % 6 == 0:  # Every 30 seconds
                    print(f"⏳ Still waiting for login... ({(i+1)*5}s elapsed)")
            else:
                print("❌ Timeout waiting for login")
                return False

        # Look for files
        print("🔍 Looking for files...")
        files_container = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "filesContainer"))
        )

        # Find a file to rename
        file_elements = driver.find_elements(By.CSS_SELECTOR, "[data-item-id]")
        csv_files = [elem for elem in file_elements if elem.get_attribute("data-item-name") and ".csv" in elem.get_attribute("data-item-name")]

        if not csv_files:
            print("❌ No CSV files found to test with")
            return False

        test_file = csv_files[0]
        file_name = test_file.get_attribute("data-item-name")
        file_id = test_file.get_attribute("data-item-id")

        print(f"✅ Found test file: {file_name} (ID: {file_id})")

        # Find and click the actions menu button
        actions_btn = test_file.find_element(By.CSS_SELECTOR, ".actions-menu-btn")
        actions_btn.click()

        print("📍 Clicked actions menu")

        # Wait for menu to appear
        time.sleep(1)

        # Find and click rename button
        rename_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Rename')]")
        rename_btn.click()

        print("📝 Clicked rename button")

        # Wait for modal to appear (increased timeout for live site)
        modal = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "renameModal"))
        )

        print("✅ Rename modal appeared!")

        # Wait a moment for modal to fully load
        time.sleep(2)

        # Check modal elements
        input_field = driver.find_element(By.ID, "newFileName")
        confirm_btn = driver.find_element(By.ID, "confirmRename")
        cancel_btn = driver.find_element(By.ID, "cancelRename")

        input_value = input_field.get_attribute("value")
        print(f"📝 Input field has value: '{input_value}'")

        # Test typing
        input_field.clear()
        input_field.send_keys("test_renamed_simple.csv")
        new_value = input_field.get_attribute("value")
        print(f"✏️ Successfully typed: '{new_value}'")

        print("✅ Modal elements are functional!")
        print("🎯 Rename modal test PASSED")

        # Close modal
        cancel_btn.click()
        time.sleep(1)

        return True

    except Exception as e:
        print(f"❌ Test failed: {str(e)}")
        import traceback
        traceback.print_exc()
        return False

    finally:
        driver.quit()

if __name__ == "__main__":
    success = test_rename_modal()
    print(f"\nResult: {'PASSED' if success else 'FAILED'}")
    sys.exit(0 if success else 1)
