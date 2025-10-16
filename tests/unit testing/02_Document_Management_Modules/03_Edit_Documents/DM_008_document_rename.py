"""
DM_008: Validate document rename functionality
Expected Result: Document renamed successfully with new name displayed
Module: Document Management Modules - Edit Documents
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

"""
DM_008: Validate document rename functionality
Expected Result: Document renamed successfully with new name displayed
Module: Document Management Modules - Edit Documents
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
from selenium.webdriver.common.action_chains import ActionChains
import time

def DM_008_document_rename():
    """DM_008: Validate document rename functionality"""
    test_id = "DM_008"
    print(f"\n🧪 Running {test_id}: Document Rename")
    print("📋 Module: Document Management Modules - Edit Documents")
    print("🎯 Priority: High | Points: 1")

    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for page to load completely
        WebDriverWait(driver, 10).until(
            lambda d: d.execute_script("return document.readyState") == "complete"
        )

        # Wait for file container to be present
        file_container = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "filesContainer"))
        )

        # Wait a bit more for files to load
        time.sleep(2)

        # Find the first file item in the list (not a folder)
        file_items = driver.find_elements(By.CSS_SELECTOR, '[data-item-id]:not([data-is-folder="true"])')

        if not file_items:
            print(f"✗ {test_id}: No files found to rename")
            return False

        # Get the first file item
        file_item = file_items[0]
        file_id = file_item.get_attribute('data-item-id')
        original_name = file_item.get_attribute('data-item-name')

        print(f"📁 Found file: {original_name} (ID: {file_id})")

        # Hover over the file item to reveal the actions button
        actions = ActionChains(driver)
        actions.move_to_element(file_item).perform()

        # Wait for the actions button to appear
        actions_button = WebDriverWait(driver, 5).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, f'[data-item-id="{file_id}"] .actions-btn, [data-item-id="{file_id}"] .actions-menu-btn'))
        )

        # Click the actions button
        actions_button.click()

        # Wait for the actions menu to appear
        actions_menu = WebDriverWait(driver, 5).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, '.actions-menu'))
        )

        # Find and click the rename option
        rename_option = WebDriverWait(driver, 5).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, '.actions-menu-item[data-action="rename"]'))
        )

        rename_option.click()

        # Wait for the rename modal to appear
        rename_modal = WebDriverWait(driver, 5).until(
            EC.presence_of_element_located((By.ID, 'renameModal'))
        )

        # Get the input field and enter new name
        new_name_input = WebDriverWait(driver, 5).until(
            EC.presence_of_element_located((By.ID, 'newFileName'))
        )

        # Create a new name by appending "_renamed" to original name
        new_name = original_name.rsplit('.', 1)[0] + "_renamed"
        if '.' in original_name:
            new_name += '.' + original_name.rsplit('.', 1)[1]

        # Clear the input and enter new name
        new_name_input.clear()
        new_name_input.send_keys(new_name)

        # Click the rename button
        confirm_button = driver.find_element(By.ID, 'confirmRename')
        confirm_button.click()

        # Wait for the modal to close (indicating success)
        WebDriverWait(driver, 10).until(
            EC.invisibility_of_element_located((By.ID, 'renameModal'))
        )

        # Wait a bit for the file list to refresh
        time.sleep(2)

        # Verify that the file name was updated in the UI
        updated_file_item = driver.find_element(By.CSS_SELECTOR, f'[data-item-id="{file_id}"]')

        # Check if the file name was updated
        updated_name = updated_file_item.get_attribute('data-item-name')
        display_name_element = updated_file_item.find_element(By.CSS_SELECTOR, '.file-name, .folder-name, [class*="name"]')

        print(f"📝 Original name: {original_name}")
        print(f"🔄 New name: {new_name}")
        print(f"✅ Updated name in data attribute: {updated_name}")

        # Check if the name was updated (either in data attribute or display text)
        name_updated = False
        if updated_name == new_name:
            name_updated = True
            print("✅ Name updated in data attribute")
        elif display_name_element and new_name in display_name_element.text:
            name_updated = True
            print("✅ Name updated in display text")
        else:
            # Try to find the name in any child element
            name_elements = updated_file_item.find_elements(By.CSS_SELECTOR, '*')
            for elem in name_elements:
                if elem.text and new_name in elem.text:
                    name_updated = True
                    print("✅ Name found in child element")
                    break

        if name_updated:
            print(f"✓ {test_id}: Document Rename test PASSED")
            print(f"🎯 Result: File renamed from '{original_name}' to '{new_name}' successfully")
            return True
        else:
            print(f"✗ {test_id}: Document Rename test FAILED - Name not updated in UI")
            print(f"Expected: {new_name}, but file still shows different name")
            return False

    except Exception as e:
        print(f"✗ {test_id}: Document Rename test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = DM_008_document_rename()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
