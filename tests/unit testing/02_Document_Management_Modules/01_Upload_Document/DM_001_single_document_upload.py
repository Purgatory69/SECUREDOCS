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
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
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
        
        # Create a test document file
        with tempfile.NamedTemporaryFile(mode='w', suffix='.txt', delete=False) as f:
            f.write("This is a test document for upload validation.\nDocument Management Module Test.\nLouiejay Bonghanoy - UP_001")
            test_file_path = f.name
        
        print(f"📄 Created test document: {os.path.basename(test_file_path)}")
        
        # Count existing documents before upload
        initial_docs = driver.find_elements(By.CSS_SELECTOR, ".file-card, .file-item, .document-item, .list-item")
        initial_count = len([doc for doc in initial_docs if doc.is_displayed()])
        print(f"📊 Initial document count: {initial_count}")
        
        # Find upload input element
        upload_selectors = [
            "input[type='file']",
            "#file-upload",
            "#document-upload",
            ".file-upload-input",
            "[name='file']",
            "[name='files[]']",
            "[name='document']"
        ]
        
        file_input = None
        for selector in upload_selectors:
            try:
                file_input = driver.find_element(By.CSS_SELECTOR, selector)
                if file_input.is_displayed() or file_input.get_attribute('type') == 'file':
                    break
            except:
                continue
        
        assert file_input is not None, "Could not find document upload input"
        print("📤 Found document upload input")
        
        # Upload the document
        file_input.send_keys(test_file_path)
        print(f"📤 Document selected for upload: {os.path.basename(test_file_path)}")
        
        # Wait for upload to process
        time.sleep(5)
        
        # Check for upload success indicators
        success_selectors = [
            ".alert-success",
            ".success-message",
            ".upload-success", 
            ".toast-success",
            ".notification-success"
        ]
        
        upload_success = False
        for selector in success_selectors:
            success_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if success_elements and any(elem.is_displayed() for elem in success_elements):
                upload_success = True
                print(f"✅ Upload success indicator found: {selector}")
                break
        
        # Check if document count increased
        final_docs = driver.find_elements(By.CSS_SELECTOR, ".file-card, .file-item, .document-item, .list-item")
        final_count = len([doc for doc in final_docs if doc.is_displayed()])
        count_increased = final_count > initial_count
        
        if count_increased:
            print(f"📈 Document count increased: {initial_count} → {final_count}")
        
        # Check if the uploaded document appears in the list
        file_name = os.path.basename(test_file_path)
        document_found = False
        
        for doc_element in final_docs:
            if doc_element.is_displayed():
                doc_text = doc_element.text.lower()
                if file_name.lower() in doc_text or "test" in doc_text:
                    document_found = True
                    print(f"🎯 Uploaded document found in list")
                    break
        
        # Check for upload progress indicators (may still be visible)
        progress_selectors = [
            ".upload-progress",
            ".progress-bar",
            ".uploading"
        ]
        
        upload_in_progress = False
        for selector in progress_selectors:
            progress_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if progress_elements and any(elem.is_displayed() for elem in progress_elements):
                upload_in_progress = True
                print("⏳ Upload still in progress, waiting...")
                time.sleep(3)  # Wait a bit more
                break
        
        # Re-check after waiting if upload was in progress
        if upload_in_progress:
            final_docs = driver.find_elements(By.CSS_SELECTOR, ".file-card, .file-item, .document-item, .list-item")
            final_count = len([doc for doc in final_docs if doc.is_displayed()])
            count_increased = final_count > initial_count
        
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
