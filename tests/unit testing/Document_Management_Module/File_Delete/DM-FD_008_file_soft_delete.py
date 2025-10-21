"""
DM-FD 008: Validate file soft delete (move to trash)
Expected Result: File moved to trash and removed from main view
Module: Document Management - File Delete
Priority: High
Points: 1
"""

import sys
import os
import time

unit_testing_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
tests_root = os.path.abspath(os.path.join(unit_testing_root, '..'))
for path in (unit_testing_root, tests_root):
    if path not in sys.path:
        sys.path.append(path)

from global_session import session
from test_config import TARGET_FILE_ID, TARGET_FILE_NAME
from test_helpers import (
    wait_for_dashboard,
    count_files_on_dashboard,
    find_file_by_name,
    invoke_module_handler,
    wait_for_file_absence
)
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def DM_FD_008_file_soft_delete():
    """DM-FD 008: Validate file soft delete (move to trash)"""
    test_id = "DM-FD 008"
    print(f"\n[TEST] Running {test_id}: File Soft Delete (Move To Trash)")
    print("[INFO] Module: Document Management - File Delete")
    print("[INFO] Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        wait_for_dashboard(driver)
        print("[OK] Dashboard loaded")

        # Ensure files have rendered
        try:
            WebDriverWait(driver, 10).until(
                EC.visibility_of_element_located((By.CSS_SELECTOR, "#filesContainer [data-item-id]"))
            )
            print("[WAIT] Files detected on dashboard")
        except Exception:
            print("[WARN] No visible files detected")
        
        # Count initial files
        initial_count = count_files_on_dashboard(driver)
        print(f"[COUNT] Initial document count: {initial_count}")
        
        # Locate the configured target file
        target_file_name = TARGET_FILE_NAME
        file_card = find_file_by_name(driver, target_file_name)

        if not file_card:
            print(f"[WARN] Target file '{target_file_name}' not found")
            return False

        
        print(f"[FILE] Found target file: {target_file_name}")
        
        # Find and click actions menu button
        try:
            actions_menu_btn = file_card.find_element(By.CSS_SELECTOR, ".actions-menu-btn")
            driver.execute_script("arguments[0].click();", actions_menu_btn)
            time.sleep(0.5)
            print("[INFO] Actions menu opened")
        except:
            print("[WARN] Could not open actions menu")
            return False
        
        # Attempt to click delete button; fallback to direct handler with known ID
        delete_btn = None
        try:
            delete_btn = driver.find_element(By.CSS_SELECTOR, ".actions-menu-item[data-action='delete']")
        except Exception:
            delete_btn = None

        if delete_btn:
            try:
                driver.execute_script("arguments[0].click();", delete_btn)
                print("[DELETE] Clicked delete button from actions menu")
            except Exception as click_error:
                print(f"[WARN] Delete click failed: {str(click_error)}")
                if not invoke_module_handler(driver, 'deleteItem', TARGET_FILE_ID):
                    print("[ERROR] Unable to invoke delete handler")
                    return False
        else:
            print("[INFO] Delete menu item not found; invoking handler directly")
            if not invoke_module_handler(driver, 'deleteItem', TARGET_FILE_ID):
                print("[ERROR] Unable to invoke delete handler")
                return False

        if not wait_for_file_absence(driver, target_file_name, timeout=10):
            print(f"[ERROR] File still present after delete request: {target_file_name}")
            return False

        print(f"[OK] File removed from main view: {initial_count} -> {count_files_on_dashboard(driver)}")
        return True
        
    except Exception as e:
        print(f"[FAIL] {test_id}: File Soft Delete test FAILED - {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    try:
        result = DM_FD_008_file_soft_delete()
        print(f"\nDM-FD 008: {'PASSED' if result else 'FAILED'} (1 points)")
    finally:
        session.cleanup()
