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
    driver = None
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
        add_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='newBtn']")))
        driver.execute_script("arguments[0].click();", add_button)
        new_folder_option = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='createFolderOption']")))
        new_folder_option.click()
        folder_name_input = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//input[@placeholder='Enter here']")))
        folder_name_input.send_keys(folder_a_name)
        create_folder_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Create Folder']")))
        create_folder_button.click()
        WebDriverWait(driver, 10).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        print(f"Folder '{folder_a_name}' created successfully.")

        # --- Create Folder B ---
        folder_b_name = f"FolderB_{int(time.time())}"
        add_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='newBtn']")))
        driver.execute_script("arguments[0].click();", add_button)
        new_folder_option = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='createFolderOption']")))
        new_folder_option.click()
        folder_name_input = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//input[@placeholder='Enter here']")))
        folder_name_input.send_keys(folder_b_name)
        create_folder_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Create Folder']")))
        create_folder_button.click()
        WebDriverWait(driver, 10).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        print(f"Folder '{folder_b_name}' created successfully.")
        driver.refresh()

        # --- Move Folder B into Folder A ---
        folder_b_element = WebDriverWait(driver, 20).until(EC.visibility_of_element_located((By.XPATH, f"//div[@data-item-name='{folder_b_name}']")))
        folder_b_element.click()
        move_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='selectionToolbar']//button[.//span[text()='Move']]")))
        move_button.click()
        destination_folder = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[contains(@class, 'folder-item')]//span[text()='{folder_a_name}']")))
        destination_folder.click()
        move_here_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Move Here']")))
        driver.execute_script("arguments[0].click();", move_here_button)
        WebDriverWait(driver, 10).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        print(f"Folder '{folder_b_name}' moved into '{folder_a_name}'.")

        # --- Delete Folder A ---
        folder_a_element = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[@data-item-name='{folder_a_name}']")))
        folder_a_element.click()
        delete_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='selectionToolbar']//button[.//span[text()='Delete']]")))
        delete_button.click()
        confirm_delete_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Delete']")))
        confirm_delete_button.click()
        WebDriverWait(driver, 10).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        print(f"Folder '{folder_a_name}' deleted successfully.")

        print("Test passed!")

    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

    finally:
        if driver:
            driver.quit()

if __name__ == "__main__":
    test_move_and_delete_folder()