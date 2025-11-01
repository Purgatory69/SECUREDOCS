
import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def test_create_folder():
    """
    Test case to create a new folder.
    """
    try:
        # Setup WebDriver
        wdm_path = ChromeDriverManager().install()
        driver_path = os.path.join(os.path.dirname(wdm_path), "chromedriver.exe")
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
            alert.dismiss()
        except: # No alert present
            pass
        time.sleep(3)

        # Wait for dashboard to load
        WebDriverWait(driver, 10).until(EC.url_contains("/dashboard"))
        print("Successfully navigated to dashboard.")

        # Click on the add button
        add_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//img[@alt='Add']")))
        add_button.click()
        time.sleep(2)

        # Click on the new folder button
        new_folder_text = WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.XPATH, "//*[text()='New Folder']")))
        new_folder_button = new_folder_text.find_element(By.XPATH, "./..")
        driver.execute_script("arguments[0].click();", new_folder_button)
        time.sleep(2)

        # Fill in the folder name
        folder_name_input = WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.XPATH, "//*[text()='Folder Name']/following-sibling::input")))
        folder_name_input.send_keys("My New Folder")
        time.sleep(2)

        # Click on the create folder button
        create_folder_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Create Folder']")))
        create_folder_button.click()
        time.sleep(5)

        # Verify that the folder is created
        assert "Folder created successfully" in driver.page_source

        print("Test passed: Folder created successfully.")

    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

    finally:
        if 'driver' in locals():
            driver.quit()

if __name__ == "__main__":
    test_create_folder()
