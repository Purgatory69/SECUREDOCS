# SecureDocs Simple Automation Tests

A simple, modular Selenium-based automation testing suite for SecureDocs application.

## Setup

### Prerequisites
- Python 3.7+
- Chrome browser installed
- ChromeDriver (will be installed automatically with webdriver-manager)

### Installation

1. Install required packages:
```bash
pip install -r requirements.txt
```

2. Make sure your SecureDocs application is running on `http://localhost:8000`

3. Update the test credentials in `common_functions.py` if needed:
   - Default email: `test@example.com`
   - Default password: `password`

## Test Structure

```
tests/
├── webdriver_utils.py          # Shared web driver configuration
├── common_functions.py         # Reusable functions (login, logout, etc.)
├── test_authentication.py      # Authentication tests (AUTH_001-010)
├── test_file_management.py     # File management tests (FILE_001-010)
├── test_folder_management.py   # Folder management tests (FOLD_001-006)
├── test_search.py              # Search functionality tests (SRCH_001-007)
├── run_tests.py                # Test runner script
├── requirements.txt            # Python dependencies
└── README.md                   # This file
```

## Running Tests

### Run All Tests
```bash
python run_tests.py
```

### Run Specific Test Modules
```bash
python run_tests.py auth        # Authentication tests only
python run_tests.py file        # File management tests only
python run_tests.py folder      # Folder management tests only
python run_tests.py search      # Search tests only
```

### Run Individual Test Files
```bash
python test_authentication.py
python test_file_management.py
python test_folder_management.py
python test_search.py
```

## Test Cases Covered

### Authentication (AUTH)
- ✓ AUTH_001: Valid login
- ✓ AUTH_002: Invalid email login
- ✓ AUTH_003: Invalid password login
- ✓ AUTH_004: Empty fields validation
- ✓ AUTH_010: Logout functionality

### File Management (FILE)
- ✓ FILE_001: Single file upload
- ✓ FILE_005: File download
- ✓ FILE_007: File rename
- ✓ FILE_008: File delete (soft delete)

### Folder Management (FOLD)
- ✓ FOLD_001: Create folder
- ✓ FOLD_002: Navigate folder
- ✓ FOLD_003: Rename folder
- ✓ FOLD_004: Delete empty folder

### Search (SRCH)
- ✓ SRCH_001: Basic search
- ✓ SRCH_002: Advanced search with filters
- ✓ SRCH_006: Save search queries
- ✓ SRCH_007: Clear search results

## Key Features

### Modular Design
- **Shared Web Driver**: Single `web_driver()` function for consistent browser setup
- **Reusable Functions**: Common actions like `login()`, `logout()`, `create_folder()` 
- **Independent Tests**: Each test can run standalone
- **Easy Extension**: Add new test files following the same pattern

### Simple Configuration
- Headless Chrome by default (change in `webdriver_utils.py`)
- Configurable base URL and credentials
- Automatic wait handling for dynamic content

## Adding New Tests

1. Create a new test file (e.g., `test_new_feature.py`)
2. Import required modules:
   ```python
   from webdriver_utils import web_driver
   from common_functions import login, BASE_URL
   ```
3. Write test functions following the pattern:
   ```python
   def test_feature_001_description():
       driver = web_driver()
       try:
           # Test logic here
           return True
       except Exception as e:
           print(f"Test failed: {e}")
           return False
       finally:
           driver.quit()
   ```
4. Add the test module to `run_tests.py`

## Troubleshooting

### Common Issues

**ChromeDriver not found**
- Install webdriver-manager: `pip install webdriver-manager`
- Or download ChromeDriver manually and add to PATH

**Elements not found**
- Check if selectors match your application's HTML
- Increase wait times if needed
- Verify application is running on correct URL

**Tests failing**
- Ensure test user account exists with correct credentials
- Check application logs for errors
- Verify database is properly seeded

### Configuration

Update `common_functions.py` to match your environment:
```python
BASE_URL = "http://localhost:8000"  # Your app URL
# Update login credentials as needed
```

Update `webdriver_utils.py` for different browser options:
```python
# Remove '--headless' to see browser during tests
# Adjust window size, add other Chrome options
```

## Contributing

When adding new tests:
1. Follow the existing naming convention (test_module_###_description)
2. Use the shared web driver and common functions
3. Handle exceptions gracefully
4. Clean up resources in finally blocks
5. Update this README with new test cases
