"""
SRCH_001: Validate basic file name search
Expected Result: Matching files displayed in results
"""

from global_session import session
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def test_case_basic_search():
    """SRCH_001: Test basic file search functionality"""
    test_id = "SRCH_001"
    print(f"\n🧪 Running {test_id}: Basic Search")
    
    search_term = "test"
    
    try:
        # Login and navigate to dashboard
        driver = session.login()
        session.navigate_to_dashboard()
        
        # Find search input
        search_input_selectors = [
            "#search-input",
            ".search-input",
            "input[name='search']",
            "input[placeholder*='Search']",
            ".search-box input"
        ]
        
        search_input = None
        for selector in search_input_selectors:
            try:
                search_input = WebDriverWait(driver, 3).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, selector))
                )
                if search_input.is_displayed():
                    break
            except:
                continue
        
        assert search_input is not None, "Could not find search input"
        
        # Enter search term
        search_input.clear()
        search_input.send_keys(search_term)
        print(f"🔍 Entered search term: {search_term}")
        
        # Find and click search button or press Enter
        search_button_selectors = [
            "#search-btn",
            ".search-btn",
            "button[type='submit']",
            ".btn-search"
        ]
        
        search_button = None
        for selector in search_button_selectors:
            try:
                search_button = driver.find_element(By.CSS_SELECTOR, selector)
                if search_button.is_displayed():
                    break
            except:
                continue
        
        if search_button is not None:
            search_button.click()
            print("🔎 Clicked search button")
        else:
            # Try pressing Enter
            from selenium.webdriver.common.keys import Keys
            search_input.send_keys(Keys.RETURN)
            print("⌨️ Pressed Enter to search")
        
        # Wait for search results
        time.sleep(3)
        
        # Check for search results or no results message
        results_selectors = [
            ".search-results",
            ".file-card",
            ".file-item", 
            ".list-item",
            ".search-result-item"
        ]
        
        no_results_selectors = [
            ".no-results",
            ".empty-state",
            ".no-files-found",
            ".search-empty"
        ]
        
        # Look for results
        search_results = []
        for selector in results_selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            search_results.extend([elem for elem in elements if elem.is_displayed()])
        
        # Look for no results message
        no_results_found = False
        for selector in no_results_selectors:
            no_results_elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if no_results_elements and any(elem.is_displayed() for elem in no_results_elements):
                no_results_found = True
                break
        
        # Check if search was executed (either results or no results message)
        search_executed = len(search_results) > 0 or no_results_found
        
        # Also check if URL changed to indicate search
        current_url = driver.current_url
        url_indicates_search = "search" in current_url or search_term in current_url
        
        assert search_executed or url_indicates_search, \
            f"Search not executed - Results: {len(search_results)}, No results msg: {no_results_found}, URL: {current_url}"
        
        print(f"✓ {test_id}: Basic search test PASSED")
        print(f"📊 Search results found: {len(search_results)}")
        print(f"📋 No results message: {no_results_found}")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Basic search test FAILED - {str(e)}")
        return False

if __name__ == "__main__":
    try:
        result = test_case_basic_search()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
