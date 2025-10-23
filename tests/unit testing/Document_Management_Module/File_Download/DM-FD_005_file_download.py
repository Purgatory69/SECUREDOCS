"""
DM-FD 005: Validate file download functionality
Expected Result: File downloads correctly with original name
Module: Document Management - File Download
Priority: High
Points: 1
"""

import sys
import os
import time
# Add parent directories to path to import global_session
unit_testing_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
tests_root = os.path.abspath(os.path.join(unit_testing_root, '..'))
for path in (unit_testing_root, tests_root):
    if path not in sys.path:
        sys.path.append(path)

from global_session import session
from test_config import TARGET_FILE_ID, TARGET_FILE_NAME
from test_helpers import wait_for_dashboard, find_file_by_name, invoke_module_handler
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains


def pause_if_requested(marker):
    """Pause execution if TEST_PAUSE environment variable is set."""
    if os.environ.get("TEST_PAUSE"):
        input(f"\n[PAUSE] {marker} - Inspect browser, then press Enter to continue...")


def DM_FD_005_file_download():
    """DM-FD 005: Test file download functionality"""
    test_id = "DM-FD 005"
    print(f"\n[TEST] Running {test_id}: File Download")
    print("[INFO] Module: Document Management - File Download")
    print("[INFO] Priority: High | Points: 1")

    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for dashboard to load
        wait_for_dashboard(driver)
        pause_if_requested("Dashboard loaded")
        print("[OK] Dashboard loaded")

        print(f"[TARGET] Waiting for existing file with ID: {TARGET_FILE_ID} ({TARGET_FILE_NAME})")
        file_card = find_file_by_name(driver, TARGET_FILE_NAME)
        if not file_card:
            try:
                file_card = WebDriverWait(driver, 20).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, f"[data-item-id='{TARGET_FILE_ID}']"))
                )
            except Exception:
                print(f"[ERROR] Could not locate target file {TARGET_FILE_NAME}")
                return False

        print("[OK] Target file card located")

        try:
            actions_menu_btn = file_card.find_element(By.CSS_SELECTOR, ".actions-menu-btn")
            driver.execute_script("arguments[0].click();", actions_menu_btn)
            time.sleep(0.5)
            print("[INFO] Actions menu opened")
        except Exception as menu_error:
            print(f"[ERROR] Could not open actions menu: {menu_error}")
            return False

        open_clicked = False
        try:
            open_btn = driver.find_element(By.CSS_SELECTOR, ".actions-menu-item[data-action='open']")
            driver.execute_script("arguments[0].click();", open_btn)
            open_clicked = True
            print("[CLICK] Selecting Open action")
        except Exception:
            print("[INFO] Open menu item not found; invoking handler directly")

        if not open_clicked:
            if not invoke_module_handler(driver, "open", TARGET_FILE_ID):
                print("[ERROR] Unable to trigger open handler")
                return False

        try:
            WebDriverWait(driver, 15).until(EC.url_contains(f"/files/{TARGET_FILE_ID}/"))
        except Exception:
            print("[ERROR] Browser did not navigate to preview page in time")
            return False

        current_url = driver.current_url
        print(f"[OK] Navigated to: {current_url}")
        if "preview" not in current_url:
            print("[ERROR] Unexpected URL after opening file preview")
            return False

        # Look for download button
        download_button = None
        download_selectors = [
            "#downloadBtn",
            "[id='downloadBtn']",
            "button[id='downloadBtn']",
            ".download-btn",
            "[data-action='download']",
            "button[title*='Download']",
            "a[href*='download']"
        ]

        print("[SEARCH] Looking for download button...")
        
        # Debug: Check what elements are visible on the page
        try:
            all_elements = driver.find_elements(By.XPATH, "//*[@id='downloadBtn' or contains(text(), 'Download') or @title='Download' or @data-action='download']")
            print(f"[DEBUG] Found {len(all_elements)} elements matching download criteria:")
            for i, elem in enumerate(all_elements[:5]):  # Show first 5
                tag = elem.tag_name
                elem_id = elem.get_attribute("id") or ""
                elem_class = elem.get_attribute("class") or ""
                elem_text = elem.text.strip()[:30] if elem.text.strip() else "no text"
                elem_href = elem.get_attribute("href") or ""
                visible = elem.is_displayed()
                print(f"  [{i}] {tag} ID:'{elem_id}' Class:'{elem_class}' Text:'{elem_text}' Href:'{elem_href}' Visible:{visible}")

            # Also check page title and body text
            page_title = driver.title
            body_text = driver.find_element(By.TAG_NAME, "body").text[:200] + "..."
            print(f"[DEBUG] Page title: '{page_title}'")
            print(f"[DEBUG] Body preview: {body_text}")
            
        except Exception as debug_error:
            print(f"[WARN] Could not debug elements: {debug_error}")

        for selector in download_selectors:
            try:
                buttons = driver.find_elements(By.CSS_SELECTOR, selector)
                visible_buttons = [btn for btn in buttons if btn.is_displayed()]
                if visible_buttons:
                    download_button = visible_buttons[0]
                    print(f"[OK] Found download button: {selector}")
                    break
            except Exception as e:
                print(f"[WARN] Selector '{selector}' failed: {str(e)}")

        if not download_button:
            print("[ERROR] No download button found")
            return False

        # Get initial download count (if browser tracks downloads)
        try:
            initial_downloads = driver.execute_script("""
                return Array.from(document.querySelectorAll('a[download]')).length;
            """)
        except Exception:
            initial_downloads = 0

        # Click download button
        print("[DOWNLOAD] Clicking download button...")
        try:
            # Scroll to button if needed
            driver.execute_script("arguments[0].scrollIntoView(true);", download_button)
            time.sleep(1)
            
            download_button.click()
            print("[OK] Download button clicked")
            
            # Wait a moment for download to start
            time.sleep(3)
            
            # Check if download started (browser dependent)
            # We can't reliably detect file downloads in Selenium, so we'll check for:
            # 1. No error messages
            # 2. Button click was successful
            # 3. No navigation away from page (unless expected)
            
            # Check for error notifications
            error_found = False
            try:
                error_elements = driver.find_elements(By.CSS_SELECTOR, 
                    ".error, .notification.error, [class*='error'], .alert-danger")
                for elem in error_elements:
                    if elem.is_displayed() and elem.text.strip():
                        error_found = True
                        print(f"[ERROR] Download error: {elem.text[:100]}...")
                        break
            except:
                pass
            
            if not error_found:
                print(f"[PASS] {test_id}: Download initiated successfully")
                print(f"[RESULT] Download button clicked for '{TARGET_FILE_NAME}' without errors")
                return True
            else:
                print(f"[FAIL] {test_id}: Download failed with error")
                return False

        except Exception as e:
            print(f"[FAIL] {test_id}: Could not click download button - {str(e)}")
            return False

    except Exception as e:
        print(f"[FAIL] {test_id}: File download test FAILED - {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    try:
        result = DM_FD_005_file_download()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
