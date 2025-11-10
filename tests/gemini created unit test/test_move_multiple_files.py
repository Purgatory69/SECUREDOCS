
import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.action_chains import ActionChains

def test_move_multiple_files():
    """
    Test case to create multiple files, a folder, and move the files into the folder.
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

        # --- Create 3 Dummy Files ---
        file_names = [f"File{i}_{int(time.time())}.txt" for i in range(3)]
        for file_name in file_names:
            # Create a dummy file
            with open(file_name, "w") as f:
                f.write("This is a dummy file.")
            
            # Upload the file
            add_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='newBtn']")))
            driver.execute_script("arguments[0].click();", add_button)
            upload_option = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='uploadFileOption']")))
            driver.execute_script("arguments[0].click();", upload_option)
            file_input = WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.XPATH, "//input[@type='file']")))
            file_input.send_keys(os.path.abspath(file_name))
            WebDriverWait(driver, 20).until(EC.invisibility_of_element_located((By.XPATH, "//*[text()='Uploading...']")))
            print(f"File '{file_name}' created successfully.")

        time.sleep(10)
        for file_name in file_names:
            WebDriverWait(driver, 30).until(EC.visibility_of_element_located((By.XPATH, f"//div[@data-item-name='{file_name}']")))


        # --- Create Destination Folder ---
        folder_name = f"DestinationFolder_{int(time.time())}"
        add_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='newBtn']")))
        driver.execute_script("arguments[0].click();", add_button)
        new_folder_option = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='createFolderOption']")))
        new_folder_option.click()
        folder_name_input = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//input[@placeholder='Enter here']")))
        folder_name_input.send_keys(folder_name)
        create_folder_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Create Folder']")))
        create_folder_button.click()
        WebDriverWait(driver, 10).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        print(f"Folder '{folder_name}' created successfully.")
        WebDriverWait(driver, 20).until(EC.visibility_of_element_located((By.XPATH, f"//div[@data-item-name='{folder_name}']")))

        # --- Move Multiple Files into Folder ---
        # Select all files
        for file_name in file_names:
            file_element = WebDriverWait(driver, 20).until(EC.visibility_of_element_located((By.XPATH, f"//div[@data-item-name='{file_name}']")))
            ActionChains(driver).key_down(Keys.CONTROL).click(file_element).key_up(Keys.CONTROL).perform()
            time.sleep(0.5)
        
        move_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//div[@id='selectionToolbar']//button[.//span[text()='Move']]")))
        move_button.click()
        destination_folder = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[contains(@class, 'folder-item')]//span[text()='{folder_name}']")))
        destination_folder.click()
        move_here_button = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[text()='Move Here']")))
        driver.execute_script("arguments[0].click();", move_here_button)
        WebDriverWait(driver, 10).until(EC.invisibility_of_element_located((By.CLASS_NAME, "swal2-container")))
        print(f"Files moved into '{folder_name}'.")

        # --- Verification ---
        # Open the folder
        folder_element = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.XPATH, f"//div[@data-item-name='{folder_name}']")))
        folder_element.click()
        time.sleep(2)

        # Check if all files are present
        for file_name in file_names:
            WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.XPATH, f"//div[@data-item-name='{file_name}']")))
            print(f"Verified: '{file_name}' is in '{folder_name}'.")

        print("Test passed!")

    except Exception as e:
        print(f"Test failed: {e}")
        import traceback
        traceback.print_exc()

    finally:
        if driver:
            driver.quit()
        # Clean up dummy files
        for file_name in file_names:
            if os.path.exists(file_name):
                os.remove(file_name)


if __name__ == "__main__":
    test_move_multiple_files()
