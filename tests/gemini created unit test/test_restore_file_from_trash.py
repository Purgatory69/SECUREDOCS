
import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains

def test_restore_file_from_trash():
    """
    Test case to login, navigate to trash, and restore a file.
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

        # Click on Trash
        trash_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.ID, "trash-link")))
        driver.execute_script("arguments[0].click();", trash_button)
        time.sleep(5)

        # Hover over the file card to reveal the more actions button
        file_card = WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.CLASS_NAME, "file-card")))
        actions = ActionChains(driver)
        actions.move_to_element(file_card).perform()
        time.sleep(2)

        # Get the number of files in the trash before restoring
        initial_file_count = len(driver.find_elements(By.CLASS_NAME, "file-card"))

        # Click on more actions button
        more_actions_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[@aria-label='More actions']")))
        driver.execute_script("arguments[0].click();", more_actions_button)
        time.sleep(2)

        # Click on restore button
        restore_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[@data-action='restore']")))
        driver.execute_script("arguments[0].click();", restore_button)

        # Handle alert
        WebDriverWait(driver, 10).until(EC.alert_is_present())
        alert = driver.switch_to.alert
        alert.accept()

        # Wait for the number of files to be decremented
        WebDriverWait(driver, 10).until(lambda driver: len(driver.find_elements(By.CLASS_NAME, "file-card")) < initial_file_count)

        # Verify file restored successfully
        assert "Item restored successfully." in driver.page_source

        print("Test passed: File restored from trash successfully.")

    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

    finally:
        if 'driver' in locals():
            driver.quit()

if __name__ == "__main__":
    test_restore_file_from_trash()
