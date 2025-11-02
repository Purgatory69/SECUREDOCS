
import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def test_move_and_delete_folder():
    """
    Test case to create two folders, move one into the other, and then delete the parent folder.
    """
    try:
        # Setup WebDriver
        wdm_path = ChromeDriverManager().install()
        driver_path = os.path.join(os.path.dirname(wdm_path), "chromedriver.exe")
        driver = webdriver.Chrome(service=ChromeService(driver_path))
        driver.get("https://securedocs.live")
        driver.maximize_window()
        time.sleep(2)

        # Login
        WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//a[@href='/login']"))).click()
        WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "email"))).send_keys("louiejaybonghanoy43@gmail.com")
        WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "password"))).send_keys("Star183795!")
        driver.find_element(By.XPATH, "//button[text()='LOGIN']").click()
        WebDriverWait(driver, 10).until(EC.url_contains("/dashboard"))

        # --- Create Folder A ---
        folder_a_name = f"FolderA_{int(time.time())}"
        create_folder(driver, folder_a_name)
        print(f"Folder '{folder_a_name}' created successfully.")

        # --- Create Folder B ---
        folder_b_name = f"FolderB_{int(time.time())}"
        create_folder(driver, folder_b_name)
        print(f"Folder '{folder_b_name}' created successfully.")

        # --- Move Folder B into Folder A ---
        # Select Folder B
        folder_b_element = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[@data-item-name='{folder_b_name}']")))
        folder_b_element.click()
        time.sleep(1)

        # Click the "Move" button
        move_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='selectionToolbar']//button[.//span[text()='Move']]")))
        move_button.click()
        time.sleep(1)

        # Select Folder A as the destination
        destination_folder = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[contains(@class, 'folder-item')]//span[text()='{folder_a_name}']")))
        destination_folder.click()
        time.sleep(1)

        # Click the "Move Here" button
        move_here_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Move Here']")))
        move_here_button.click()
        time.sleep(3)
        print(f"Folder '{folder_b_name}' moved into '{folder_a_name}'.")

        # --- Delete Folder A ---
        # Select Folder A
        folder_a_element = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[@data-item-name='{folder_a_name}']")))
        folder_a_element.click()
        time.sleep(1)

        # Click the "Delete" button
        delete_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='selectionToolbar']//button[.//span[text()='Delete']]")))
        delete_button.click()
        time.sleep(1)

        # Confirm the deletion
        confirm_delete_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Delete']")))
        confirm_delete_button.click()
        time.sleep(3)
        print(f"Folder '{folder_a_name}' deleted successfully.")

    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

    finally:
        if 'driver' in locals():
            driver.quit()

def create_folder(driver, folder_name):
    # Click the "Add" button to show the dropdown
    add_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='newBtn']")))
    add_button.click()
    time.sleep(1)

    # Click the "New Folder" option from the dropdown
    new_folder_option = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='createFolderOption']")))
    new_folder_option.click()
    time.sleep(1)

    # Enter folder name
    folder_name_input = WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.XPATH, "//input[@placeholder='Enter here']")))
    folder_name_input.send_keys(folder_name)
    time.sleep(1)

    # Click "Create" button
    create_folder_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Create Folder']")))
    create_folder_button.click()
    time.sleep(5) # Wait for folder to be created and page to update

if __name__ == "__main__":
    test_move_and_delete_folder()
