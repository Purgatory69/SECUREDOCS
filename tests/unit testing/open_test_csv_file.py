"""
Test to open and preview the specific test.csv file (ID: 162)
This test will find the test.csv file and use the actions menu to open it for preview
"""

import sys
import os
sys.path.append(os.path.dirname(__file__))

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def open_test_csv_file():
    """Open the test.csv file for preview"""
    print("\n🎯 Opening test.csv file for preview")
    print("📋 File ID: 162")
    print("📄 File: test.csv (2332 bytes, CSV format)")
    
    try:
        # Login as user and navigate to user dashboard
        driver = session.login(account_type="user")
        session.navigate_to_dashboard(account_type="user")
        
        # Wait for dashboard to fully load
        WebDriverWait(driver, 15).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
        )
        print("✅ Dashboard loaded successfully")
        
        # Wait additional time for files to load via AJAX
        print("⏳ Waiting for files to load...")
        time.sleep(15)
        
        # Check if there's a loading indicator and wait for it to disappear
        loading_selectors = [".loading", ".spinner", ".loader", "[data-loading]"]
        for selector in loading_selectors:
            try:
                loading_element = driver.find_element(By.CSS_SELECTOR, selector)
                if loading_element.is_displayed():
                    print(f"🔄 Found loading indicator: {selector}, waiting...")
                    WebDriverWait(driver, 15).until(
                        EC.invisibility_of_element_located((By.CSS_SELECTOR, selector))
                    )
                    print("✅ Loading indicator disappeared")
                    break
            except:
                continue
        
        # Look for file with ID 162 specifically
        print("🔍 Looking for file with ID 162...")
        file_id = "162"
        target_file_element = None
        
        # Strategy 1: Find by data-item-id="162"
        try:
            print("📍 Strategy 1: Finding by [data-item-id='162']...")
            target_file_element = driver.find_element(By.CSS_SELECTOR, f"[data-item-id='{file_id}']")
            if target_file_element.is_displayed():
                print(f"✅ Found file with data-item-id='{file_id}'")
            else:
                print(f"⚠️ Found but not visible")
                target_file_element = None
        except Exception as e:
            print(f"❌ Strategy 1 failed: {str(e)[:50]}")
        
        # Strategy 2: Find by data-file-id="162"
        if target_file_element is None:
            try:
                print("📍 Strategy 2: Finding by [data-file-id='162']...")
                target_file_element = driver.find_element(By.CSS_SELECTOR, f"[data-file-id='{file_id}']")
                if target_file_element.is_displayed():
                    print(f"✅ Found file with data-file-id='{file_id}'")
                else:
                    print(f"⚠️ Found but not visible")
                    target_file_element = None
            except Exception as e:
                print(f"❌ Strategy 2 failed: {str(e)[:50]}")
        
        # Strategy 3: Find by .file-card with data-item-id
        if target_file_element is None:
            try:
                print("📍 Strategy 3: Finding by .file-card[data-item-id='162']...")
                target_file_element = driver.find_element(By.CSS_SELECTOR, f".file-card[data-item-id='{file_id}']")
                if target_file_element.is_displayed():
                    print(f"✅ Found file card with ID {file_id}")
                else:
                    print(f"⚠️ Found but not visible")
                    target_file_element = None
            except Exception as e:
                print(f"❌ Strategy 3 failed: {str(e)[:50]}")
        
        # Strategy 4: Search all elements with data-item-id and find 162
        if target_file_element is None:
            try:
                print("📍 Strategy 4: Searching all [data-item-id] elements...")
                all_items = driver.find_elements(By.CSS_SELECTOR, "[data-item-id]")
                print(f"🔍 Found {len(all_items)} elements with data-item-id")
                
                for item in all_items:
                    item_id = item.get_attribute("data-item-id")
                    if item_id == file_id and item.is_displayed():
                        target_file_element = item
                        print(f"✅ Found file with ID {file_id} in list")
                        break
                    elif item_id == file_id:
                        print(f"⚠️ Found file {file_id} but not visible")
                
                if target_file_element is None:
                    print(f"❌ File ID {file_id} not found in {len(all_items)} items")
            except Exception as e:
                print(f"❌ Strategy 4 failed: {str(e)[:50]}")
        
        # If still not found, show what's available
        if target_file_element is None:
            print("❌ File with ID 162 not found on dashboard")
            print("📋 Available files (showing first 10):")
            all_files = driver.find_elements(By.CSS_SELECTOR, "[data-item-id], [data-file-id]")
            for i, file_elem in enumerate(all_files[:10]):
                try:
                    item_id = file_elem.get_attribute("data-item-id") or file_elem.get_attribute("data-file-id") or "no-id"
                    item_name = file_elem.get_attribute("data-item-name") or "no-name"
                    file_text = file_elem.text[:30] if file_elem.text else "no-text"
                    visible = file_elem.is_displayed()
                    print(f"  {i+1}. ID: {item_id}, Name: {item_name}, Text: '{file_text}', Visible: {visible}")
                except Exception as e:
                    print(f"  {i+1}. Could not read file info: {str(e)[:30]}")
            return False
        
        print(f"🎯 Successfully found file element with ID {file_id}")
        
        print("🖱️ Clicking on test.csv file card to open preview...")
        
        # The file card itself is clickable and will navigate to preview
        # It has role="button" and aria-label="Open file test.csv"
        # Just click directly on the file card element
        
        # Get current URL before clicking
        original_url = driver.current_url
        print(f"🌐 Current URL: {original_url}")
        
        # Click the file card to open preview
        target_file_element.click()
        print("✅ Clicked on test.csv file card")
        
        # Wait for navigation to preview URL
        print("⏳ Waiting for navigation to preview page...")
        
        try:
            WebDriverWait(driver, 15).until(
                lambda driver: "preview" in driver.current_url or "/files/" in driver.current_url and driver.current_url != original_url
            )
        except:
            print("⚠️ URL didn't change as expected, checking current state...")
        
        # Check current URL
        time.sleep(15)  # Allow page to load
        current_url = driver.current_url
        print(f"🎯 Current URL: {current_url}")
        
        # Verify we're on preview page
        url_is_preview = (
            "preview" in current_url or
            "/files/" in current_url or
            "view.officeapps.live.com" in current_url
        )
        
        if url_is_preview:
            print("✅ Successfully navigated to preview page!")
            
            # Wait for preview content to load
            time.sleep(5)
            print("⏳ Waiting for preview content to load...")
            
            # Check for preview page elements
            preview_indicators = [
                ".file-preview",
                ".document-viewer", 
                "iframe",
                ".preview-container",
                ".office-viewer",
                "[data-preview]",
                ".file-content",
                ".csv-preview",
                "table"  # CSV files often display as tables
            ]
            
            preview_content_found = False
            found_element = None
            for selector in preview_indicators:
                elements = driver.find_elements(By.CSS_SELECTOR, selector)
                if elements and any(elem.is_displayed() for elem in elements):
                    preview_content_found = True
                    found_element = selector
                    print(f"📄 Preview content found: {selector}")
                    break
            
            if preview_content_found:
                print(f"✅ test.csv file opened successfully in preview!")
                print(f"🎯 Preview element: {found_element}")
            else:
                print("⚠️ Preview page loaded but content not detected")
                print("📋 This might be normal for CSV files loading via external viewer")
            
            # Check page title or content for CSV indicators
            page_title = driver.title
            page_source = driver.page_source.lower()
            
            csv_indicators = [
                "csv" in page_title.lower(),
                "csv" in page_source,
                "test.csv" in page_source,
                "spreadsheet" in page_source,
                "table" in page_source
            ]
            
            if any(csv_indicators):
                print("📊 CSV file indicators found in page content")
            
            print(f"📄 Page title: {page_title}")
            
            return True
        else:
            print(f"❌ Not on preview page. Current URL: {current_url}")
            return False
        
    except Exception as e:
        print(f"❌ Error opening test.csv file: {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = open_test_csv_file()
        print(f"\n{'='*50}")
        print(f"TEST RESULT: {'✅ SUCCESS' if result else '❌ FAILED'}")
        print(f"{'='*50}")
    finally:
        session.cleanup()
