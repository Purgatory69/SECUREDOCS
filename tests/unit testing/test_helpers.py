"""
Common helper functions for SecureDocs test suite
These helpers ensure consistent patterns across all tests
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
import time


def wait_for_dashboard(driver, timeout=10):
    """Wait for dashboard to fully load"""
    WebDriverWait(driver, timeout).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
    )
    time.sleep(2)  # Additional wait for dynamic content
    return True


def navigate_to_profile(driver):
    """Navigate to user profile page"""
    base_url = driver.current_url.split('/user/')[0]
    driver.get(f"{base_url}/user/profile")
    time.sleep(3)
    return True


def open_upload_modal(driver):
    """
    Open the upload modal/dialog
    Returns True if modal opened successfully
    """
    # Look for upload button
    upload_button_selectors = [
        "button[id='uploadBtn']",
        "button:contains('Upload')",
        ".upload-btn",
        "[data-action='upload']",
        "#upload-button"
    ]
    
    upload_btn = None
    for selector in upload_button_selectors:
        try:
            if 'contains' in selector:
                # Find by text content
                buttons = driver.find_elements(By.TAG_NAME, "button")
                for btn in buttons:
                    if btn.is_displayed() and 'upload' in btn.text.lower():
                        upload_btn = btn
                        break
            else:
                upload_btn = driver.find_element(By.CSS_SELECTOR, selector)
                if upload_btn.is_displayed():
                    break
        except:
            continue
    
    if upload_btn:
        upload_btn.click()
        time.sleep(2)  # Wait for modal animation
        return True
    
    return False


def find_file_input(driver):
    """
    Find the file input element (may be hidden) and make it interactable
    Returns the file input element or None
    """
    file_input_selectors = [
        "input[type='file']",
        "#file-upload",
        "#fileInput",
        "#document-upload",
        ".file-upload-input",
        "[name='file']",
        "[name='files[]']",
        "[name='document']"
    ]
    
    for selector in file_input_selectors:
        try:
            file_input = driver.find_element(By.CSS_SELECTOR, selector)
            # File inputs are often hidden, so don't check visibility
            if file_input.get_attribute('type') == 'file':
                # Make the file input visible and interactable using JavaScript
                driver.execute_script("""
                    var elem = arguments[0];
                    elem.style.display = 'block';
                    elem.style.visibility = 'visible';
                    elem.style.opacity = '1';
                    elem.style.position = 'absolute';
                    elem.style.zIndex = '9999';
                    elem.style.width = '100px';
                    elem.style.height = '100px';
                    elem.style.top = '0';
                    elem.style.left = '0';
                """, file_input)
                time.sleep(0.5)  # Small delay after making visible
                return file_input
        except:
            continue
    
    return None


def wait_for_upload_complete(driver, timeout=15):
    """
    Wait for file upload to complete
    Returns True if upload completed successfully
    """
    # Wait for loading indicators to disappear
    loading_selectors = [
        ".uploading",
        ".upload-progress",
        ".progress-bar",
        "[data-uploading='true']"
    ]
    
    time.sleep(2)  # Initial wait for upload to start
    
    for selector in loading_selectors:
        try:
            WebDriverWait(driver, timeout).until(
                EC.invisibility_of_element_located((By.CSS_SELECTOR, selector))
            )
        except:
            pass  # Element might not exist, which is fine
    
    time.sleep(2)  # Additional wait for UI update
    return True


def check_success_message(driver):
    """
    Check for success message/notification
    Returns True if success indicator found
    """
    success_selectors = [
        ".alert-success",
        ".success-message",
        ".upload-success",
        ".toast-success",
        ".notification-success",
        ".swal2-success"  # SweetAlert2
    ]
    
    for selector in success_selectors:
        try:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                return True
        except:
            continue
    
    return False


def click_user_profile_dropdown(driver):
    """
    Click the user profile button to open dropdown menu
    Returns True if successful
    """
    try:
        user_profile_btn = driver.find_element(By.ID, "userProfileBtn")
        
        # Use ActionChains for reliable click
        actions = ActionChains(driver)
        actions.move_to_element(user_profile_btn).click().perform()
        time.sleep(2)
        
        return True
    except Exception as e:
        print(f"⚠️ Could not open user profile dropdown: {str(e)}")
        return False


def find_dropdown_link(driver, link_text):
    """
    Find a link in the dropdown menu by text
    Returns the link element or None
    """
    all_links = driver.find_elements(By.CSS_SELECTOR, "a")
    
    for link in all_links:
        try:
            if link.is_displayed() and link_text.lower() in link.text.lower():
                return link
        except:
            continue
    
    return None


def count_files_on_dashboard(driver):
    """
    Count visible files/folders on dashboard
    Returns count of visible items
    """
    file_selectors = [
        "[data-item-id]",
        ".file-card",
        ".file-item",
        ".document-item",
        ".grid-item"
    ]
    
    for selector in file_selectors:
        try:
            items = driver.find_elements(By.CSS_SELECTOR, selector)
            visible_items = [item for item in items if item.is_displayed()]
            if visible_items:
                return len(visible_items)
        except:
            continue
    
    return 0


def find_file_by_name(driver, file_name):
    """
    Find a file/folder by name on dashboard
    Returns the element or None
    """
    all_items = driver.find_elements(By.CSS_SELECTOR, "[data-item-id], .file-card, .file-item")
    
    for item in all_items:
        try:
            if item.is_displayed():
                item_text = item.text.lower()
                item_name = item.get_attribute("data-item-name") or ""
                
                if file_name.lower() in item_text or file_name.lower() in item_name.lower():
                    return item
        except:
            continue
    
    return None


def close_modal(driver):
    """
    Close any open modal/dialog
    Returns True if successful
    """
    close_selectors = [
        ".modal-close",
        ".close",
        "[data-dismiss='modal']",
        ".swal2-close",
        "button[aria-label='Close']"
    ]
    
    for selector in close_selectors:
        try:
            close_btn = driver.find_element(By.CSS_SELECTOR, selector)
            if close_btn.is_displayed():
                close_btn.click()
                time.sleep(1)
                return True
        except:
            continue
    
    # Try pressing Escape key
    try:
        from selenium.webdriver.common.keys import Keys
        driver.find_element(By.TAG_NAME, "body").send_keys(Keys.ESCAPE)
        time.sleep(1)
        return True
    except:
        pass
    
    return False
