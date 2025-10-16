"""
DM_001: Validate single document upload functionality
Expected Result: Document uploaded successfully to user storage
Module: Document Management Modules - Upload Document
Priority: High
Points: 1
"""

import sys
import os
import time
# Add parent directories to path to import global_session
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    open_upload_modal,
    find_file_input,
    wait_for_upload_complete,
    check_success_message,
    count_files_on_dashboard,
    find_file_by_name
)
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


def pause_if_requested(marker):
    """Pause execution if TEST_PAUSE environment variable is set."""
    if os.environ.get("TEST_PAUSE"):
        input(f"\n[PAUSE] {marker} - Inspect browser, then press Enter to continue...")

def DM_001_single_document_upload():
    """DM_001: Test single document upload functionality"""
    test_id = "DM_001"
    print(f"\n🧪 Running {test_id}: Single Document Upload")
    print("📋 Module: Document Management Modules - Upload Document")  
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load using helper
        wait_for_dashboard(driver)
        pause_if_requested("Dashboard loaded")
        print("✅ Dashboard loaded")
        
        # Use the actual Louiejay_Test_Plan.csv file for upload
        test_file_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', 'Louiejay_Test_Plan.csv'))
        
        if not os.path.exists(test_file_path):
            raise FileNotFoundError(f"Test plan file not found: {test_file_path}")
        
        print(f"📄 Using test document: {os.path.basename(test_file_path)}")
        
        # Count existing documents before upload
        initial_count = count_files_on_dashboard(driver)
        print(f"📊 Initial document count: {initial_count}")
        
        # Try to open upload modal (if exists)
        modal_opened = open_upload_modal(driver)
        if modal_opened:
            print("✅ Upload menu triggered")
            time.sleep(1)
        
        dropdown_visible = False
        try:
            dropdown_element = driver.find_element(By.ID, "newDropdown")
            dropdown_visible = dropdown_element.is_displayed()
            if dropdown_visible:
                print("📂 Upload dropdown visible")
        except Exception as dropdown_error:
            print(f"⚠️ Upload dropdown not detected: {str(dropdown_error)}")
        
        assert dropdown_visible, "Upload dropdown did not appear after clicking New button"
        
        option_selectors = {
            "upload_file": "#uploadFileOption",
            "arweave": "#openClientArweaveBtn",
            "new_folder": "#createFolderOption"
        }
        option_elements = {}
        for key, selector in option_selectors.items():
            try:
                elem = driver.find_element(By.CSS_SELECTOR, selector)
                if elem.is_displayed():
                    option_elements[key] = elem
                    print(f"📌 Option available: {selector}")
            except Exception as option_error:
                print(f"⚠️ Option missing: {selector} ({str(option_error)})")
        
        assert "upload_file" in option_elements, "Upload file option not available in dropdown"
        
        option_elements["upload_file"].click()
        print("📥 Selected New File option")
        pause_if_requested("Upload modal opened")
        time.sleep(1)
        
        # Find file input element using direct selector (don't modify its CSS)
        file_input = driver.find_element(By.ID, "fileInput")
        assert file_input is not None, "Could not find document upload input"
        print("📤 Found document upload input")
        pause_if_requested("File input ready")
        
        # Upload the document
        file_input.send_keys(test_file_path)
        print(f"📤 Document selected for upload: {os.path.basename(test_file_path)}")

        # Dispatch a change event so the upload module processes the file selection
        driver.execute_script("""
            var fileInput = document.getElementById('fileInput');
            if (fileInput) {
                var event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        """)
        print("🔄 Dispatched change event on file input")
        pause_if_requested("File selected and change dispatched")

        # Debug: Check if file input has files after event dispatch
        file_input_files = driver.execute_script("return document.getElementById('fileInput').files.length;")
        print(f"📋 File input has {file_input_files} files")

        # Wait for the upload button to become enabled by the front-end logic
        try:
            WebDriverWait(driver, 10).until(
                lambda d: d.find_element(By.ID, "uploadBtn").is_enabled()
            )
            print("✅ Upload button enabled by front-end")
        except Exception:
            print("⚠️ Upload button did not enable within timeout")

        # Check if upload button is enabled
        upload_btn = driver.find_element(By.ID, "uploadBtn")
        if upload_btn.is_enabled():
            print("✅ Upload button is enabled - ready to upload")
            pause_if_requested("Ready to click upload")
            upload_btn.click()
            print("✅ Clicked Upload button")
            
            # Wait for upload to complete
            wait_for_upload_complete(driver)
            print("⏳ Upload processing complete")
            
            # Check for success message
            upload_success = check_success_message(driver)
            if upload_success:
                print("✅ Upload success message found")
            
            # Check if document count increased
            final_count = count_files_on_dashboard(driver)
            count_increased = final_count > initial_count
            
            if count_increased:
                print(f"📈 Document count increased: {initial_count} → {final_count}")
            
            # Check if the uploaded document appears in the list
            file_name = os.path.basename(test_file_path)
            document_element = find_file_by_name(driver, file_name)
            document_found = document_element is not None
            
            if document_found:
                print(f"🎯 Uploaded document found in list")
            
            # Assert upload success (at least one indicator should be true)
            upload_successful = upload_success or count_increased or document_found
            
            assert upload_successful, \
                f"Document upload failed - Success msg: {upload_success}, Count increased: {count_increased}, Document found: {document_found}"
            
            print(f"✓ {test_id}: Single document upload test PASSED")
            print(f"🎯 Result: Document uploaded successfully")
            print(f"📊 Final document count: {final_count}")
            return True
            
        else:
            print("❌ Upload button is still disabled")
            return False
        
    except Exception as e:
        print(f"✗ {test_id}: Single document upload test FAILED - {str(e)}")
        return False
        
    finally:
        # Don't delete the Louiejay_Test_Plan.csv - it's a permanent test file
        print("📝 Test file (Louiejay_Test_Plan.csv) retained for further testing")

if __name__ == "__main__":
    try:
        result = DM_001_single_document_upload()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
