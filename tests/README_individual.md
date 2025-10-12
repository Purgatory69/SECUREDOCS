# SecureDocs Individual Test Cases

A modular Selenium-based automation testing suite where each test case is in its own file for easy tracking and management.

## 🔧 Setup

### Prerequisites
- Python 3.7+
- Chrome browser installed
- ChromeDriver (will be installed automatically with webdriver-manager)

### Installation
```bash
pip install -r requirements.txt
```

### Configuration
Update credentials in `global_session.py` if needed:
```python
BASE_URL = "http://securedocs.live"  # Your app URL
test_email = "test@example.com"
test_password = "password"
```

## 🏗️ Architecture

### Global Session Management
- **Single Browser Instance**: All tests share one browser session
- **Persistent Login**: Login once, reuse across all test cases
- **Automatic Cleanup**: Browser closes when tests complete

### Individual Test Cases
Each test case is a separate file following the pattern:
```
test_case_<description>.py
```

## 📁 Test Structure

```
tests/
├── global_session.py              # Shared session manager
├── webdriver_utils.py             # Web driver configuration
├── run_individual_tests.py        # Test runner for individual cases
├── 
├── Authentication Tests:
├── test_case_successful_login.py           # AUTH_001
├── test_case_invalid_email_login.py        # AUTH_002  
├── test_case_invalid_password_login.py     # AUTH_003
├── test_case_empty_fields_login.py         # AUTH_004
├── test_case_user_logout.py                # AUTH_010
├──
├── File Management Tests:
├── test_case_single_file_upload.py         # FILE_001
├──
├── Folder Management Tests:
├── test_case_create_folder.py              # FOLD_001
├──
├── Search Tests:
├── test_case_basic_search.py               # SRCH_001
└──
```

## 🚀 Running Tests

### Run All Tests
```bash
python run_individual_tests.py
```

### Run Test Categories
```bash
python run_individual_tests.py auth        # Authentication tests
python run_individual_tests.py file        # File management tests
python run_individual_tests.py folder      # Folder management tests
python run_individual_tests.py search      # Search tests
```

### Run Specific Test Cases
```bash
python run_individual_tests.py AUTH_001    # Successful login
python run_individual_tests.py FILE_001    # Single file upload
python run_individual_tests.py FOLD_001    # Create folder
python run_individual_tests.py SRCH_001    # Basic search
```

### Run Individual Test Files
```bash
python test_case_successful_login.py
python test_case_create_folder.py
python test_case_basic_search.py
```

## 📋 Available Test Cases

### Authentication (AUTH)
- ✅ **AUTH_001** - `test_case_successful_login.py`: Valid login
- ✅ **AUTH_002** - `test_case_invalid_email_login.py`: Invalid email login
- ✅ **AUTH_003** - `test_case_invalid_password_login.py`: Invalid password login
- ✅ **AUTH_004** - `test_case_empty_fields_login.py`: Empty fields validation
- ✅ **AUTH_010** - `test_case_user_logout.py`: Logout functionality

### File Management (FILE)
- ✅ **FILE_001** - `test_case_single_file_upload.py`: Single file upload

### Folder Management (FOLD)
- ✅ **FOLD_001** - `test_case_create_folder.py`: Create folder

### Search (SRCH)
- ✅ **SRCH_001** - `test_case_basic_search.py`: Basic search

## 🔄 Global Session Features

### Persistent Login
```python
from global_session import session

# Login once - session persists across tests
driver = session.login()

# Navigate to dashboard using existing session
driver = session.navigate_to_dashboard()

# Check login status
if session.is_logged_in():
    print("Already logged in!")
```

### Session Management
```python
# Reset session (logout and start fresh)
session.reset_session()

# Cleanup (close browser)
session.cleanup()
```

## 📝 Adding New Test Cases

1. **Create new test file**:
   ```python
   # test_case_your_feature.py
   from global_session import session
   from selenium.webdriver.common.by import By
   
   def test_case_your_feature():
       """YOUR_ID: Test description"""
       test_id = "YOUR_001"
       print(f"\n🧪 Running {test_id}: Your Feature")
       
       try:
           # Use global session
           driver = session.login()
           session.navigate_to_dashboard()
           
           # Your test logic here
           # ...
           
           print(f"✓ {test_id}: Test PASSED")
           return True
           
       except Exception as e:
           print(f"✗ {test_id}: Test FAILED - {str(e)}")
           return False
   
   if __name__ == "__main__":
       try:
           result = test_case_your_feature()
           print(f"\nTest Result: {'PASSED' if result else 'FAILED'}")
       finally:
           session.cleanup()
   ```

2. **Add to runner** (edit `run_individual_tests.py`):
   ```python
   TEST_CASES = {
       'your_category': ['test_case_your_feature'],
       # ...
   }
   
   TEST_ID_MAP = {
       'YOUR_001': 'test_case_your_feature',
       # ...
   }
   ```

## 🎯 Key Benefits

### Easy Tracking
- Each test case is isolated in its own file
- Clear naming convention: `test_case_<description>.py`
- Test ID mapping for quick reference

### Efficient Execution
- Single browser session reduces startup time
- Persistent login eliminates repeated authentication
- Global session management prevents conflicts

### Flexible Running
- Run individual tests for focused debugging
- Run categories for module testing
- Run all tests for comprehensive validation

### Simple Debugging
- Each test case can run independently
- Clear error messages with test IDs
- Easy to add logging and breakpoints

## 🐛 Troubleshooting

### Test Failures
```bash
# Run specific failing test
python test_case_successful_login.py

# Run with more verbose output
python run_individual_tests.py AUTH_001
```

### Session Issues
```bash
# If login fails, check credentials in global_session.py
# Make sure your app is running on the correct URL
```

### Browser Issues
```bash
# Update Chrome and ChromeDriver
pip install --upgrade selenium webdriver-manager
```

## 📊 Example Output

```
=======================================================================
SecureDocs Individual Test Case Runner
=======================================================================
Started at: 2025-10-12 18:30:15

==================================================
RUNNING AUTH TESTS
==================================================
🧪 Executing: test_case_successful_login
🚀 Created new browser session
🔐 Logging in as test@example.com...
✓ Login successful - session established

🧪 Running AUTH_001: Successful Login
✓ AUTH_001: Successful login test PASSED

🧪 Executing: test_case_invalid_email_login

🧪 Running AUTH_002: Invalid Email Login
✓ AUTH_002: Invalid email login test PASSED

AUTH Tests Summary: 5/5 passed

=======================================================================
Tests completed at: 2025-10-12 18:32:45
Total execution time: 150.30 seconds
=======================================================================
```
