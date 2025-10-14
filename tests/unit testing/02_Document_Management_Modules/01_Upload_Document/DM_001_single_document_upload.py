"""
DM_001: Validate single document upload functionality
Expected Result: Document uploaded successfully to user storage
Module: Document Management Modules - Upload Document
Priority: High
Points: 1
"""

import sys
import os
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
from selenium.webdriver.common.by import By
import time
import tempfile

def DM_001_single_document_upload():
    """DM_001: Test single document upload functionality"""
    test_id = "DM_001"
    print(f"\n🧪 Running {test_id}: Single Document Upload")
    print("📋 Module: Document Management Modules - Upload Document")  
    print("🎯 Priority: High | Points: 1")
    
    test_file_path = None
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load using helper
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")
        
        # Create a test document file
        with tempfile.NamedTemporaryFile(mode='w', suffix='.txt', delete=False) as f:
            f.write("This is a test document for upload validation.\nDocument Management Module Test.\nLouiejay Bonghanoy - DM_001")
            test_file_path = f.name
        
        print(f"📄 Created test document: {os.path.basename(test_file_path)}")
        
        # Count existing documents before upload
        initial_count = count_files_on_dashboard(driver)
        print(f"📊 Initial document count: {initial_count}")
        
        # Try to open upload modal (if exists)
        modal_opened = open_upload_modal(driver)
        if modal_opened:
            print("✅ Upload modal opened")
            time.sleep(1)
        
        # Find file input element using helper
        file_input = find_file_input(driver)
        assert file_input is not None, "Could not find document upload input"
        print("📤 Found document upload input")
        
        # Upload the document
        file_input.send_keys(test_file_path)
        print(f"📤 Document selected for upload: {os.path.basename(test_file_path)}")
        
        # Wait for upload to complete using helper
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
        
    except Exception as e:
        print(f"✗ {test_id}: Single document upload test FAILED - {str(e)}")
        return False
        
    finally:
        # Cleanup test file
        if test_file_path and os.path.exists(test_file_path):
            try:
                os.unlink(test_file_path)
                print("🧹 Test document cleaned up")
            except:
                pass

if __name__ == "__main__":
    try:
        result = DM_001_single_document_upload()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
