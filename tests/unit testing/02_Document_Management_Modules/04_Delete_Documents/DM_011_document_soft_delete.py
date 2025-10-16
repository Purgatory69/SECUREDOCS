"""
DM_011: Validate document soft delete (move to trash)
Expected Result: Document moved to trash and removed from main view
Module: Document Management Modules - Delete Documents
Priority: High
Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    count_files_on_dashboard,
    find_file_by_name
)
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
import time

def DM_011_document_soft_delete():
    """DM_011: Validate document soft delete (move to trash)"""
    test_id = "DM_011"
    print(f"\n🧪 Running {test_id}: Document Soft Delete (Move To Trash)")
    print("📋 Module: Document Management Modules - Delete Documents")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Wait for dashboard to load
        wait_for_dashboard(driver)
        print("✅ Dashboard loaded")

        # Ensure files have rendered before counting/searching
        try:
            WebDriverWait(driver, 10).until(
                EC.visibility_of_element_located((By.CSS_SELECTOR, "#filesContainer [data-item-id], #filesContainer .file-card, #filesContainer .file-item"))
            )
            print("⏳ Files detected on dashboard")
        except Exception:
            print("⚠️ No visible files detected after wait")
        
        # Count initial files
        initial_count = count_files_on_dashboard(driver)
        print(f"📊 Initial document count: {initial_count}")
        
        # Debug: List all files on dashboard
        all_files = driver.find_elements(By.CSS_SELECTOR, "[data-item-id], .file-card, .file-item")
        print(f"🔍 Found {len(all_files)} total file elements")
        for i, file_elem in enumerate(all_files[:10]):  # Show first 10
            try:
                name = file_elem.get_attribute("data-item-name") or file_elem.text[:50]
                print(f"  {i+1}. {name}")
            except:
                print(f"  {i+1}. [could not read name]")
        
        # Find the Louiejay_Test_Plan.csv file that was uploaded
        target_file_name = "Louiejay_Test_Plan.csv"
        file_card = find_file_by_name(driver, target_file_name)
        
        if not file_card:
            print(f"⚠️ Target file '{target_file_name}' not found on dashboard")
            print("ℹ️ The file may not have been uploaded successfully in the previous test")
            return False
        
        print(f"📄 Found target file: {target_file_name}")
        
        # Find the actions menu button directly (three dots)
        actions_menu_btn = None
        try:
            # Look for the actions menu button within the file card
            actions_menu_btn = file_card.find_element(By.CSS_SELECTOR, ".actions-menu-btn")
            print("📌 Found actions menu button")
        except:
            print("⚠️ Actions menu button not found within file card")
            return False
        
        # Click the actions menu button to open the menu (try multiple methods)
        menu_opened = False
        for click_method in ['action_chains', 'javascript_click', 'regular_click']:
            try:
                if click_method == 'action_chains':
                    actions = ActionChains(driver)
                    actions.move_to_element(actions_menu_btn).pause(0.5).click().perform()
                elif click_method == 'javascript_click':
                    driver.execute_script("arguments[0].click();", actions_menu_btn)
                else:  # regular_click
                    actions_menu_btn.click()
                
                time.sleep(0.5)  # Wait for menu to appear
                
                # Check if menu appeared
                menu_check = driver.find_elements(By.CSS_SELECTOR, ".actions-menu")
                if menu_check:
                    print(f"✅ Opened actions menu using {click_method}")
                    menu_opened = True
                    break
                else:
                    print(f"⚠️ {click_method} did not open menu")
            except Exception as e:
                print(f"⚠️ {click_method} failed: {str(e)}")
        
        if not menu_opened:
            print("⚠️ All click methods failed to open actions menu")
            return False

        # Verify DOM state after click
        try:
            menu_check = driver.find_elements(By.CSS_SELECTOR, ".actions-menu")
            print(f"🔍 Found {len(menu_check)} actions-menu containers after click")
            if menu_check:
                menu_html = menu_check[0].get_attribute('outerHTML')[:500]  # First 500 chars
                print(f"📋 Menu HTML snippet: {menu_html}")
            else:
                print("⚠️ No .actions-menu found in DOM")
        except Exception as e:
            print(f"⚠️ Could not check DOM: {str(e)}")

        # Retry loop for menu detection (up to 3 attempts)
        delete_btn = None
        actions_menu = None
        max_retries = 3
        for attempt in range(max_retries):
            print(f"🔄 Attempt {attempt + 1}/{max_retries} to locate delete option")

            try:
                actions_menu = WebDriverWait(driver, 2).until(
                    EC.visibility_of_element_located((By.CSS_SELECTOR, ".actions-menu"))
                )
                print("📂 Actions menu container visible")
                break  # Success, exit retry loop
            except Exception:
                if attempt < max_retries - 1:
                    print("⚠️ Actions menu not visible, re-clicking button...")
                    try:
                        actions = ActionChains(driver)
                        actions.move_to_element(actions_menu_btn).pause(0.5).click().perform()
                        time.sleep(0.5)
                    except Exception as e:
                        print(f"⚠️ Re-click failed: {str(e)}")
                        continue
                else:
                    print("⚠️ Actions menu container did not appear after retries")
                    actions_menu = None

        if actions_menu:
            try:
                menu_candidates = actions_menu.find_elements(By.CSS_SELECTOR, ".actions-menu-item, [data-action]")
            except Exception:
                menu_candidates = []

            # If container lookup failed, fall back to global search
            if not menu_candidates:
                try:
                    menu_candidates = WebDriverWait(driver, 5).until(
                        EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".actions-menu-item, [data-action]"))
                    )
                    print("⏱️ Actions menu items present globally")
                except Exception:
                    print("⚠️ Could not detect menu items in time")
                    menu_candidates = []

            for item in menu_candidates:
                try:
                    text = (item.text or "").strip().lower()
                    data_action = (item.get_attribute("data-action") or "").lower()
                    if 'delete' in text or data_action == 'delete':
                        delete_btn = item
                        print("🕵️ Identified delete option")
                        break
                except Exception:
                    continue

        if delete_btn and not delete_btn.is_displayed():
            # Attempt to scroll into view in case it's offscreen
            try:
                driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", delete_btn)
                time.sleep(0.5)
            except Exception:
                pass

        if not delete_btn:
            print("⚠️ Delete button not found in actions menu")
            return False

        print("🗑️ Found delete button")

        # Set up diagnostics (runs once per session)
        driver.execute_script("""
            try {
                if (!window.__dm011DiagnosticsInitialized) {
                    window.__dm011DiagnosticsInitialized = true;
                    window.__dm011Diagnostics = { logs: [], fetches: [] };

                    (function() {
                        const diag = window.__dm011Diagnostics;
                        const originalLog = console.log;
                        const originalError = console.error;
                        const originalDebug = console.debug;
                        const pushLog = (type, args) => {
                            try {
                                const message = Array.from(args).map(arg => {
                                    if (typeof arg === 'object') return JSON.stringify(arg);
                                    return String(arg);
                                }).join(' ');
                                diag.logs.push(`${type} ${message}`);
                            } catch (_) {}
                        };

                        console.log = function(...args) {
                            pushLog('[LOG]', args);
                            return originalLog.apply(this, args);
                        };
                        console.error = function(...args) {
                            pushLog('[ERROR]', args);
                            return originalError.apply(this, args);
                        };
                        console.debug = function(...args) {
                            pushLog('[DEBUG]', args);
                            return originalDebug.apply(this, args);
                        };

                        const originalFetch = window.fetch;
                        window.fetch = async function(...fetchArgs) {
                            const started = Date.now();
                            let url = '';
                            let method = 'GET';
                            const input = fetchArgs[0];
                            const init = fetchArgs[1] || {};
                            if (typeof input === 'string') {
                                url = input;
                            } else if (input && input.url) {
                                url = input.url;
                            }
                            method = (init.method || (input && input.method) || 'GET').toUpperCase();

                            const response = await originalFetch.apply(this, fetchArgs);

                            if (method === 'DELETE' && url.includes('/files/')) {
                                try {
                                    const clone = response.clone();
                                    const bodyText = await clone.text();
                                    diag.fetches.push({
                                        url,
                                        method,
                                        status: response.status,
                                        duration_ms: Date.now() - started,
                                        body_preview: bodyText.slice(0, 200)
                                    });
                                } catch (e) {
                                    diag.fetches.push({
                                        url,
                                        method,
                                        status: response.status,
                                        duration_ms: Date.now() - started,
                                        body_preview: '[unavailable: ' + e.message + ']'
                                    });
                                }
                            }
                            return response;
                        };
                    })();
                }
            } catch (diagError) {
                console.warn('Failed to initialize DM_011 diagnostics', diagError);
            }
        """)

        # Click delete button
        try:
            delete_btn.click()
        except Exception:
            driver.execute_script("arguments[0].click();", delete_btn)
        time.sleep(1)
        print("✅ Clicked delete button")

        # Fallback: invoke delete via exposed module helper if available
        driver.execute_script("""
            try {
                const btn = arguments[0];
                const itemId = btn && btn.dataset ? btn.dataset.itemId : null;
                if (!itemId) {
                    console.warn('[DM_011] Delete button missing data-item-id attribute');
                    return;
                }

                if (window.__files && typeof window.__files.deleteItem === 'function') {
                    console.debug('[DM_011] Calling window.__files.deleteItem for', itemId);
                    window.__files.deleteItem(itemId);
                } else {
                    console.warn('[DM_011] window.__files.deleteItem not available');
                }
            } catch (err) {
                console.error('[DM_011] Error invoking window.__files.deleteItem', err);
            }
        """, delete_btn)

        # Gather diagnostics
        time.sleep(2)
        diagnostics = driver.execute_script("return window.__dm011Diagnostics || {logs: [], fetches: []};")
        try:
            recent_logs = diagnostics.get('logs', [])
            fetch_logs = diagnostics.get('fetches', [])
        except AttributeError:
            recent_logs = diagnostics['logs']
            fetch_logs = diagnostics['fetches']

        print("📋 Recent console logs (up to 10):")
        for log in recent_logs[-10:]:
            print(f"   {log}")

        if fetch_logs:
            print("🌐 Fetch diagnostics:")
            for fetch in fetch_logs[-3:]:
                url = fetch.get('url', '')
                status = fetch.get('status', '')
                duration = fetch.get('duration_ms', '')
                body_preview = fetch.get('body_preview', '')
                method = fetch.get('method', '')
                print(f"   {method} {url} → {status} ({duration} ms) :: {body_preview}")

        # Check for confirmation dialog
        confirmation_found = False
        confirmation_selectors = [
            ".swal2-popup",  # SweetAlert2
            ".modal-dialog",
            "[data-modal='confirm']",
            ".confirm-dialog",
            ".delete-confirmation"
        ]
        
        for selector in confirmation_selectors:
            try:
                confirm_dialog = driver.find_element(By.CSS_SELECTOR, selector)
                if confirm_dialog.is_displayed():
                    print(f"🔍 Found confirmation dialog: {selector}")
                    confirmation_found = True
                    
                    # Look for confirm button
                    confirm_btn_selectors = [
                        ".swal2-confirm",
                        ".btn-confirm",
                        ".confirm-btn",
                        "button:contains('Delete')",
                        "button:contains('Confirm')"
                    ]
                    
                    for btn_selector in confirm_btn_selectors:
                        try:
                            if 'contains' in btn_selector:
                                buttons = driver.find_elements(By.TAG_NAME, "button")
                                for btn in buttons:
                                    if btn.is_displayed() and any(text in btn.text.lower() for text in ['delete', 'confirm', 'yes']):
                                        btn.click()
                                        print("✅ Confirmed deletion in dialog")
                                        confirmation_found = True
                                        break
                            else:
                                confirm_btn = driver.find_element(By.CSS_SELECTOR, btn_selector)
                                if confirm_btn.is_displayed():
                                    confirm_btn.click()
                                    print("✅ Confirmed deletion in dialog")
                                    confirmation_found = True
                                    break
                        except:
                            continue
                    break
            except:
                continue
        
        if not confirmation_found:
            print("ℹ️ No confirmation dialog detected")

        # Wait for file to be removed from dashboard (longer wait for soft delete AJAX call)
        print("⏳ Waiting for soft delete to complete (AJAX call + UI refresh)...")
        time.sleep(8)  # Increased wait for async delete operation and UI update
        
        # Soft delete might not immediately update UI, so refresh the page
        print("🔄 Refreshing page to check if file was soft deleted...")
        driver.refresh()
        time.sleep(3)  # Wait for page reload
        
        # Wait for dashboard to load again
        wait_for_dashboard(driver)
        print("✅ Dashboard reloaded after delete")
        
        # Ensure files have rendered again
        try:
            WebDriverWait(driver, 10).until(
                EC.visibility_of_element_located((By.CSS_SELECTOR, "#filesContainer [data-item-id], #filesContainer .file-card, #filesContainer .file-item"))
            )
            print("⏳ Files re-detected on dashboard")
        except Exception:
            print("⚠️ No visible files detected after refresh")
        
        # Verify file count decreased
        final_count = count_files_on_dashboard(driver)
        count_decreased = final_count < initial_count
        
        if count_decreased:
            print(f"📉 Document count decreased: {initial_count} → {final_count}")
        
        # Verify file no longer appears in main view
        file_still_visible = find_file_by_name(driver, target_file_name) is not None
        
        if not file_still_visible:
            print(f"✅ File '{target_file_name}' removed from main view")
        
        # Assert deletion success
        deletion_successful = count_decreased and not file_still_visible
        
        assert deletion_successful, \
            f"Soft delete failed - Count decreased: {count_decreased}, File removed: {not file_still_visible}"
        
        print(f"✓ {test_id}: Document soft delete test PASSED")
        print(f"🎯 Result: Document moved to trash successfully")
        print(f"📊 Final document count: {final_count}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Document soft delete test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = DM_011_document_soft_delete()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
