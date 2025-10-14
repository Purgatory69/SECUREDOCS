# SecureDocs Test Patterns & Standards

This document outlines the standardized patterns for writing tests in the SecureDocs test suite.

## 📋 **Table of Contents**
1. [Import Structure](#import-structure)
2. [Login & Navigation](#login--navigation)
3. [Modal Interactions](#modal-interactions)
4. [File Operations](#file-operations)
5. [Profile & Settings](#profile--settings)
6. [Common Patterns](#common-patterns)

---

## 🔧 **Import Structure**

### Standard Imports
```python
import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    open_upload_modal,
    find_file_input,
    wait_for_upload_complete,
    check_success_message,
    count_files_on_dashboard,
    find_file_by_name,
    navigate_to_profile,
    click_user_profile_dropdown,
    find_dropdown_link
)
from selenium.webdriver.common.by import By
import time
```

---

## 🔐 **Login & Navigation**

### Login to Dashboard
```python
# Login and navigate to dashboard
driver = session.login()
session.navigate_to_dashboard()

# Wait for dashboard to fully load
wait_for_dashboard(driver)
print("✅ Dashboard loaded")
```

### Navigate to Profile
```python
# Navigate to profile page
navigate_to_profile(driver)
print("✅ Navigated to profile page")
```

### Using Profile Dropdown
```python
# Open user profile dropdown
if click_user_profile_dropdown(driver):
    print("✅ Profile dropdown opened")
    
    # Find link in dropdown
    link = find_dropdown_link(driver, "Profile Settings")
    if link:
        link.click()
        time.sleep(2)
```

---

## 📤 **Modal Interactions**

### Opening Upload Modal
```python
# Try to open upload modal
modal_opened = open_upload_modal(driver)
if modal_opened:
    print("✅ Upload modal opened")
    time.sleep(1)
```

### Finding File Input
```python
# Find file input (may be hidden)
file_input = find_file_input(driver)
assert file_input is not None, "Could not find file upload input"
print("📤 Found file upload input")
```

### Uploading Files
```python
# Upload file
file_input.send_keys(file_path)
print(f"📤 File selected: {os.path.basename(file_path)}")

# Wait for upload to complete
wait_for_upload_complete(driver)
print("⏳ Upload complete")

# Check for success
if check_success_message(driver):
    print("✅ Success message displayed")
```

---

## 📁 **File Operations**

### Counting Files
```python
# Count files before operation
initial_count = count_files_on_dashboard(driver)
print(f"📊 Initial count: {initial_count}")

# ... perform operation ...

# Count files after operation
final_count = count_files_on_dashboard(driver)
print(f"📊 Final count: {final_count}")

# Verify change
if final_count > initial_count:
    print(f"📈 Count increased: {initial_count} → {final_count}")
```

### Finding Files by Name
```python
# Find specific file
file_element = find_file_by_name(driver, "test.txt")
if file_element:
    print("🎯 File found on dashboard")
    # Perform actions on file_element
```

---

## 👤 **Profile & Settings**

### Accessing Profile Settings
```python
# Method 1: Direct navigation (recommended)
navigate_to_profile(driver)

# Method 2: Via dropdown
click_user_profile_dropdown(driver)
profile_link = find_dropdown_link(driver, "Profile Settings")
if profile_link:
    profile_link.click()
    time.sleep(3)
```

### Updating Profile Information
```python
# Navigate to profile
navigate_to_profile(driver)

# Find and update name field
name_input = driver.find_element(By.ID, "name")
original_name = name_input.get_attribute("value")

name_input.clear()
name_input.send_keys("New Name")

# Submit form
save_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
save_button.click()
time.sleep(2)

# Verify update
if check_success_message(driver):
    print("✅ Profile updated successfully")
```

---

## 🔄 **Common Patterns**

### Test Structure
```python
def TEST_ID_test_name():
    """TEST_ID: Test description"""
    test_id = "TEST_ID"
    print(f"\n🧪 Running {test_id}: Test Name")
    print("📋 Module: Module Name")
    print("🎯 Priority: High | Points: 1")
    
    try:
        # Setup
        driver = session.login()
        session.navigate_to_dashboard()
        wait_for_dashboard(driver)
        
        # Test logic here
        
        # Assertions
        assert condition, "Error message"
        
        print(f"✓ {test_id}: Test PASSED")
        print(f"🎯 Result: Success message")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: Test FAILED - {str(e)}")
        return False
    
    finally:
        # Cleanup if needed
        pass

if __name__ == "__main__":
    try:
        result = TEST_ID_test_name()
        print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
    finally:
        session.cleanup()
```

### Waiting for Elements
```python
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Wait for element to be present
WebDriverWait(driver, 10).until(
    EC.presence_of_element_located((By.CSS_SELECTOR, ".element"))
)

# Wait for element to be visible
WebDriverWait(driver, 10).until(
    EC.visibility_of_element_located((By.ID, "element-id"))
)

# Wait for element to be clickable
WebDriverWait(driver, 10).until(
    EC.element_to_be_clickable((By.CSS_SELECTOR, "button"))
)
```

### Error Handling
```python
try:
    # Attempt primary method
    element = driver.find_element(By.ID, "primary-id")
except:
    # Fallback method
    try:
        element = driver.find_element(By.CSS_SELECTOR, ".fallback-class")
    except:
        print("⚠️ Element not found with any method")
        element = None
```

---

## ✅ **Best Practices**

1. **Always use helper functions** from `test_helpers.py` when available
2. **Wait for dashboard** after navigation using `wait_for_dashboard()`
3. **Use descriptive print statements** with emojis for better readability
4. **Check multiple indicators** for success (messages, counts, element presence)
5. **Clean up test data** in `finally` blocks
6. **Use ActionChains** for reliable clicks on interactive elements
7. **Add time.sleep()** after modal opens/closes for animations
8. **Handle session reuse** by checking current URL before navigation
9. **Use try-except** for fallback selectors
10. **Return boolean** from test functions for consistent reporting

---

## 🚫 **Common Pitfalls to Avoid**

1. ❌ Don't hardcode URLs - use `driver.current_url` and split/join
2. ❌ Don't assume elements are visible - check `is_displayed()`
3. ❌ Don't use bare `confirm()` - use `window.confirm()`
4. ❌ Don't forget to wait after clicks - modals have animations
5. ❌ Don't rely on single selectors - provide fallbacks
6. ❌ Don't skip cleanup - always use `finally` blocks
7. ❌ Don't ignore session state - check if already on target page
8. ❌ Don't use fixed sleep times > 3 seconds - use WebDriverWait
9. ❌ Don't forget CSRF tokens - ensure proper headers
10. ❌ Don't test in headless mode initially - debug visually first

---

## 📝 **Example: Complete Test**

```python
"""
DM_001: Validate single document upload
Expected Result: Document uploaded successfully
Module: Document Management - Upload
Priority: High | Points: 1
"""

import sys
import os
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..'))

from global_session import session
from test_helpers import (
    wait_for_dashboard,
    open_upload_modal,
    find_file_input,
    wait_for_upload_complete,
    check_success_message,
    count_files_on_dashboard
)
import tempfile

def DM_001_single_upload():
    test_id = "DM_001"
    print(f"\n🧪 Running {test_id}: Single Document Upload")
    
    test_file = None
    try:
        # Login and setup
        driver = session.login()
        session.navigate_to_dashboard()
        wait_for_dashboard(driver)
        
        # Create test file
        with tempfile.NamedTemporaryFile(mode='w', suffix='.txt', delete=False) as f:
            f.write("Test content")
            test_file = f.name
        
        # Count before
        initial_count = count_files_on_dashboard(driver)
        
        # Upload
        open_upload_modal(driver)
        file_input = find_file_input(driver)
        file_input.send_keys(test_file)
        wait_for_upload_complete(driver)
        
        # Verify
        final_count = count_files_on_dashboard(driver)
        success = check_success_message(driver)
        
        assert final_count > initial_count or success, "Upload failed"
        
        print(f"✓ {test_id}: PASSED")
        return True
        
    except Exception as e:
        print(f"✗ {test_id}: FAILED - {str(e)}")
        return False
        
    finally:
        if test_file and os.path.exists(test_file):
            os.unlink(test_file)

if __name__ == "__main__":
    try:
        result = DM_001_single_upload()
    finally:
        session.cleanup()
```

---

## 🔄 **Updating Existing Tests**

When updating old tests to use these patterns:

1. Add `test_helpers` imports
2. Replace manual waits with helper functions
3. Use `wait_for_dashboard()` after navigation
4. Replace custom modal logic with `open_upload_modal()`
5. Use `find_file_input()` instead of manual selector loops
6. Add `wait_for_upload_complete()` after file selection
7. Use `check_success_message()` for verification
8. Replace manual file counting with `count_files_on_dashboard()`
9. Update print statements to match emoji style
10. Ensure proper cleanup in `finally` blocks

---

**Last Updated**: October 14, 2025
**Version**: 1.0
**Author**: Louiejay Test Suite
