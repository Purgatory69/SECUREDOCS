"""
FILE_001: Validate single file upload functionality
Expected Result: File uploaded successfully to current folder
"""

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
import os
import tempfile

def test_case_single_file_upload():
    """FILE_001: Test single file upload"""
    test_id = "FILE_001"
    print(f"\n🧪 Running {test_id}: Single File Upload")
    
    test_file_path = None
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Create a temporary test file
        with tempfile.NamedTemporaryFile(mode='w', suffix='.txt', delete=False) as f:
            f.write("This is a test file for upload automation testing.")
            test_file_path = f.name
        
        print(f"📄 Created test file: {test_file_path}")
        
        # Count files before upload
        initial_files = driver.find_elements(By.CSS_SELECTOR, ".file-card, .file-item, .list-item")
        initial_count = len(initial_files)
        print(f"📊 Initial file count: {initial_count}")
        
        # Find file upload input
        upload_selectors = [
            "input[type='file']",
            "#file-upload",
            ".file-upload-input",
            "[name='file']",
            "[name='files[]']"
        ]
        
        file_input = None
        for selector in upload_selectors:
            try:
                file_input = driver.find_element(By.CSS_SELECTOR, selector)
                break
            except:
                continue
        
        assert file_input is not None, "Could not find file upload input"
        
        # Upload the file
        file_input.send_keys(test_file_path)
        print("📤 File selected for upload")
        
        # Wait for upload to process
        time.sleep(5)
        
        # Check if file count increased or if upload success indicator appears
        final_files = driver.find_elements(By.CSS_SELECTOR, ".file-card, .file-item, .list-item")
        final_count = len(final_files)
        
        # Look for success indicators
        success_selectors = [
            ".alert-success",
            ".success-message", 
            ".upload-success",
            ".toast-success"
        ]
        
        upload_success = False
        for selector in success_selectors:
            success_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if success_elements and any(elem.is_displayed() for elem in success_elements):
                upload_success = True
                break
        
        # Check if new file appears in the file list
        file_name = os.path.basename(test_file_path)
        file_found = False
        
        for file_element in final_files:
            if file_name in file_element.text or "test" in file_element.text.lower():
                file_found = True
                break
        
        # Assert success (either count increased, success message, or file found)
        assert (final_count > initial_count) or upload_success or file_found, \
            f"Upload failed - Count: {initial_count} -> {final_count}, Success msg: {upload_success}, File found: {file_found}"
        
        print(f"✓ {test_id}: Single file upload test PASSED")
        print(f"📊 Final file count: {final_count}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Single file upload test FAILED - {str(e)}")
        return False
        
    finally:
        # Cleanup test file
        if test_file_path and os.path.exists(test_file_path):
            try:
                os.unlink(test_file_path)
                print("🧹 Test file cleaned up")
            except:
                pass

if __name__ == "__main__":
    try:
        result = test_case_single_file_upload()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
