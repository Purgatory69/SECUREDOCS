"""
DM_013: Validate permanent document deletion
Expected Result: Document permanently deleted from system
Module: Document Management Modules - Delete Documents
Priority: Medium
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
    wait_for_file_presence,
    wait_for_file_absence,
    find_file_by_name,
    open_actions_menu,
    find_actions_menu_item,
    invoke_module_handler,
    switch_to_main_view
)

TARGET_FILE_NAME = "Louiejay_Test_Plan.csv"

def DM_013_permanent_document_deletion():
    """DM_013: Validate permanent document deletion"""
    test_id = "DM_013"
    print(f"\n🧪 Running {test_id}: Permanent Document Deletion")
    print("📋 Module: Document Management Modules - Delete Documents")
    print("🎯 Priority: Medium | Points: 1")
    
    try:
        driver = session.login()
        session.navigate_to_dashboard()
        wait_for_dashboard(driver)

        print(f"📂 Preparing '{TARGET_FILE_NAME}' for permanent deletion test")
        if not ensure_file_in_trash(driver, TARGET_FILE_NAME):
            raise AssertionError(f"Unable to move '{TARGET_FILE_NAME}' to trash prior to permanent delete test")

        if not switch_to_trash_view(driver, wait_seconds=5):
            raise AssertionError("Could not switch to trash view")

        if not wait_for_file_presence(driver, TARGET_FILE_NAME, timeout=15):
            raise AssertionError(f"'{TARGET_FILE_NAME}' not located in trash")

        file_card = find_file_by_name(driver, TARGET_FILE_NAME)
        if not file_card:
            raise AssertionError("File card not found in trash for permanent delete")

        if not open_actions_menu(driver, file_card, debug_label="for permanent delete"):
            raise AssertionError("Failed to open actions menu in trash for permanent delete")

        force_btn = find_actions_menu_item(driver, 'force-delete', fallback_text='delete permanently')
        item_id = file_card.get_attribute('data-item-id')

        driver.execute_script("window.confirm = () => true;")

        clicked = False
        if force_btn:
            try:
                driver.execute_script("arguments[0].click();", force_btn)
                clicked = True
                print("✅ Triggered permanent delete via actions menu")
            except Exception as click_error:
                print(f"⚠️ Permanent delete button click failed: {str(click_error)}")

        if not clicked:
            print("ℹ️ Falling back to window.__files.forceDeleteItem handler")
            if not invoke_module_handler(driver, 'forceDeleteItem', item_id):
                raise AssertionError("Failed to invoke permanent delete handler")

        if not wait_for_file_absence(driver, TARGET_FILE_NAME, timeout=15):
            raise AssertionError("File still present in trash after permanent delete")

        if not switch_to_main_view(driver, wait_seconds=3):
            raise AssertionError("Failed to navigate back to main documents view")

        if wait_for_file_presence(driver, TARGET_FILE_NAME, timeout=5):
            raise AssertionError("File unexpectedly reappeared on main dashboard after permanent delete")

        print(f"✓ {test_id}: Permanent document deletion successful")
        print("🎯 Result: File removed from trash and remains absent from main view")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Permanent Document Deletion test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = DM_013_permanent_document_deletion()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
