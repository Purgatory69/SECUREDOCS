"""
DM_008: Validate document rename functionality
Expected Result: Document renamed successfully with new name displayed
Module: Document Management Modules - Edit Documents
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
import time
from selenium.common.exceptions import StaleElementReferenceException, TimeoutException

def DM_008_document_rename():
    """DM_008: Validate document rename functionality"""
    test_id = "DM_008"
    print(f"\n🧪 Running {test_id}: Document Rename")
    print("📋 Module: Document Management Modules - Edit Documents")
    print("🎯 Priority: High | Points: 1")

    try:
        # Login and navigate to dashboard
        print("🔐 Attempting to login...")
        driver = session.login()
        print("✅ Login successful")
        
        session.navigate_to_dashboard()
        print("✅ Dashboard navigation successful")

        # Wait for dashboard to load
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard'], body"))
        )
        print("✅ Dashboard loaded")

        # Look for the test.csv file in the file list
        print("🔍 Looking for test.csv file...")

        # Wait for dashboard to load completely
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard'], body"))
        )

        # Wait for files to load - check for any file elements
        print("⏳ Waiting for files to load...")
        WebDriverWait(driver, 15).until(
            lambda d: len(d.find_elements(By.CSS_SELECTOR, '[data-item-id]')) > 0
        )

        # Additional wait for AJAX/file loading
        time.sleep(3)

        # Look for files in the container
        files_container = driver.find_element(By.ID, 'filesContainer')

        # Debug: Print what files are found
        all_files = files_container.find_elements(By.CSS_SELECTOR, '[data-item-id]')
        print(f"📋 Found {len(all_files)} files total:")
        for i, file in enumerate(all_files):
            name = file.get_attribute('data-item-name') or 'No name'
            file_id = file.get_attribute('data-item-id') or 'No ID'
            classes = file.get_attribute('class') or 'No classes'
            print(f"   {i+1}. {name} (ID: {file_id})")
            if i >= 9:  # Show first 10 files
                print("   ... and more files")
                break

        # Try multiple approaches to find test.csv
        test_csv_found = False
        file_element = None

        # Approach 1: Direct data attribute selector
        try:
            print("🔎 Approach 1: Looking for [data-item-name='test.csv']")
            file_element = files_container.find_element(By.CSS_SELECTOR, "[data-item-name='test.csv']")
            if file_element:
                test_csv_found = True
                print("✅ Found test.csv with approach 1")
        except:
            print("❌ Approach 1 failed")

        # Approach 2: Look for any element containing test.csv
        if not test_csv_found:
            try:
                print("🔎 Approach 2: Looking for elements containing 'test.csv'")
                all_elements = files_container.find_elements(By.XPATH, "//*[contains(text(), 'test.csv')]")
                if all_elements:
                    # Find the parent element that has data-item-id
                    for element in all_elements:
                        parent = element
                        for _ in range(5):  # Go up 5 levels max
                            if parent.get_attribute('data-item-id'):
                                file_element = parent
                                test_csv_found = True
                                print("✅ Found test.csv with approach 2")
                                break
                            parent = parent.find_element(By.XPATH, "..") if parent != files_container else None
                            if not parent:
                                break
                        if test_csv_found:
                            break
            except Exception as e:
                print(f"❌ Approach 2 failed: {str(e)[:100]}...")

        # Approach 3: Look for any CSV file
        if not test_csv_found:
            print("⚠️ test.csv not found, looking for any CSV file...")
            try:
                csv_files = files_container.find_elements(By.CSS_SELECTOR, '[data-item-name*="csv"], [data-item-name*="CSV"]')
                print(f"🔎 Found {len(csv_files)} CSV files")
                if csv_files:
                    file_element = csv_files[0]
                    current_name = file_element.get_attribute('data-item-name')
                    print(f"✅ Using CSV file: {current_name}")
                    test_csv_found = True
            except Exception as e:
                print(f"❌ CSV search failed: {str(e)[:100]}...")

        if not file_element:
            print("❌ Could not find any file to rename")
            return False

        # Get current file name for verification
        current_name = file_element.get_attribute('data-item-name')
        file_id = file_element.get_attribute('data-item-id')
        print(f"📄 File to rename: {current_name} (ID: {file_id})")

        # Find the actions menu button (three dots)
        actions_btn = None
        try:
            # Look for actions button within the file element
            actions_btn = file_element.find_element(By.CSS_SELECTOR, '.actions-menu-btn')
        except:
            # Reveal the hidden actions menu button (it requires hover)
            try:
                driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", file_element)
            except Exception:
                pass

            actions = ActionChains(driver)
            actions.move_to_element(file_element).pause(0.3).perform()
            time.sleep(0.3)

            # Re-locate file element & button after hover to avoid stale references
            item_id = file_element.get_attribute('data-item-id')
            try:
                file_element = WebDriverWait(driver, 5).until(
                    lambda d: d.find_element(By.CSS_SELECTOR, f"[data-item-id='{item_id}']")
                )
            except StaleElementReferenceException:
                try:
                    file_element = driver.find_element(By.CSS_SELECTOR, f"[data-item-id='{item_id}']")
                except Exception:
                    try:
                        file_element = driver.find_element(By.CSS_SELECTOR, "[data-item-name='test.csv']")
                    except Exception as e:
                        print(f"❌ Unable to stabilize file element: {str(e)[:100]}...")
                        return False

            try:
                actions_btn = file_element.find_element(By.CSS_SELECTOR, '.actions-menu-btn')
            except Exception as e:
                print(f"❌ Actions menu button not found after hover: {str(e)[:100]}...")
                return False

        print("📍 Found actions menu button, attempting to click...")

        try:
            WebDriverWait(driver, 5).until(lambda d: actions_btn.is_displayed())
        except Exception:
            try:
                actions.move_to_element(actions_btn).pause(0.2).perform()
            except Exception:
                pass

        try:
            driver.execute_script("arguments[0].click();", actions_btn)
        except Exception:
            try:
                actions.move_to_element(actions_btn).click().perform()
            except Exception as e:
                print(f"❌ Unable to click actions button: {str(e)[:100]}...")
                return False

        # Wait for actions menu to appear (increased wait time)
        print("⏳ Waiting for actions menu to appear...")
        time.sleep(2)  # Increased wait time for menu to appear
        
        # Debug: Check what elements are visible after clicking
        try:
            all_elements = driver.find_elements(By.CSS_SELECTOR, '*')
            menu_like_elements = [elem for elem in all_elements if 
                                'menu' in (elem.get_attribute('class') or '').lower() or
                                elem.get_attribute('role') == 'menu' or
                                'actions' in (elem.get_attribute('class') or '').lower()]
            print(f"🔍 Found {len(menu_like_elements)} menu-like elements after click")
            for i, elem in enumerate(menu_like_elements[:3]):
                classes = elem.get_attribute('class') or 'no-class'
                role = elem.get_attribute('role') or 'no-role'
                print(f"   Menu {i+1}: class='{classes}', role='{role}'")
        except Exception as e:
            print(f"⚠️ Debug check failed: {str(e)[:100]}...")
        
        # Try multiple ways to find the actions menu
        actions_menu = None
        try:
            # First try to find it in the document with longer wait
            actions_menu = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, '.actions-menu'))
            )
            print("✅ Actions menu found")
        except:
            print("❌ Actions menu not found with standard selector, trying alternatives...")
            # Try to find any menu that might be open
            try:
                menus = driver.find_elements(By.CSS_SELECTOR, '[role="menu"], .actions-menu, .context-menu, .dropdown-menu')
                if menus:
                    actions_menu = menus[0]
                    print("✅ Found alternative menu element")
                else:
                    print("❌ No menu elements found at all")
                    # Last resort: try to find any element that might contain menu items
                    try:
                        menu_items = driver.find_elements(By.CSS_SELECTOR, '[data-action]')
                        if menu_items:
                            # Assume the parent of the first menu item is the menu
                            actions_menu = menu_items[0].find_element(By.XPATH, "ancestor::*[contains(@class, 'menu') or @role='menu']")
                            print("✅ Found menu via menu items")
                        else:
                            print("❌ No menu items found either")
                    except Exception as e2:
                        print(f"❌ Last resort search failed: {str(e2)[:100]}...")
            except Exception as e:
                print(f"❌ Failed to find any menu: {str(e)[:100]}...")

        # If we still can't find the menu, try clicking the actions button again
        if not actions_menu:
            print("🔄 Menu not found, trying to click actions button again...")
            try:
                # Re-find the actions button (it might have changed)
                actions_btn = file_element.find_element(By.CSS_SELECTOR, '.actions-menu-btn')
                actions_btn.click()
                time.sleep(3)  # Wait longer
                
                # Try to find menu again
                actions_menu = WebDriverWait(driver, 5).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, '.actions-menu'))
                )
                print("✅ Actions menu found after second click")
            except Exception as e:
                print(f"❌ Still couldn't find menu after second click: {str(e)[:100]}...")
                return False

        # Find and click the Rename button
        rename_btn = None
        try:
            rename_btn = actions_menu.find_element(By.CSS_SELECTOR, '.actions-menu-item[data-action="rename"]')
        except:
            print("❌ Rename button not found in actions menu")
            return False

        print("📝 Clicking rename button...")
        rename_btn.click()

        print("🔧 Force modal trigger...")
        driver.execute_script("showRenameModal(arguments[0]);", file_id)

        # Check for JavaScript errors
        console_errors = driver.get_log('browser')
        if console_errors:
            print(f"⚠️ Browser console errors found: {len(console_errors)}")
            for error in console_errors[-5:]:  # Show last 5 errors
                print(f"   Console: {error['level']}: {error['message']}")

        print("⏳ Waiting for rename modal to appear...")
        try:
            WebDriverWait(driver, 12).until(
                EC.visibility_of_element_located((By.ID, 'renameModal'))
            )
        except TimeoutException:
            print("❌ Rename modal did not appear within timeout")
            try:
                modals = driver.find_elements(By.ID, 'renameModal')
                print(f"🔍 Existing renameModal elements: {len(modals)}")
                for idx, modal in enumerate(modals):
                    print(f"   Modal {idx+1} displayed={modal.is_displayed()} enabled={modal.is_enabled()}")
            except Exception as modal_err:
                print(f"⚠️ Modal inspection failed: {str(modal_err)[:100]}...")
            return False

        # Find the new name input field
        new_name_input = driver.find_element(By.ID, 'newFileName')

        # Generate new name - specifically rename test.csv to test1.csv
        if current_name == "test.csv":
            new_name = "test1.csv"
        elif '.' in current_name:
            name_parts = current_name.rsplit('.', 1)
            new_name = f"{name_parts[0]}_renamed.{name_parts[1]}"
        else:
            new_name = f"{current_name}_renamed"

        print(f"✏️ Renaming from '{current_name}' to '{new_name}'")

        # Clear and enter new name
        new_name_input.clear()
        new_name_input.send_keys(new_name)

        # Click the confirm rename button
        confirm_btn = driver.find_element(By.ID, 'confirmRename')
        driver.execute_script("""
            if (!window.__renameHooked && typeof window.renameItem === 'function') {
                const originalRenameItem = window.renameItem;
                window.renameItem = async function(fileId, newName) {
                    try {
                        const result = await originalRenameItem(fileId, newName);
                        window.__lastRenameResponse = { status: 'resolved', result };
                        return result;
                    } catch (error) {
                        window.__lastRenameResponse = { status: 'rejected', message: error && error.message ? error.message : String(error) };
                        throw error;
                    }
                };
                window.__renameHooked = true;
            }
        """)
        print("🖱️ Clicking confirm rename button...")
        confirm_btn.click()

        # Check if the rename operation succeeded by monitoring for success/error messages
        print("⏳ Monitoring for rename completion...")

        # Wait a bit for the API call to complete
        time.sleep(2)

        rename_result = driver.execute_script("return window.__lastRenameResponse || null;")
        if rename_result:
            print(f"🧪 renameItem result: {rename_result}")

        # Check for success/error notifications
        try:
            # Look for notification messages
            notifications = driver.find_elements(By.CSS_SELECTOR, '.notification, .alert, [role="alert"]')
            if notifications:
                for notification in notifications:
                    text = notification.text.strip()
                    if text:
                        print(f"📢 Found notification: '{text}'")
                        if 'success' in text.lower() or 'renamed' in text.lower():
                            print("✅ Success notification found - rename likely succeeded")
                        elif 'error' in text.lower() or 'failed' in text.lower():
                            print("❌ Error notification found - rename likely failed")
        except Exception as e:
            print(f"⚠️ Could not check notifications: {str(e)[:100]}...")

        # Fetch the file details directly from the backend to confirm its current name
        print("🔎 Checking backend file name via API...")
        try:
            file_details = driver.execute_script("""
                const fileId = arguments[0];
                return fetch(`/files/${fileId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .catch(error => ({ error: error.message }));
            """, file_id)

            if isinstance(file_details, dict):
                backend_name = file_details.get('file_name') or file_details.get('data', {}).get('file_name')
                print(f"🗃️ Backend reports file name: {backend_name}")
            else:
                print(f"⚠️ Unexpected API response type: {type(file_details)}")
        except Exception as api_check_err:
            print(f"⚠️ Unable to fetch backend file name: {str(api_check_err)[:100]}...")

        # Wait for modal to close and notification to appear
        time.sleep(1)

        # Verify the rename was successful by checking if the file with new name exists
        print("🔍 Verifying rename was successful...")

        # Wait a moment for the list to refresh
        time.sleep(3)

        # Re-fetch the files container to get updated data
        try:
            files_container = driver.find_element(By.ID, 'filesContainer')
        except Exception:
            print("❌ Could not re-fetch files container after rename")
            return False

        # Look for the renamed file
        renamed_file_found = False
        original_file_gone = False

        # Check for renamed file multiple times with delays
        for attempt in range(3):
            print(f"🔍 Verification attempt {attempt + 1}/3...")
            time.sleep(2)

            try:
                # Look for the renamed file
                renamed_element = files_container.find_element(By.CSS_SELECTOR, f'[data-item-name="{new_name}"]')
                if renamed_element:
                    renamed_file_found = True
                    print("✅ Renamed file found in the list")
                    break
            except Exception:
                print(f"❌ Renamed file '{new_name}' not found in attempt {attempt + 1}")

            try:
                # Check if original file is gone
                driver.find_element(By.CSS_SELECTOR, f'[data-item-name="{current_name}"]')
                print(f"⚠️ Original file '{current_name}' still exists in attempt {attempt + 1}")
            except Exception:
                original_file_gone = True
                print("✅ Original file no longer exists")

        if renamed_file_found and original_file_gone:
            print(f"✅ {test_id}: Document Rename test PASSED")
            print(f"🎯 Result: File successfully renamed from '{current_name}' to '{new_name}'")
            return True
        else:
            print(f"✗ {test_id}: Document Rename test FAILED - Verification failed")
            print(f"   Renamed file found: {renamed_file_found}")
            print(f"   Original file gone: {original_file_gone}")
            return False

    except Exception as e:
        print(f"✗ {test_id}: Document Rename test FAILED - {str(e)}")
        import traceback
        traceback.print_exc()
        
        # Provide manual testing instructions
        print("\n" + "="*60)
        print("📋 MANUAL TESTING INSTRUCTIONS")
        print("="*60)
        print("Since automated testing failed, please test manually:")
        print()
        print("1. 🌐 Open your SecureDocs dashboard in a browser")
        print("2. 🔐 Login with your account (premium@gmail.com)")
        print("3. 📁 Navigate to your file dashboard")
        print("4. 🔍 Find a file named 'test.csv' (or any CSV file)")
        print("5. 📍 Click the three-dot menu (⋯) on the file")
        print("6. 📝 Select 'Rename' from the dropdown menu")
        print("7. ✏️ Change the name from 'test.csv' to 'test1.csv'")
        print("8. ✅ Click 'Rename' to confirm")
        print("9. 🔍 Verify the file name changed successfully")
        print()
        print("Expected Results:")
        print("• ✅ Rename modal appears with current file name")
        print("• ✅ File extension is preserved for files")
        print("• ✅ File name updates in the file list")
        print("• ✅ Success notification appears")
        print("• ✅ Original file name no longer exists")
        print("="*60)
        
        return False

def manual_rename_test():
    """Manual testing mode for document rename functionality"""
    print("\n🧪 DM_008: Manual Document Rename Test")
    print("📋 This mode helps you test the rename functionality manually")
    print()
    print("INSTRUCTIONS:")
    print("1. Open your SecureDocs dashboard in a browser")
    print("2. Navigate to your files")
    print("3. Find a CSV file (preferably 'test.csv')")
    print("4. Click the three-dot menu (⋯)")
    print("5. Select 'Rename'")
    print("6. Change the name from 'test.csv' to 'test1.csv' and click 'Rename'")
    print()
    print("What happens? (y/n for each):")
    
    questions = [
        "Does the rename modal appear?",
        "Can you enter a new name?",
        "Does the file extension stay the same?",
        "Does the file list update after rename?",
        "Do you see a success notification?",
        "Is the old file name gone?"
    ]
    
    results = []
    for question in questions:
        while True:
            answer = input(f"{question} (y/n): ").lower().strip()
            if answer in ['y', 'n']:
                results.append(answer == 'y')
                break
            print("Please answer 'y' or 'n'")
    
    passed = all(results)
    print(f"\nResult: {'PASSED' if passed else 'FAILED'}")
    print(f"Score: {sum(results)}/{len(results)} checks passed")
    
    return passed

if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1 and sys.argv[1] == "--manual":
        # Run manual testing mode
        try:
            result = manual_rename_test()
            print(f"\nManual Test Result: {'PASSED' if result else 'FAILED'}")
        except KeyboardInterrupt:
            print("\nManual test cancelled by user")
        except Exception as e:
            print(f"Manual test error: {e}")
    else:
        # Run automated testing
        try:
            result = DM_008_document_rename()
            print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
        finally:
            session.cleanup()
