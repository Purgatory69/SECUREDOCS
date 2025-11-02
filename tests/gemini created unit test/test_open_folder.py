
import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def test_open_folder():
    """
    Test case to login, create a folder, and then open it.
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
        folder_name = f"TestFolder_{int(time.time())}"
        folder_name_input.send_keys(folder_name)
        time.sleep(1)

        # Click "Create" button
        create_folder_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Create Folder']")))
        create_folder_button.click()
        time.sleep(5) # Wait for folder to be created and page to update

        print(f"Folder '{folder_name}' created successfully.")

        # --- Open Folder ---
        # Find and click the newly created folder to select it
        folder_element = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[@data-item-name='{folder_name}']")))
        folder_element.click()
        time.sleep(1)

        # Click the "Open" button
        time.sleep(1) # allow time for toolbar to appear
        open_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='selectionToolbar']//button[.//span[text()='Open']]")))
        open_button.click()
        time.sleep(3) # Wait for folder contents to load

        # Verify that we are inside the folder by checking for the folder name in the breadcrumb
        breadcrumb_element = WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.XPATH, f"//a[contains(., '{folder_name}')]")))
        assert breadcrumb_element.is_displayed()

        print(f"Successfully opened folder '{folder_name}'.")

    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

    finally:
        if 'driver' in locals():
            driver.quit()

if __name__ == "__main__":
    test_open_folder()
