from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from webdriver_utils import wait_for_element, wait_for_clickable
import time

# Base URL for the application
BASE_URL = "http://securedocs.live"

def login(driver, email="test@example.com", password="password"):
    """Login function that can be reused across tests"""
    driver.get(f"{BASE_URL}/login")
    
    # Wait for login form to load
    email_field = wait_for_element(driver, By.NAME, "email")
    password_field = wait_for_element(driver, By.NAME, "password")
    
    # Fill in credentials
    email_field.clear()
    email_field.send_keys(email)
    password_field.clear()
    password_field.send_keys(password)
    
    # Click login button
    login_button = wait_for_clickable(driver, By.CSS_SELECTOR, "button[type='submit']")
    login_button.click()
    
    # Wait for redirect to dashboard
    wait_for_element(driver, By.CSS_SELECTOR, "[data-page='user-dashboard']", timeout=10)
    
    return True

def logout(driver):
    """Logout function"""
    # Click user menu dropdown
    user_menu = wait_for_clickable(driver, By.CSS_SELECTOR, ".dropdown-toggle")
    user_menu.click()
    
    # Click logout
    logout_link = wait_for_clickable(driver, By.CSS_SELECTOR, "a[href*='logout']")
    logout_link.click()
    
    # Wait for redirect to login page
    wait_for_element(driver, By.CSS_SELECTOR, "form[action*='login']")
    
    return True

def register_user(driver, name, email, password, confirm_password=None):
    """Register a new user"""
    if confirm_password is None:
        confirm_password = password
        
    driver.get(f"{BASE_URL}/register")
    
    # Fill registration form
    name_field = wait_for_element(driver, By.NAME, "name")
    email_field = wait_for_element(driver, By.NAME, "email")
    password_field = wait_for_element(driver, By.NAME, "password")
    confirm_field = wait_for_element(driver, By.NAME, "password_confirmation")
    
    name_field.send_keys(name)
    email_field.send_keys(email)
    password_field.send_keys(password)
    confirm_field.send_keys(confirm_password)
    
    # Submit form
    register_button = wait_for_clickable(driver, By.CSS_SELECTOR, "button[type='submit']")
    register_button.click()
    
    return True

def navigate_to_dashboard(driver):
    """Navigate to dashboard if not already there"""
    current_url = driver.current_url
    if "dashboard" not in current_url:
        driver.get(f"{BASE_URL}/dashboard")
        wait_for_element(driver, By.CSS_SELECTOR, "[data-page='user-dashboard']")
    
    return True

def create_folder(driver, folder_name):
    """Create a new folder"""
    navigate_to_dashboard(driver)
    
    # Click create folder button
    create_button = wait_for_clickable(driver, By.ID, "create-folder-btn")
    create_button.click()
    
    # Wait for modal and fill folder name
    folder_input = wait_for_element(driver, By.ID, "folder-name-input")
    folder_input.send_keys(folder_name)
    
    # Submit
    submit_button = wait_for_clickable(driver, By.CSS_SELECTOR, "#createFolderModal button[type='submit']")
    submit_button.click()
    
    # Wait for modal to close
    time.sleep(1)
    
    return True

def upload_file(driver, file_path):
    """Upload a file"""
    navigate_to_dashboard(driver)
    
    # Click upload button
    upload_button = wait_for_clickable(driver, By.ID, "upload-btn")
    upload_button.click()
    
    # Select file
    file_input = wait_for_element(driver, By.CSS_SELECTOR, "input[type='file']")
    file_input.send_keys(file_path)
    
    # Wait for upload to complete
    time.sleep(2)
    
    return True

def search_files(driver, search_term):
    """Search for files"""
    navigate_to_dashboard(driver)
    
    # Click search input
    search_input = wait_for_element(driver, By.ID, "search-input")
    search_input.clear()
    search_input.send_keys(search_term)
    
    # Press search button
    search_button = wait_for_clickable(driver, By.ID, "search-btn")
    search_button.click()
    
    # Wait for results
    time.sleep(1)
    
    return True
