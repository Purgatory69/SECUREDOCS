"""
DM-FR 009: Validate file restore from trash
Expected Result: File restored to original location
Module: Document Management - File Restore
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    count_files_on_dashboard
)
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def DM_FR_009_file_restore():
    """DM-FR 009: Validate file restore from trash"""
    test_id = "DM-FR 009"
    print(f"\n🧪 Running {test_id}: File Restore From Trash")
    print("📋 Module: Document Management - File Restore")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")
        
        # Count initial files in main view
        initial_main_count = count_files_on_dashboard(driver)
        print(f"📊 Initial main view count: {initial_main_count}")
        
        # Navigate to trash view
        print("🗑️ Navigating to trash view...")
        try:
            # Try to find and click trash button
            trash_btn = driver.find_element(By.CSS_SELECTOR, "[data-view='trash'], #trashBtn, .trash-btn")
            trash_btn.click()
            time.sleep(2)
            print("✅ Navigated to trash view")
        except:
            # Try JavaScript approach
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
        
        # Find first file in trash
        trash_files = driver.find_elements(By.CSS_SELECTOR, "#filesContainer [data-item-id]")
        if not trash_files:
            print("❌ No files found in trash")
            return False
        
        file_card = trash_files[0]
        file_name = file_card.get_attribute('data-item-name')
        print(f"📄 Found file in trash: {file_name}")
        
        # Find and click actions menu
        try:
            actions_menu_btn = file_card.find_element(By.CSS_SELECTOR, ".actions-menu-btn")
            driver.execute_script("arguments[0].click();", actions_menu_btn)
            time.sleep(0.5)
            print("📌 Actions menu opened")
        except:
            print("⚠️ Could not open actions menu")
            return False
        
        # Find and click restore button
        try:
            restore_btn = driver.find_element(By.CSS_SELECTOR, ".actions-menu-item[data-action='restore']")
            restore_btn.click()
            print("♻️ Clicked restore button")
        except:
            print("❌ Restore button not found")
            return False
        
        # Wait for restore to complete
        time.sleep(3)
        
        # Navigate back to main view
        print("🏠 Navigating back to main view...")
        try:
            home_btn = driver.find_element(By.CSS_SELECTOR, "[data-view='main'], #homeBtn, .home-btn")
            home_btn.click()
            time.sleep(2)
        except:
            driver.execute_script("if (window.loadUserFiles) window.loadUserFiles();")
            time.sleep(2)
        
        # Count files in main view after restore
        final_main_count = count_files_on_dashboard(driver)
        file_restored = final_main_count > initial_main_count
        
        if file_restored:
            print(f"✅ File restored to main view: {initial_main_count} → {final_main_count}")
            return True
        else:
            print(f"❌ File not restored: {initial_main_count} → {final_main_count}")
            return False
        
    except Exception as e:
        print(f"✗ {test_id}: File Restore test FAILED - {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    try:
        result = DM_FR_009_file_restore()
        print(f"\nDM-FR 009: {'PASSED' if result else 'FAILED'} (1 points)")
    finally:
        session.cleanup()
