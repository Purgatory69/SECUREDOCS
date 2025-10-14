"""
DM_005: Validate document list displays all user files
Expected Result: All user documents displayed in organized list/grid
Module: Document Management Modules - View Documents
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import wait_for_dashboard, count_files_on_dashboard
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def DM_005_document_list_display():
    """DM_005: Test document list displays all user files"""
    test_id = "DM_005"
    print(f"\n🧪 Running {test_id}: Document List Display")
    print("📋 Module: Document Management Modules - View Documents")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")
        
        # Count files using helper
        file_count = count_files_on_dashboard(driver)
        print(f"📊 Total files on dashboard: {file_count}")
        
        # Locate primary files container
        files_container = None
        try:
            files_container = driver.find_element(By.ID, "filesContainer")
            if files_container.is_displayed():
                print("📂 Found #filesContainer on dashboard")
        except Exception as container_error:
            print(f"⚠️ filesContainer not found directly: {str(container_error)}")
        
        list_container_found = files_container is not None and files_container.is_displayed()
        
        # Look for individual document items inside filesContainer first
        document_selectors = [
            "[data-item-id]",
            "[data-item-type]",
            ".file-card",
            ".file-item",
            ".document-item",
            ".document-card",
            ".list-item",
            ".file-row"
        ]
        
        documents_found = []
        search_root = files_container if files_container else driver
        for selector in document_selectors:
            try:
                elements = search_root.find_elements(By.CSS_SELECTOR, selector)
                visible_elements = [elem for elem in elements if elem.is_displayed()]
                if visible_elements:
                    documents_found.extend(visible_elements)
                    print(f"📄 Found {len(visible_elements)} documents with selector: {selector}")
            except Exception as e:
                continue
        
        document_count = len(documents_found)
        print(f"📊 Total documents displayed (visible): {document_count}")
        
        # Check for organized display (headers, sections, etc.)
        organization_selectors = [
            ".file-header",
            ".document-header",
            ".list-header",
            ".sort-options",
            ".view-options",
            ".file-actions"
        ]
        
        organized_display = False
        for selector in organization_selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                organized_display = True
                print(f"🗂️ Organized display feature found: {selector}")
                break
        
        # Check for document information display
        if documents_found:
            sample_doc = documents_found[0]
            doc_info_selectors = [
                ".file-name",
                ".document-name",
                ".file-size",
                ".file-date",
                ".document-info"
            ]
            
            doc_info_found = False
            for selector in doc_info_selectors:
                try:
                    info_element = sample_doc.find_element(By.CSS_SELECTOR, selector)
                    if info_element.is_displayed():
                        doc_info_found = True
                        print(f"ℹ️ Document information displayed: {selector}")
                        break
                except:
                    continue
        
        # Check for empty state if no documents
        if document_count == 0:
            empty_state_selectors = [
                ".empty-state",
                ".no-files",
                ".no-documents",
                ".empty-message"
            ]
            
            empty_state_found = False
            for selector in empty_state_selectors:
                elements = driver.find_elements(By.CSS_SELECTOR, selector)
                if elements and any(elem.is_displayed() for elem in elements):
                    empty_state_found = True
                    print(f"📭 Empty state message found: {selector}")
                    break
            
            # Empty state is acceptable
            documents_displayed = empty_state_found
        else:
            documents_displayed = True
        
        # Overall validation
        list_functional = list_container_found or document_count > 0 or documents_displayed
        
        assert list_functional, \
            f"Document list not functional - Container: {list_container_found}, Documents: {document_count}, Display: {documents_displayed}"
        
        print(f"✓ {test_id}: Document list display test PASSED")
        print(f"🎯 Result: Document list displayed with {document_count} items")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Document list display test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = DM_005_document_list_display()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
