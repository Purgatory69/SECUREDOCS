"""
DM-FPD 010: Validate permanent file deletion
Expected Result: File permanently deleted from system
Module: Document Management - File Permanent Delete
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import wait_for_dashboard
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def DM_FPD_010_permanent_deletion():
    """DM-FPD 010: Validate permanent file deletion"""
    test_id = "DM-FPD 010"
    print(f"\n🧪 Running {test_id}: Permanent File Deletion")
    print("📋 Module: Document Management - File Permanent Delete")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")
        
        # Navigate to trash view
        print("🗑️ Navigating to trash view...")
        try:
            trash_btn = driver.find_element(By.CSS_SELECTOR, "[data-view='trash'], #trashBtn, .trash-btn")
            trash_btn.click()
            time.sleep(2)
            print("✅ Navigated to trash view")
        except:
            driver.execute_script("if (window.loadTrashItems) window.loadTrashItems();")
            time.sleep(2)
            print("✅ Loaded trash view via JavaScript")
        
        # Wait for trash items to load
        try:
            WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "#filesContainer [data-item-id]"))
            )
            print("⏳ Trash items loaded")
        except:
            print("⚠️ No items in trash")
            return False
        
        # Count initial trash files
        initial_trash_files = driver.find_elements(By.CSS_SELECTOR, "#filesContainer [data-item-id]")
        initial_count = len(initial_trash_files)
        print(f"📊 Initial trash count: {initial_count}")
        
        if initial_count == 0:
            print("❌ No files in trash to delete")
            return False
        
        # Get first file in trash
        file_card = initial_trash_files[0]
        file_name = file_card.get_attribute('data-item-name')
        file_id = file_card.get_attribute('data-item-id')
        print(f"📄 Target file for permanent deletion: {file_name} (ID: {file_id})")
        
        # Find and click actions menu
        try:
            actions_menu_btn = file_card.find_element(By.CSS_SELECTOR, ".actions-menu-btn")
            driver.execute_script("arguments[0].click();", actions_menu_btn)
            time.sleep(0.5)
            print("📌 Actions menu opened")
        except:
            print("⚠️ Could not open actions menu")
            return False
        
        # Find and click permanent delete button
        try:
            delete_btn = driver.find_element(By.CSS_SELECTOR, ".actions-menu-item[data-action='force-delete'], .actions-menu-item[data-action='permanent-delete']")
            delete_btn.click()
            print("💀 Clicked permanent delete button")
        except:
            print("❌ Permanent delete button not found")
            return False
        
        # Handle confirmation dialog if it appears
        time.sleep(1)
        try:
            confirm_btn = driver.find_element(By.CSS_SELECTOR, ".confirm-delete-btn, #confirmDelete, [data-action='confirm']")
            confirm_btn.click()
            print("✅ Confirmed permanent deletion")
        except:
            print("ℹ️ No confirmation dialog (or already confirmed)")
        
        # Wait for deletion to complete
        time.sleep(3)
        
        # Count files in trash after deletion
        final_trash_files = driver.find_elements(By.CSS_SELECTOR, "#filesContainer [data-item-id]")
        final_count = len(final_trash_files)
        
        file_deleted = final_count < initial_count
        
        if file_deleted:
            print(f"✅ File permanently deleted: {initial_count} → {final_count}")
            return True
        else:
            print(f"❌ File still in trash: {initial_count} → {final_count}")
            return False
        
    except Exception as e:
        print(f"✗ {test_id}: Permanent File Deletion test FAILED - {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    try:
        result = DM_FPD_010_permanent_deletion()
        print(f"\nDM-FPD 010: {'PASSED' if result else 'FAILED'} (1 points)")
    finally:
        session.cleanup()
