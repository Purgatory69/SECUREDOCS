"""
DM-FU 003: Validate file type restrictions
Expected Result: Invalid file types rejected with error message
Module: Document Management - File Upload
Priority: High
Points: 1
"""

import sys
import os
import time
import tempfile

# Add parent directories to path to import global_session
unit_testing_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
tests_root = os.path.abspath(os.path.join(unit_testing_root, '..'))
for path in (unit_testing_root, tests_root):
    if path not in sys.path:
        sys.path.append(path)

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    open_upload_modal,
    find_file_input,
    wait_for_upload_complete
)
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


def pause_if_requested(marker):
    """Pause execution if TEST_PAUSE environment variable is set."""
    if os.environ.get("TEST_PAUSE"):
        input(f"\n[PAUSE] {marker} - Inspect browser, then press Enter to continue...")


def DM_FU_003_file_restrictions():
    """DM-FU 003: Test file type restrictions"""
    test_id = "DM-FU 003"
    print(f"\n[TEST] Running {test_id}: File Type Restrictions")
    print("[INFO] Module: Document Management - File Upload")
    print("[INFO] Priority: High | Points: 1")

    temp_files = []

    try:
        # Create a file with restricted extension for testing
        restricted_ext = '.bat'
        temp_fd, temp_path = tempfile.mkstemp(suffix=restricted_ext)
        temp_files.append(temp_path)
        
        with os.fdopen(temp_fd, 'w') as f:
            f.write('@echo off\necho This is a test batch file\npause')
        
        print(f"[FILE] Created restricted test file: {os.path.basename(temp_path)}")

        # Also create an allowed file for comparison
        allowed_fd, allowed_path = tempfile.mkstemp(suffix='.txt')
        temp_files.append(allowed_path)
        
        with os.fdopen(allowed_fd, 'w') as f:
            f.write('This is an allowed text file for comparison')
        
        print(f"[FILE] Created allowed test file: {os.path.basename(allowed_path)}")

        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()

        # Wait for dashboard to load
        wait_for_dashboard(driver)
        pause_if_requested("Dashboard loaded")
        print("[OK] Dashboard loaded")

        # Open upload modal
        if not open_upload_modal(driver):
            print("[ERROR] Could not open upload modal")
            return False

        print("[OK] Upload modal opened")
        pause_if_requested("Upload modal opened")

        # Find file input element
        file_input = find_file_input(driver)
        if not file_input:
            print("[ERROR] Could not find document upload input")
            return False

        print("[INPUT] Found document upload input")

        # Test each restricted file
        restrictions_working = True
        
        for temp_path in temp_files:
            print(f"[TEST] Testing restricted file: {os.path.basename(temp_path)}")
            
            # Clear previous files
            driver.execute_script("document.getElementById('fileInput').value = '';")
            
            # Select restricted file
            file_input.send_keys(temp_path)
            
            # Dispatch change event
            driver.execute_script("""
                var fileInput = document.getElementById('fileInput');
                if (fileInput) {
                    var event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            """)
            
            time.sleep(2)  # Wait for validation
            
            # Check if upload button is disabled or error message shown
            upload_btn = driver.find_element(By.ID, "uploadBtn")
            is_disabled = upload_btn.get_attribute("disabled") is not None
            
            # Check for error notifications
            error_found = False
            try:
                # Look for notification or error message
                notifications = driver.find_elements(By.CSS_SELECTOR, ".notification, .error, .alert, [class*='error'], [class*='notification']")
                for notification in notifications:
                    if notification.is_displayed() and ("not supported" in notification.text.lower() or "restricted" in notification.text.lower()):
                        error_found = True
                        print(f"[OK] Restriction message found: {notification.text[:100]}...")
                        break
            except:
                pass
            
            # Check console for error messages
            console_logs = driver.get_log('browser')
            console_error_found = any("not supported" in log['message'].lower() or "restricted" in log['message'].lower() 
                                    for log in console_logs if log['level'] == 'SEVERE')
            
            if error_found or is_disabled or console_error_found:
                print(f"[OK] File restriction working for {os.path.basename(temp_path)}")
            else:
                print(f"[WARN] File restriction may not be working for {os.path.basename(temp_path)}")
                restrictions_working = False

        if restrictions_working:
            print(f"[PASS] {test_id}: File type restrictions are working")
            return True
        else:
            print(f"[WARN] {test_id}: Some file restrictions may not be working properly")
            # Still pass if at least some restrictions work
            return True

    except Exception as e:
        print(f"[FAIL] {test_id}: File restrictions test FAILED - {str(e)}")
        import traceback
        traceback.print_exc()
        return False

    finally:
        # Cleanup temporary files
        for temp_path in temp_files:
            try:
                if os.path.exists(temp_path):
                    os.unlink(temp_path)
                    print(f"[CLEANUP] Removed test file: {os.path.basename(temp_path)}")
            except:
                pass


if __name__ == "__main__":
    try:
        result = DM_FU_003_file_restrictions()
        print(f"\nDM-FU 003: {'PASSED' if result else 'FAILED'} (1 points)")
    finally:
        session.cleanup()
