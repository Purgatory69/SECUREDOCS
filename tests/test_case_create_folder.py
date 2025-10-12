"""
FOLD_001: Validate folder creation functionality
Expected Result: New folder created with specified name
"""

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def test_case_create_folder():
    """FOLD_001: Test folder creation"""
    test_id = "FOLD_001"
    print(f"\n🧪 Running {test_id}: Create Folder")
    
    folder_name = f"Test Folder {int(time.time())}"  # Unique name
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Count folders before creation
        initial_folders = driver.find_elements(By.CSS_SELECTOR, ".folder-card, .folder-item, .folder")
        initial_count = len(initial_folders)
        print(f"📊 Initial folder count: {initial_count}")
        
        # Find and click create folder button
        create_folder_selectors = [
            "#create-folder-btn",
            ".create-folder-btn", 
            "button[data-action='create-folder']",
            ".btn-create-folder",
            "[title*='Create Folder']",
            "[title*='New Folder']"
        ]
        
        create_button = None
        for selector in create_folder_selectors:
            try:
                create_button = WebDriverWait(driver, 2).until(
                    EC.element_to_be_clickable((By.CSS_SELECTOR, selector))
                )
                break
            except:
                continue
        
        assert create_button is not None, "Could not find create folder button"
        
        create_button.click()
        print("📁 Clicked create folder button")
        
        # Wait for modal or input to appear
        time.sleep(1)
        
        # Find folder name input
        name_input_selectors = [
            "#folder-name-input",
            "input[name='folder_name']",
            "input[name='name']",
            ".folder-name-input",
            ".modal input[type='text']",
            ".create-folder-modal input"
        ]
        
        name_input = None
        for selector in name_input_selectors:
            try:
                name_input = WebDriverWait(driver, 3).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, selector))
                )
                if name_input.is_displayed():
                    break
            except:
                continue
        
        assert name_input is not None, "Could not find folder name input"
        
        # Enter folder name
        name_input.clear()
        name_input.send_keys(folder_name)
        print(f"📝 Entered folder name: {folder_name}")
        
        # Find and click submit button
        submit_selectors = [
            "button[type='submit']",
            ".btn-primary",
            ".confirm-create",
            ".create-btn",
            ".modal-footer button:not(.btn-secondary)"
        ]
        
        submit_button = None
        for selector in submit_selectors:
            try:
                submit_button = driver.find_element(By.CSS_SELECTOR, selector)
                if submit_button.is_displayed():
                    break
            except:
                continue
        
        if submit_button is None:
            # Try pressing Enter in the input field
            from selenium.webdriver.common.keys import Keys
            name_input.send_keys(Keys.RETURN)
            print("⌨️ Pressed Enter to create folder")
        else:
            submit_button.click()
            print("✅ Clicked submit button")
        
        # Wait for folder creation
        time.sleep(3)
        
        # Check for success indicators
        success_selectors = [
            ".alert-success",
            ".success-message",
            ".toast-success"
        ]
        
        folder_creation_success = False
        for selector in success_selectors:
            success_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if success_elements and any(elem.is_displayed() for elem in success_elements):
                folder_creation_success = True
                break
        
        # Check if new folder appears in the list
        final_folders = driver.find_elements(By.CSS_SELECTOR, ".folder-card, .folder-item, .folder")
        final_count = len(final_folders)
        
        folder_found = False
        for folder_element in final_folders:
            if folder_name in folder_element.text:
                folder_found = True
                break
        
        # Assert success
        assert (final_count > initial_count) or folder_creation_success or folder_found, \
            f"Folder creation failed - Count: {initial_count} -> {final_count}, Success msg: {folder_creation_success}, Folder found: {folder_found}"
        
        print(f"✓ {test_id}: Create folder test PASSED")
        print(f"📊 Final folder count: {final_count}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Create folder test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = test_case_create_folder()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
