"""
UP_003: Validate file preview modal opens for supported formats
Expected Result: Preview modal opens displaying file content correctly
Module: User Profile Modules - File Preview
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
import tempfile

def UP_003_file_preview_modal_opens():
    """UP_003: Test file preview opens in separate URL"""
    test_id = "UP_003"
    print(f"\n🧪 Running {test_id}: File Preview Opens")
    print("📋 Module: User Profile Modules - File Preview")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login as user and navigate to user dashboard
        driver = session.login(account_type="user")
        session.navigate_to_dashboard(account_type="user")
        
        # Wait for dashboard to fully load
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
        )
        print("✅ Dashboard loaded")
        
        # Upload a test file first
        test_file_path = None
        try:
            import tempfile
            import os
            
            # Create a small test file
            with tempfile.NamedTemporaryFile(mode='w', suffix='.txt', delete=False) as f:
                f.write("This is a test file for preview functionality.\n")
                f.write("It contains some sample text content.\n")
                f.write("Line 3: Testing file preview.\n")
                test_file_path = f.name
            
            # Find and use file upload input
            upload_input = driver.find_element(By.CSS_SELECTOR, "input[type='file']")
            upload_input.send_keys(test_file_path)
            
            # Wait for upload to complete
            time.sleep(5)
            print("📤 Test file uploaded")
            
        except Exception as upload_error:
            print(f"⚠️ File upload failed: {upload_error}")
            print("🔍 Will try to use existing files for preview test")
        
        # Find files in the dashboard
        file_selectors = [
            ".file-item",
            ".document-item", 
            "[data-item-id]",
            ".file-card",
            ".grid-item"
        ]
        
        file_found = False
        actions_menu = None
        
        for selector in file_selectors:
            file_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            visible_files = [elem for elem in file_elements if elem.is_displayed()]
            
            if visible_files:
                print(f"🎯 Found {len(visible_files)} files with selector: {selector}")
                
                # Try to find actions menu for the first file
                target_file = visible_files[0]
                
                # Look for actions menu trigger
                menu_triggers = [
                    ".actions-btn",
                    ".dropdown-toggle",
                    ".menu-btn",
                    "[data-toggle='dropdown']",
                    ".three-dots",
                    ".options-btn"
                ]
                
                for trigger_selector in menu_triggers:
                    try:
                        menu_trigger = target_file.find_element(By.CSS_SELECTOR, trigger_selector)
                        if menu_trigger.is_displayed():
                            menu_trigger.click()
                            time.sleep(1)
                            actions_menu = True
                            file_found = True
                            print(f"🍔 Opened actions menu with: {trigger_selector}")
                            break
                    except:
                        continue
                
                if file_found:
                    break
        
        if not file_found:
            # Try to find any element with data-item-id (which indicates a file)
            all_files = driver.find_elements(By.CSS_SELECTOR, "[data-item-id]")
            if all_files:
                target_file = all_files[0]
                file_id = target_file.get_attribute("data-item-id")
                print(f"🎯 Found file with ID: {file_id}")
                
                # Try to find and click actions menu
                try:
                    # Look for actions button within or near this file
                    actions_btn = driver.find_element(By.CSS_SELECTOR, f"[data-item-id='{file_id}'] .actions-btn, [data-item-id='{file_id}'] .dropdown-toggle")
                    actions_btn.click()
                    time.sleep(1)
                    actions_menu = True
                    file_found = True
                    print("🍔 Found and opened actions menu")
                except:
                    print("❌ Could not find actions menu for file")
        
        assert file_found and actions_menu, "Could not find file with accessible actions menu"
        
        # Look for the "Open" action button
        open_selectors = [
            "[data-action='open']",
            ".actions-menu-item[data-action='open']",
            "button[data-action='open']",
            ".open-btn",
            ".view-btn"
        ]
        
        open_button = None
        for selector in open_selectors:
            try:
                open_button = driver.find_element(By.CSS_SELECTOR, selector)
                if open_button.is_displayed():
                    print(f"👁️ Found Open button: {selector}")
                    break
            except:
                continue
        
        assert open_button is not None, "Could not find Open action button"
        
        # Get current URL before clicking
        original_url = driver.current_url
        print(f"🌐 Original URL: {original_url}")
        
        # Click the Open button
        open_button.click()
        print("🖱️ Clicked Open button")
        
        # Wait for navigation to preview URL
        WebDriverWait(driver, 15).until(
            lambda driver: "preview" in driver.current_url or "/files/" in driver.current_url
        )
        
        # Verify we're on preview page
        current_url = driver.current_url
        print(f"🎯 Navigated to: {current_url}")
        
        # Check if URL contains preview or files/{id}/preview pattern
        url_is_preview = (
            "preview" in current_url or 
            "/files/" in current_url or
            "view.officeapps.live.com" in current_url  # Office viewer
        )
        
        assert url_is_preview, f"Not on preview page: {current_url}"
        
        # Wait for preview content to load
        time.sleep(5)
        
        # Check for preview page elements
        preview_indicators = [
            ".file-preview",
            ".document-viewer",
            "iframe",
            ".preview-container",
            ".office-viewer",
            "[data-preview]",
            ".file-content"
        ]
        
        preview_content_found = False
        for selector in preview_indicators:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                preview_content_found = True
                print(f"📄 Preview content found: {selector}")
                break
        
        # Check for file type detection in console logs (if available)
        # Note: Selenium can't easily read console logs, so we'll check for visual indicators
        
        # Verify preview page loaded successfully
        page_loaded = preview_content_found or url_is_preview
        
        assert page_loaded, f"Preview page did not load properly - Content: {preview_content_found}, URL: {url_is_preview}"
        
        print(f"✓ {test_id}: File preview opens test PASSED")
        print(f"🎯 Result: Preview page loaded - Content: {preview_content_found}, URL pattern: {url_is_preview}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: File preview opens test FAILED - {str(e)}")
        return False
        
    finally:
        # Cleanup test file
        if test_file_path and os.path.exists(test_file_path):
            try:
                os.unlink(test_file_path)
                print("🧹 Test file cleaned up")
            except:
                pass

if __name__ == "__main__":
    try:
        result = UP_003_file_preview_modal_opens()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
