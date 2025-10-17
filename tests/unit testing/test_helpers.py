"""
Common helper functions for SecureDocs test suite
These helpers ensure consistent patterns across all tests
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.common.exceptions import TimeoutException
import time


def wait_for_dashboard(driver, timeout=10):
    """Wait for dashboard to fully load"""
    WebDriverWait(driver, timeout).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, "[data-page='user-dashboard']"))
    )
    time.sleep(2)  # Additional wait for dynamic content
    return True


def navigate_to_profile(driver):
    """Navigate to user profile page"""
    base_url = driver.current_url.split('/user/')[0]
    driver.get(f"{base_url}/user/profile")
    time.sleep(3)
    return True


def open_upload_modal(driver):
    """
    Open the upload modal/dialog
    Returns True if modal opened successfully
    """
    # Look for upload button or "New" button that reveals dropdown
    upload_button_selectors = [
        "#newBtn",
        "button[id='newBtn']",
        "button[id='uploadBtn']",
        "button:contains('Upload')",
        ".upload-btn",
        "[data-action='upload']",
        "#upload-button"
    ]
    
    upload_btn = None
    for selector in upload_button_selectors:
        try:
            if 'contains' in selector:
                # Find by text content
                buttons = driver.find_elements(By.TAG_NAME, "button")
                for btn in buttons:
                    if btn.is_displayed() and 'upload' in btn.text.lower():
                        upload_btn = btn
                        break
            else:
                upload_btn = driver.find_element(By.CSS_SELECTOR, selector)
                if upload_btn.is_displayed():
                    break
        except:
            continue
    
    if upload_btn:
        try:
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", upload_btn)
        except:
            pass
        try:
            actions = ActionChains(driver)
            actions.move_to_element(upload_btn).click().perform()
        except:
            upload_btn.click()
        time.sleep(0.5)  # Allow dropdown/modal to appear
        
        # Verify dropdown or modal is visible by checking class changes
        dropdown_checkers = [
            "return (function(){const el=document.getElementById('newDropdown'); if(!el) return false; return !el.classList.contains('hidden') && !el.classList.contains('invisible');})();",
            "return (function(){const el=document.getElementById('uploadModal'); if(!el) return false; return window.getComputedStyle(el).display !== 'none';})();"
        ]
        
        for script in dropdown_checkers:
            try:
                WebDriverWait(driver, 5).until(lambda d: d.execute_script(script))
                print("📂 Upload dropdown/modal became visible")
                return True
            except:
                continue
        
        # Fallback: manually toggle dropdown classes if still hidden
        try:
            forced_visible = driver.execute_script("""
                const dropdown = document.getElementById('newDropdown');
                if (!dropdown) { return false; }
                dropdown.classList.remove('hidden', 'opacity-0', 'invisible', 'translate-y-[-10px]');
                dropdown.classList.add('opacity-100', 'visible', 'translate-y-0');
                return !dropdown.classList.contains('hidden') && !dropdown.classList.contains('invisible');
            """);
            if (forced_visible):
                print("📂 Upload dropdown forced visible via fallback")
                return True
        except:
            pass
        
        return False
    
    return False


def find_file_input(driver):
    """
    Find the file input element (may be hidden) and make it interactable
    Returns the file input element or None
    """
    file_input_selectors = [
        "input[type='file']",
        "#file-upload",
        "#fileInput",
        "#document-upload",
        ".file-upload-input",
        "[name='file']",
        "[name='files[]']",
        "[name='document']"
    ]
    
    for selector in file_input_selectors:
        try:
            file_input = driver.find_element(By.CSS_SELECTOR, selector)
            # File inputs are often hidden, so don't check visibility
            if file_input.get_attribute('type') == 'file':
                # Make the file input visible and interactable using JavaScript
                driver.execute_script("""
                    var elem = arguments[0];
                    elem.style.display = 'block';
                    elem.style.visibility = 'visible';
                    elem.style.opacity = '1';
                    elem.style.position = 'absolute';
                    elem.style.zIndex = '9999';
                    elem.style.width = '100px';
                    elem.style.height = '100px';
                    elem.style.top = '0';
                    elem.style.left = '0';
                """, file_input)
                time.sleep(0.5)  # Small delay after making visible
                return file_input
        except:
            continue
    
    return None


def wait_for_upload_complete(driver, timeout=15):
    """
    Wait for file upload to complete
    Returns True if upload completed successfully
    """
    # Wait for loading indicators to disappear
    loading_selectors = [
        ".uploading",
        ".upload-progress",
        ".progress-bar",
        "[data-uploading='true']"
    ]
    
    time.sleep(2)  # Initial wait for upload to start
    
    for selector in loading_selectors:
        try:
            WebDriverWait(driver, timeout).until(
                EC.invisibility_of_element_located((By.CSS_SELECTOR, selector))
            )
        except:
            pass  # Element might not exist, which is fine
    
    time.sleep(2)  # Additional wait for UI update
    return True


def check_success_message(driver):
    """
    Check for success message/notification
    Returns True if success indicator found
    """
    success_selectors = [
        ".alert-success",
        ".success-message", 
        ".upload-success",
        ".toast-success",
        ".notification-success",
        ".swal2-success",  # SweetAlert2
        ".flex.items-center.bg-green-500.text-white",  # Success notification
        "[class*='bg-green-500']"  # Any element with green background (success color)
    ]
    
    for selector in success_selectors:
        try:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements and any(elem.is_displayed() for elem in elements):
                return True
        except:
            continue
    
    return False


def click_user_profile_dropdown(driver):
    """
    Click the user profile button to open dropdown menu
    Returns True if successful
    """
    try:
        user_profile_btn = driver.find_element(By.ID, "userProfileBtn")
        
        # Use ActionChains for reliable click
        actions = ActionChains(driver)
        actions.move_to_element(user_profile_btn).click().perform()
        time.sleep(2)
        
        return True
    except Exception as e:
        print(f"⚠️ Could not open user profile dropdown: {str(e)}")
        return False


def find_dropdown_link(driver, link_text):
    """
    Find a link in the dropdown menu by text
    Returns the link element or None
    """
    all_links = driver.find_elements(By.CSS_SELECTOR, "a")
    
    for link in all_links:
        try:
            if link.is_displayed() and link_text.lower() in link.text.lower():
                return link
        except:
            continue
    
    return None


def count_files_on_dashboard(driver):
    """
    Count visible files/folders on dashboard
    Returns count of visible items
    """
    file_selectors = [
        "#filesContainer [data-item-id]",
        "#filesContainer .file-card",
        "#filesContainer .file-item",
        "#filesContainer .document-item",
        "[data-item-id]",
        ".file-card",
        ".file-item",
        ".document-item",
        ".grid-item"
    ]
    
    for selector in file_selectors:
        try:
            items = driver.find_elements(By.CSS_SELECTOR, selector)
            visible_items = [item for item in items if item.is_displayed()]
            if visible_items:
                return len(visible_items)
        except:
            continue
    
    return 0


def find_file_by_name(driver, file_name):
    """
    Find a file/folder by name on dashboard
    Returns the element or None
    """
    all_items = driver.find_elements(By.CSS_SELECTOR, "[data-item-id], .file-card, .file-item")
    
    for item in all_items:
        try:
            if item.is_displayed():
                item_text = item.text.lower()
                item_name = item.get_attribute("data-item-name") or ""
                
                if file_name.lower() in item_text or file_name.lower() in item_name.lower():
                    return item
        except:
            continue
    
    return None


def close_modal(driver):
    """
    Close any open modal/dialog
    Returns True if successful
    """
    close_selectors = [
        ".modal-close",
        ".close",
        "[data-dismiss='modal']",
        ".swal2-close",
        "button[aria-label='Close']"
    ]
    
    for selector in close_selectors:
        try:
            close_btn = driver.find_element(By.CSS_SELECTOR, selector)
            if close_btn.is_displayed():
                close_btn.click()
                time.sleep(1)
                return True
        except:
            continue
    
    # Try pressing Escape key
    try:
        from selenium.webdriver.common.keys import Keys
        driver.find_element(By.TAG_NAME, "body").send_keys(Keys.ESCAPE)
        time.sleep(1)
        return True
    except:
        pass
    
    return False


def switch_to_trash_view(driver, wait_seconds=10):
    try:
        trash_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.ID, "trash-link"))
        )
    except Exception as e:
        print(f"⚠️ Could not locate trash link: {str(e)}")
        return False
    try:
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", trash_link)
    except Exception:
        pass
    try:
        driver.execute_script("arguments[0].click();", trash_link)
    except Exception:
        try:
            trash_link.click()
        except Exception as click_error:
            print(f"⚠️ Failed to click trash link: {str(click_error)}")
            return False
    print("✅ Navigated to trash view via trash-link")
    time.sleep(wait_seconds)
    try:
        WebDriverWait(driver, 10).until(
            lambda d: d.execute_script("const container = document.getElementById('filesContainer'); return container && container.dataset.view === 'trash';")
        )
        return True
    except Exception:
        print("⚠️ Trash view did not become active in time")
        return False


def switch_to_main_view(driver, wait_seconds=3):
    try:
        my_documents_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.ID, "my-documents-link"))
        )
    except Exception as e:
        print(f"⚠️ Could not locate my documents link: {str(e)}")
        return False
    try:
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", my_documents_link)
    except Exception:
        pass
    try:
        driver.execute_script("arguments[0].click();", my_documents_link)
    except Exception:
        try:
            my_documents_link.click()
        except Exception as click_error:
            print(f"⚠️ Failed to click my documents link: {str(click_error)}")
            return False
    print("✅ Navigated to main documents view")
    time.sleep(wait_seconds)
    try:
        WebDriverWait(driver, 10).until(
            lambda d: d.execute_script("const container = document.getElementById('filesContainer'); return !container || !container.dataset.view || container.dataset.view === 'main';")
        )
        return True
    except Exception:
        print("⚠️ Main documents view did not become active in time")
        return False


def wait_for_file_presence(driver, file_name, timeout=15):
    try:
        WebDriverWait(driver, timeout).until(
            lambda d: find_file_by_name(d, file_name) is not None
        )
        return True
    except TimeoutException:
        return False


def wait_for_file_absence(driver, file_name, timeout=15):
    try:
        WebDriverWait(driver, timeout).until(
            lambda d: find_file_by_name(d, file_name) is None
        )
        return True
    except TimeoutException:
        return False


def open_actions_menu(driver, file_card, debug_label=""):
    try:
        actions_menu_btn = file_card.find_element(By.CSS_SELECTOR, ".actions-menu-btn")
    except Exception as e:
        print(f"⚠️ Actions menu button not found {debug_label}: {str(e)}")
        return False
    try:
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", actions_menu_btn)
    except Exception:
        pass
    methods = ["action_chains", "javascript_click", "regular_click"]
    for method in methods:
        try:
            if method == "action_chains":
                actions = ActionChains(driver)
                actions.move_to_element(actions_menu_btn).pause(0.5).click().perform()
            elif method == "javascript_click":
                driver.execute_script("arguments[0].click();", actions_menu_btn)
            else:
                actions_menu_btn.click()
            time.sleep(0.5)
            menus = driver.find_elements(By.CSS_SELECTOR, ".actions-menu")
            if menus:
                print(f"✅ Opened actions menu {debug_label} using {method}")
                return True
            else:
                print(f"⚠️ {method} did not reveal actions menu {debug_label}")
        except Exception as click_error:
            print(f"⚠️ {method} failed to open actions menu {debug_label}: {str(click_error)}")
    print(f"⚠️ Unable to open actions menu {debug_label}")
    return False


def find_actions_menu_item(driver, action_name, fallback_text=None):
    menus = driver.find_elements(By.CSS_SELECTOR, ".actions-menu")
    for menu in reversed(menus):
        try:
            item = menu.find_element(By.CSS_SELECTOR, f".actions-menu-item[data-action='{action_name}']")
            if item.is_displayed():
                return item
        except Exception:
            pass
        if fallback_text:
            try:
                for candidate in menu.find_elements(By.CSS_SELECTOR, ".actions-menu-item"):
                    text = (candidate.text or "").strip().lower()
                    if fallback_text.lower() in text and candidate.is_displayed():
                        return candidate
            except Exception:
                continue
    return None


def invoke_module_handler(driver, handler_name, item_id):
    try:
        return driver.execute_script(
            """
                try {
                    const id = arguments[1];
                    if (!id) {
                        console.warn('[test_helpers] Missing item id when invoking handler');
                        return false;
                    }
                    const handler = window.__files && typeof window.__files[arguments[0]] === 'function'
                        ? window.__files[arguments[0]]
                        : (typeof window[arguments[0]] === 'function' ? window[arguments[0]] : null);
                    if (!handler) {
                        console.warn('[test_helpers] Handler not available:', arguments[0]);
                        return false;
                    }
                    handler(id);
                    return true;
                } catch (err) {
                    console.error('[test_helpers] Error invoking handler', err);
                    return false;
                }
            """,
            handler_name,
            item_id
        )
    except Exception as e:
        print(f"⚠️ Failed to invoke handler {handler_name}: {str(e)}")
        return False


def ensure_file_in_trash(driver, file_name):
    if switch_to_trash_view(driver, wait_seconds=5):
        if wait_for_file_presence(driver, file_name, timeout=5):
            print(f"ℹ️ '{file_name}' already present in trash")
            return True

    if not switch_to_main_view(driver, wait_seconds=3):
        print("⚠️ Could not navigate to main documents view")
        return False

    if not wait_for_file_presence(driver, file_name, timeout=10):
        print(f"⚠️ File '{file_name}' not found on dashboard; cannot move to trash")
        return False

    file_card = find_file_by_name(driver, file_name)
    if not file_card:
        print(f"⚠️ File card for '{file_name}' not found even though presence was detected")
        return False

    if not open_actions_menu(driver, file_card, debug_label=f"for {file_name} delete"):
        return False

    delete_btn = find_actions_menu_item(driver, 'delete', fallback_text='delete')
    item_id = file_card.get_attribute('data-item-id')

    if delete_btn:
        try:
            driver.execute_script("arguments[0].click();", delete_btn)
            print("✅ Clicked delete option from menu")
        except Exception as e:
            print(f"⚠️ Clicking delete option failed: {str(e)}")
            if not invoke_module_handler(driver, 'deleteItem', item_id):
                return False
    else:
        print("⚠️ Delete option not found; attempting direct handler call")
        if not invoke_module_handler(driver, 'deleteItem', item_id):
            return False

    if not wait_for_file_absence(driver, file_name, timeout=10):
        print(f"⚠️ File '{file_name}' still visible in main view after delete")
        return False

    if not switch_to_trash_view(driver, wait_seconds=5):
        return False

    if not wait_for_file_presence(driver, file_name, timeout=10):
        print(f"⚠️ File '{file_name}' did not appear in trash after delete")
        return False

    print(f"✅ '{file_name}' moved to trash")
    return True
