
import os
import time
import logging
import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, ElementClickInterceptedException

# --- Test Configuration ---
BASE_URL = "https://securedocs.live"
EMAIL = "louiejaybonghanoy43@gmail.com"
PASSWORD = "Star183795!"
LOG_LEVEL = logging.INFO

# --- Logger Setup ---
logging.basicConfig(level=LOG_LEVEL, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# --- Pytest Fixture for WebDriver ---
@pytest.fixture(scope="module")
def driver():
    """
    Sets up and tears down the WebDriver for the test module.
    """
    logger.info("Setting up WebDriver.")
    wdm_path = ChromeDriverManager().install()
    driver_path = os.path.join(os.path.dirname(wdm_path), "chromedriver.exe")
    driver = webdriver.Chrome(service=ChromeService(driver_path))
    driver.get(BASE_URL)
    driver.maximize_window()
    yield driver
    logger.info("Tearing down WebDriver.")
    driver.quit()

# --- Custom Wait Functions ---
def wait_for_element(driver, by, value, timeout=30):
    """
    Waits for an element to be present, visible, and clickable.
    """
    try:
        wait = WebDriverWait(driver, timeout)
        element = wait.until(EC.presence_of_element_located((by, value)))
        element = wait.until(EC.visibility_of_element_located((by, value)))
        element = wait.until(EC.element_to_be_clickable((by, value)))
        return element
    except TimeoutException:
        logger.error(f"Timeout while waiting for element: {by}={value}")
        raise

def js_click(driver, element):
    """
    Clicks an element using JavaScript.
    """
    try:
        driver.execute_script("arguments[0].click();", element)
    except Exception as e:
        logger.error(f"Error while clicking element with JavaScript: {e}")
        raise

# --- Test Case ---
def test_robust_move_multiple_files(driver):
    """
    Test case to create multiple files, a folder, and move the files into the folder.
    """
    try:
        # --- Login ---
        logger.info("Logging in.")
        wait_for_element(driver, By.XPATH, "//a[@href='/login']").click()
        wait_for_element(driver, By.ID, "email").send_keys(EMAIL)
        wait_for_element(driver, By.ID, "password").send_keys(PASSWORD)
        js_click(driver, driver.find_element(By.XPATH, "//button[text()='LOGIN']"))
        WebDriverWait(driver, 30).until(EC.url_contains("/dashboard"))
        logger.info("Login successful.")

        # --- Create 3 Dummy Files ---
        file_names = [f"File{i}_{int(time.time())}.txt" for i in range(3)]
        for file_name in file_names:
            logger.info(f"Creating dummy file: {file_name}")
            with open(file_name, "w") as f:
                f.write("This is a dummy file.")
            
            logger.info(f"Uploading file: {file_name}")
            add_button = wait_for_element(driver, By.XPATH, "//div[@id='newBtn']")
            js_click(driver, add_button)
            upload_option = wait_for_element(driver, By.XPATH, "//div[@id='uploadFileOption']")
            js_click(driver, upload_option)
            file_input = WebDriverWait(driver, 30).until(EC.presence_of_element_located((By.XPATH, "//input[@type='file']")))
            file_input.send_keys(os.path.abspath(file_name))
            WebDriverWait(driver, 60).until(EC.invisibility_of_element_located((By.XPATH, "//*[text()='Uploading...']")))
            logger.info(f"File '{file_name}' uploaded successfully.")

        # --- Verify Files are Visible ---
        for file_name in file_names:
            logger.info(f"Verifying file is visible: {file_name}")
            wait_for_element(driver, By.XPATH, f"//div[@data-item-name='{file_name}']")

        # --- Create Destination Folder ---
        folder_name = f"DestinationFolder_{int(time.time())}"
        logger.info(f"Creating destination folder: {folder_name}")
        add_button = wait_for_element(driver, By.XPATH, "//div[@id='newBtn']")
        js_click(driver, add_button)
        new_folder_option = wait_for_element(driver, By.XPATH, "//div[@id='createFolderOption']")
        js_click(driver, new_folder_option)
        folder_name_input = wait_for_element(driver, By.XPATH, "//input[@placeholder='Enter here']")
        folder_name_input.send_keys(folder_name)
        create_folder_button = wait_for_element(driver, By.XPATH, "//button[text()='Create Folder']")
        js_click(driver, create_folder_button)
        WebDriverWait(driver, 30).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        logger.info(f"Folder '{folder_name}' created successfully.")
        wait_for_element(driver, By.XPATH, f"//div[@data-item-name='{folder_name}']")

        # --- Move Multiple Files into Folder ---
        logger.info("Moving files to destination folder.")
        for file_name in file_names:
            file_element = wait_for_element(driver, By.XPATH, f"//div[@data-item-name='{file_name}']")
            js_click(driver, file_element)
            time.sleep(0.5)
        
        move_button = wait_for_element(driver, By.XPATH, "//div[@id='selectionToolbar']//button[.//span[text()='Move']]")
        js_click(driver, move_button)
        destination_folder = wait_for_element(driver, By.XPATH, f"//div[contains(@class, 'folder-item')]//span[text()='{folder_name}']")
        js_click(driver, destination_folder)
        move_here_button = wait_for_element(driver, By.XPATH, "//button[text()='Move Here']")
        js_click(driver, move_here_button)
        WebDriverWait(driver, 30).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        logger.info("Files moved successfully.")

        # --- Verification ---
        logger.info("Verifying files are in the destination folder.")
        folder_element = wait_for_element(driver, By.XPATH, f"//div[@data-item-name='{folder_name}']")
        js_click(driver, folder_element)
        time.sleep(2)

        for file_name in file_names:
            wait_for_element(driver, By.XPATH, f"//div[@data-item-name='{file_name}']")
            logger.info(f"Verified: '{file_name}' is in '{folder_name}'.")

        logger.info("Test passed!")

    except Exception as e:
        logger.error(f"Test failed: {e}")
        pytest.fail(f"Test failed with exception: {e}")

    finally:
        # --- Clean up dummy files ---
        if 'file_names' in locals():
            for file_name in file_names:
                if os.path.exists(file_name):
                    os.remove(file_name)

if __name__ == "__main__":
    pytest.main([__file__])
