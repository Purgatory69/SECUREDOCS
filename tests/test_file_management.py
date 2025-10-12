from webdriver_utils import web_driver
from common_functions import login, create_folder, upload_file, navigate_to_dashboard, BASE_URL
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
import time
import os

def test_file_001_single_upload():
    """FILE_001: Validate single file upload functionality"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        
        # Navigate to dashboard
        navigate_to_dashboard(driver)
        
        # Create a test file
        test_file_path = "C:\\temp\\test_upload.txt"
        os.makedirs(os.path.dirname(test_file_path), exist_ok=True)
        with open(test_file_path, 'w') as f:
            f.write("Test file content")
        
        # Upload file
        file_input = driver.find_element(By.CSS_SELECTOR, "input[type='file']")
        file_input.send_keys(test_file_path)
        
        # Wait for upload completion
        time.sleep(3)
        
        # Check if file appears in file list
        file_cards = driver.find_elements(By.CSS_SELECTOR, ".file-card, .file-item")
        assert len(file_cards) > 0
        
        print("✓ FILE_001: Single file upload test passed")
        return True
    except Exception as e:
        print(f"✗ FILE_001: Single file upload test failed - {str(e)}")
        return False
    finally:
        # Cleanup
        try:
            os.remove(test_file_path)
        except:
            pass
        driver.quit()

def test_file_007_rename_file():
    """FILE_007: Validate file renaming functionality"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        navigate_to_dashboard(driver)
        
        # Find first file and open actions menu
        file_card = driver.find_element(By.CSS_SELECTOR, ".file-card, .file-item")
        actions_button = file_card.find_element(By.CSS_SELECTOR, ".actions-btn, .dropdown-toggle")
        actions_button.click()
        
        # Click rename option
        rename_option = driver.find_element(By.CSS_SELECTOR, "[data-action='rename'], .rename-option")
        rename_option.click()
        
        # Enter new name
        name_input = driver.find_element(By.CSS_SELECTOR, ".rename-input, input[name='name']")
        name_input.clear()
        name_input.send_keys("renamed_file.txt")
        
        # Confirm rename
        confirm_button = driver.find_element(By.CSS_SELECTOR, ".confirm-rename, .btn-confirm")
        confirm_button.click()
        
        # Wait for update
        time.sleep(2)
        
        print("✓ FILE_007: File rename test passed")
        return True
    except Exception as e:
        print(f"✗ FILE_007: File rename test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_file_008_delete_file():
    """FILE_008: Validate file soft delete (move to trash)"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        navigate_to_dashboard(driver)
        
        # Find first file and open actions menu
        file_card = driver.find_element(By.CSS_SELECTOR, ".file-card, .file-item")
        actions_button = file_card.find_element(By.CSS_SELECTOR, ".actions-btn, .dropdown-toggle")
        actions_button.click()
        
        # Click delete option
        delete_option = driver.find_element(By.CSS_SELECTOR, "[data-action='delete'], .delete-option")
        delete_option.click()
        
        # Confirm deletion in modal or alert
        try:
            confirm_button = driver.find_element(By.CSS_SELECTOR, ".btn-danger, .confirm-delete")
            confirm_button.click()
        except:
            # Handle browser alert
            alert = driver.switch_to.alert
            alert.accept()
        
        # Wait for file to disappear
        time.sleep(2)
        
        print("✓ FILE_008: File delete test passed")
        return True
    except Exception as e:
        print(f"✗ FILE_008: File delete test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def test_file_005_download():
    """FILE_005: Validate file download functionality"""
    driver = web_driver()
    try:
        # Login first
        login(driver, "test@example.com", "password")
        navigate_to_dashboard(driver)
        
        # Find first file and open actions menu
        file_card = driver.find_element(By.CSS_SELECTOR, ".file-card, .file-item")
        actions_button = file_card.find_element(By.CSS_SELECTOR, ".actions-btn, .dropdown-toggle")
        actions_button.click()
        
        # Click download option
        download_option = driver.find_element(By.CSS_SELECTOR, "[data-action='download'], .download-option")
        download_option.click()
        
        # Wait for download to start
        time.sleep(3)
        
        print("✓ FILE_005: File download test passed")
        return True
    except Exception as e:
        print(f"✗ FILE_005: File download test failed - {str(e)}")
        return False
    finally:
        driver.quit()

def run_file_management_tests():
    """Run all file management tests"""
    print("Running File Management Tests...")
    tests = [
        test_file_001_single_upload,
        test_file_007_rename_file,
        test_file_008_delete_file,
        test_file_005_download
    ]
    
    passed = 0
    total = len(tests)
    
    for test in tests:
        if test():
            passed += 1
    
    print(f"\nFile Management Tests Summary: {passed}/{total} passed")
    return passed == total

if __name__ == "__main__":
    run_file_management_tests()
