import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def test_delete_empty_folder():
    """
    Test case to login, create an empty folder, and then delete it.
    """
    try:
        # Setup WebDriver
        wdm_path = ChromeDriverManager().install()
        driver_path = os.path.join(os.path.dirname(wdm_path), "chromedriver.exe")
        print(f"Constructed ChromeDriver path: {driver_path}")
        driver = webdriver.Chrome(service=ChromeService(driver_path))
        driver.get("https://securedocs.live")
        time.sleep(5)
        driver.maximize_window()
        time.sleep(2)

        # Click login button on homepage
        WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//a[@href='/login']"))).click()
        time.sleep(2)

        # Fill login form
        WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "email"))).send_keys("louiejaybonghanoy43@gmail.com")
        WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "password"))).send_keys("Star183795!")
        time.sleep(1)

        # Click login button
        login_button_in_form = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='LOGIN']")))
        driver.execute_script("arguments[0].click();", login_button_in_form)
        time.sleep(2) # Give some time for the alert to appear

        # Handle Google password safety alert if it appears
        try:
            WebDriverWait(driver, 5).until(EC.alert_is_present())
            alert = driver.switch_to.alert
            print(f"Alert text: {alert.text}")
            alert.dismiss()
            print("Dismissed Google password safety alert.")
        except: # No alert present
            pass
        time.sleep(3)

        # Wait for dashboard to load
        WebDriverWait(driver, 10).until(EC.url_contains("/dashboard"))
        print("Successfully navigated to dashboard.")

        # --- Create Folder ---
        # Click the "Create New" button
        create_new_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.ID, "create-new-button")))
        create_new_button.click()
        time.sleep(1)

        # Click the "New Folder" option
        new_folder_option = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.ID, "new-folder-option")))
        new_folder_option.click()
        time.sleep(1)

        # Enter folder name
        folder_name_input = WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "folder-name-input")))
        folder_name = f"EmptyFolder_{int(time.time())}"
        folder_name_input.send_keys(folder_name)
        time.sleep(1)

        # Click "Create" button
        create_folder_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.ID, "create-folder-button")))
        create_folder_button.click()
        time.sleep(3) # Wait for folder to be created and page to update

        print(f"Folder '{folder_name}' created successfully.")

        # --- Delete Folder ---
        # Find the folder element (assuming it's visible after creation)
        folder_element = WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.XPATH, f"//div[contains(@class, 'folder-item')]//span[text()='{folder_name}']")))

        # Right-click or click a context menu button to reveal delete option
        # This part is highly dependent on the UI. Assuming a context menu with a delete option.
        # For now, let's assume there's a 'delete' button visible or accessible via a click.
        # This XPath is a placeholder and will likely need adjustment.
        delete_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[contains(@class, 'folder-item')]//span[text()='{folder_name}']/ancestor::div[contains(@class, 'folder-item')]//button[@data-action='delete']")))
        delete_button.click()
        time.sleep(1)

        # Confirm deletion (assuming a confirmation dialog with a 'Delete' button)
        confirm_delete_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.ID, "confirm-delete-button")))
        confirm_delete_button.click()
        time.sleep(3) # Wait for deletion to complete and page to update

        # Verify folder is no longer present
        assert not EC.presence_of_element_located((By.XPATH, f"//div[contains(@class, 'folder-item')]//span[text()='{folder_name}']"))(driver)

        print(f"Folder '{folder_name}' deleted successfully.")

    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

    finally:
        if 'driver' in locals():
            driver.quit()

if __name__ == "__main__":
    test_delete_empty_folder()