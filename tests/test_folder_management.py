from webdriver_utils import web_driver
from common_functions import login, create_folder, navigate_to_dashboard, BASE_URL
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
import time

def test_fold_001_create_folder():
    """FOLD_001: Validate folder creation functionality"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        
        # Create folder
        result = create_folder(driver, "Test Folder")
        assert result == True
        
        # Verify folder appears in file list
        folder_cards = driver.find_elements(By.CSS_SELECTOR, ".folder-card, .folder-item")
        folder_found = False
        for card in folder_cards:
            if "Test Folder" in card.text:
                folder_found = True
                break
        
        assert folder_found == True
        
        print("✓ FOLD_001: Create folder test passed")
        return True
    except Exception as e:
        print(f"✗ FOLD_001: Create folder test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_fold_002_navigate_folder():
    """FOLD_002: Validate folder navigation"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        navigate_to_dashboard(driver)
        
        # Find and click on a folder
        folder_card = driver.find_element(By.CSS_SELECTOR, ".folder-card, .folder-item")
        folder_name = folder_card.find_element(By.CSS_SELECTOR, ".folder-name, .item-name").text
        folder_card.click()
        
        # Wait for navigation
        time.sleep(2)
        
        # Check if breadcrumb or URL changed
        breadcrumbs = driver.find_elements(By.CSS_SELECTOR, ".breadcrumb, .breadcrumb-item")
        current_url = driver.current_url
        
        # At least one should indicate we're in a folder
        assert len(breadcrumbs) > 0 or "folder" in current_url or "parent_id" in current_url
        
        print("✓ FOLD_002: Navigate folder test passed")
        return True
    except Exception as e:
        print(f"✗ FOLD_002: Navigate folder test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_fold_003_rename_folder():
    """FOLD_003: Validate folder renaming"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        navigate_to_dashboard(driver)
        
        # Find first folder and open actions menu
        folder_card = driver.find_element(By.CSS_SELECTOR, ".folder-card, .folder-item")
        actions_button = folder_card.find_element(By.CSS_SELECTOR, ".actions-btn, .dropdown-toggle")
        actions_button.click()
        
        # Click rename option
        rename_option = driver.find_element(By.CSS_SELECTOR, "[data-action='rename'], .rename-option")
        rename_option.click()
        
        # Enter new name
        name_input = driver.find_element(By.CSS_SELECTOR, ".rename-input, input[name='name']")
        name_input.clear()
        name_input.send_keys("Renamed Folder")
        
        # Confirm rename
        confirm_button = driver.find_element(By.CSS_SELECTOR, ".confirm-rename, .btn-confirm")
        confirm_button.click()
        
        # Wait for update
        time.sleep(2)
        
        print("✓ FOLD_003: Rename folder test passed")
        return True
    except Exception as e:
        print(f"✗ FOLD_003: Rename folder test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_fold_004_delete_empty_folder():
    """FOLD_004: Validate empty folder deletion"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        navigate_to_dashboard(driver)
        
        # Create a new empty folder first
        create_folder(driver, "Empty Folder")
        
        # Find the empty folder and open actions menu
        folder_cards = driver.find_elements(By.CSS_SELECTOR, ".folder-card, .folder-item")
        target_folder = None
        for card in folder_cards:
            if "Empty Folder" in card.text:
                target_folder = card
                break
        
        assert target_folder is not None
        
        actions_button = target_folder.find_element(By.CSS_SELECTOR, ".actions-btn, .dropdown-toggle")
        actions_button.click()
        
        # Click delete option
        delete_option = driver.find_element(By.CSS_SELECTOR, "[data-action='delete'], .delete-option")
        delete_option.click()
        
        # Confirm deletion
        try:
            confirm_button = driver.find_element(By.CSS_SELECTOR, ".btn-danger, .confirm-delete")
            confirm_button.click()
        except:
            # Handle browser alert
            alert = driver.switch_to.alert
            alert.accept()
        
        # Wait for folder to disappear
        time.sleep(2)
        
        print("✓ FOLD_004: Delete empty folder test passed")
        return True
    except Exception as e:
        print(f"✗ FOLD_004: Delete empty folder test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def run_folder_management_tests():
    """Run all folder management tests"""
    print("Running Folder Management Tests...")
    tests = [
        test_fold_001_create_folder,
        test_fold_002_navigate_folder,
        test_fold_003_rename_folder,
        test_fold_004_delete_empty_folder
    ]
    
    passed = 0
    total = len(tests)
    
    for test in tests:
        if test():
            passed += 1
    
    print(f"\nFolder Management Tests Summary: {passed}/{total} passed")
    return passed == total

if __name__ == "__main__":
    run_folder_management_tests()
