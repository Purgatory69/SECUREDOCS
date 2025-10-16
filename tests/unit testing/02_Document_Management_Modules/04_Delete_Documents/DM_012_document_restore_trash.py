"""
DM_012: Validate document restore from trash
Expected Result: Document restored from trash to original location
Module: Document Management Modules - Delete Documents
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    ensure_file_in_trash,
    switch_to_trash_view,
    switch_to_main_view,
    wait_for_file_presence,
    wait_for_file_absence,
    find_file_by_name,
    open_actions_menu,
    find_actions_menu_item,
    invoke_module_handler
)

TARGET_FILE_NAME = "Louiejay_Test_Plan.csv"

def DM_012_document_restore_trash():
    """DM_012: Validate document restore from trash"""
    test_id = "DM_012"
    print(f"\n🧪 Running {test_id}: Document Restore From Trash")
    print("📋 Module: Document Management Modules - Delete Documents")
    print("🎯 Priority: High | Points: 1")
    
    try:
        driver = session.login()
        session.navigate_to_dashboard()
        wait_for_dashboard(driver)

        print(f"📂 Ensuring '{TARGET_FILE_NAME}' is present in trash for restoration")
        if not ensure_file_in_trash(driver, TARGET_FILE_NAME):
            raise AssertionError(f"Unable to move '{TARGET_FILE_NAME}' to trash prior to restore test")

        if not switch_to_trash_view(driver, wait_seconds=5):
            raise AssertionError("Could not switch to trash view")

        if not wait_for_file_presence(driver, TARGET_FILE_NAME, timeout=15):
            raise AssertionError(f"'{TARGET_FILE_NAME}' not visible in trash")

        file_card = find_file_by_name(driver, TARGET_FILE_NAME)
        if not file_card:
            raise AssertionError("File card not located in trash after presence check")

        if not open_actions_menu(driver, file_card, debug_label="for restore"):
            raise AssertionError("Could not open actions menu in trash for restore")

        restore_btn = find_actions_menu_item(driver, 'restore', fallback_text='restore')
        item_id = file_card.get_attribute('data-item-id')

        driver.execute_script("window.confirm = () => true;")

        clicked = False
        if restore_btn:
            try:
                driver.execute_script("arguments[0].click();", restore_btn)
                clicked = True
                print("✅ Triggered restore via actions menu")
            except Exception as click_error:
                print(f"⚠️ Restore button click failed: {str(click_error)}")

        if not clicked:
            print("ℹ️ Falling back to window.__files.restoreItem handler")
            if not invoke_module_handler(driver, 'restoreItem', item_id):
                raise AssertionError("Failed to invoke restore handler")

        if not wait_for_file_absence(driver, TARGET_FILE_NAME, timeout=15):
            raise AssertionError("File still present in trash after restore action")

        if not switch_to_main_view(driver, wait_seconds=5):
            raise AssertionError("Failed to return to main documents view")

        if not wait_for_file_presence(driver, TARGET_FILE_NAME, timeout=20):
            raise AssertionError("Restored file not visible on main dashboard")

        print(f"✓ {test_id}: Document restored from trash successfully")
        print("🎯 Result: File returned to main documents view")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Document Restore From Trash test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = DM_012_document_restore_trash()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
