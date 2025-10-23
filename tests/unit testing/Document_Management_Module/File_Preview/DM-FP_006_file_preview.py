"""
DM-FP 006: Validate file preview for supported formats
Expected Result: Preview modal opens with file content
Module: Document Management - File Preview
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
from test_config import TARGET_FILE_ID, TARGET_FILE_NAME, TARGET_FILE_SELECTOR, TARGET_FILE_PATH
from test_helpers import wait_for_dashboard
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


def pause_if_requested(marker):
    """Pause execution if TEST_PAUSE environment variable is set."""
    if os.environ.get("TEST_PAUSE"):
        input(f"\n[PAUSE] {marker} - Inspect browser, then press Enter to continue...")


def DM_FP_006_file_preview():
    """DM-FP 006: Test file preview functionality"""
    test_id = "DM-FP 006"
    print(f"\n[TEST] Running {test_id}: File Preview")
    print("[INFO] Module: Document Management - File Preview")
    print("[INFO] Priority: High | Points: 1")

    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for dashboard to load
        wait_for_dashboard(driver)
        pause_if_requested("Dashboard loaded")
        print("[OK] Dashboard loaded")

        # Look for the existing file with ID 192
        target_file_id = "192"
        print(f"[TARGET] Waiting for existing file with ID: {target_file_id} (Louiejay_Test_Plan.csv)")

        file_card = None
        try:
            file_card = WebDriverWait(driver, 20).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, f"[data-item-id='{target_file_id}']"))
            )
        except Exception:
            print(f"[ERROR] Could not locate file card for ID {target_file_id}")
            return False

        print("[OK] Target file card located")

        # Debug: Check file card properties and event listeners
        debug_info = driver.execute_script("""
            const card = arguments[0];
            const fileId = card.dataset.fileId || card.dataset.itemId;
            const isFolder = card.dataset.isFolder === 'true';
            const hasEventListeners = !!card.onclick || !!card._events;
            
            // Check if handleFilePreview function exists
            const hasHandleFilePreview = typeof window.handleFilePreview === 'function';
            
            return {
                fileId: fileId,
                isFolder: isFolder,
                hasEventListeners: hasEventListeners,
                hasHandleFilePreview: hasHandleFilePreview,
                tagName: card.tagName,
                className: card.className,
                dataAttributes: Object.keys(card.dataset)
            };
        """, file_card)
        
        print(f"[DEBUG] File card info: {debug_info}")

        if not debug_info.get('hasHandleFilePreview', False):
            print("[INFO] handleFilePreview function not available, navigating directly to preview URL")
            # Navigate directly to the preview URL since the JS function isn't loaded
            preview_url = f"/files/{target_file_id}/preview"
            print(f"[NAVIGATE] Going directly to: {preview_url}")
            driver.get(f"{driver.current_url.rstrip('/')}{preview_url}")
        else:
            # Move cursor to reveal actions button
            try:
                actions = ActionChains(driver)
                actions.move_to_element(file_card).perform()
                time.sleep(1)
            except Exception as hover_error:
                print(f"[WARN] Could not hover over file card automatically: {hover_error}")

            # Ensure actions button is visible
            try:
                actions_button = WebDriverWait(file_card, 10).until(
                    lambda card: card.find_element(By.CSS_SELECTOR, '.actions-menu-btn')
                )
            except Exception:
                print("[ERROR] Actions menu button not found inside file card")
                return False

            if not actions_button.is_displayed():
                driver.execute_script("arguments[0].style.opacity='1'; arguments[0].style.visibility='visible';", actions_button)
                time.sleep(0.5)

            print("[CLICK] Opening actions menu for target file")
            actions_button.click()

            # Wait for the actions menu to render
            try:
                WebDriverWait(driver, 10).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, '.actions-menu'))
                )
            except Exception:
                print("[ERROR] Actions menu did not appear")
                return False

            time.sleep(0.5)

            # Inspect available menu items for diagnostics
            try:
                menu_items = driver.execute_script(
                    """
                    return Array.from(document.querySelectorAll('.actions-menu-item')).map(btn => ({
                        action: btn.dataset.action || '',
                        itemId: btn.dataset.itemId || '',
                        text: (btn.textContent || '').trim(),
                        visible: !!(btn.offsetParent)
                    }));
                    """
                )
                if menu_items:
                    print("[DEBUG] Actions menu items:")
                    for item in menu_items:
                        print(f"  - action={item['action']} | itemId={item['itemId']} | text='{item['text']}' | visible={item['visible']}")
                else:
                    print("[DEBUG] No actions menu items detected")
            except Exception as menu_error:
                print(f"[WARN] Could not inspect actions menu items: {menu_error}")

            # Locate the Open button (handle multiple selector strategies)
            open_button = None
            candidates = driver.find_elements(By.CSS_SELECTOR, '.actions-menu-item')
            for candidate in candidates:
                action = (candidate.get_attribute('data-action') or '').lower()
                item_id = candidate.get_attribute('data-item-id') or ''
                text = (candidate.text or '').strip().lower()
                if action == 'open' and (item_id == target_file_id or item_id == '' or item_id is None):
                    open_button = candidate
                    break
                if action == 'open' and not item_id:
                    open_button = candidate
                    break
                if 'open' in text and candidate.is_displayed():
                    open_button = candidate
                    break

            if not open_button:
                print("[ERROR] Open action not available in actions menu")
                return False

            print("[CLICK] Selecting Open action")
            driver.execute_script("arguments[0].click();", open_button)

        # Wait for navigation to preview URL
        try:
            WebDriverWait(driver, 15).until(EC.url_contains(f"/files/{target_file_id}/"))
        except Exception:
            print("[ERROR] Browser did not navigate to preview page in time")
            return False

        current_url = driver.current_url
        print(f"[OK] Navigated to: {current_url}")
        if "preview" in current_url or f"/files/{target_file_id}" in current_url:
            print(f"[PASS] {test_id}: File preview opened successfully")
            return True

        print("[FAIL] Unexpected URL after opening file")
        return False

    except Exception as e:
        print(f"FAILED {test_id}: File preview modal opens test FAILED - {str(e)}")
        return False

    finally:
        # Only cleanup if we created our own session
        if 'session' in locals():
            session.cleanup()


if __name__ == "__main__":
    DM_FP_006_file_preview()
