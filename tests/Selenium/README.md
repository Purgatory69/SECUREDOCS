# SecureDocs Selenium Testing Suite

This directory contains Python Selenium tests for the SecureDocs authentication system.

## Files Structure

```
tests/Selenium/
├── requirements.txt      # Python dependencies
├── base_test.py         # Base test class with common functionality
├── test_login.py        # Login functionality tests
├── test_signup.py       # Signup functionality tests
├── run_all_tests.py     # Run all tests with summary
├── README.md            # This file
└── screenshots/         # Auto-generated screenshots (created during tests)
```

## Setup Instructions

1. **Install Python dependencies:**
   ```bash
   cd tests/Selenium
   pip install -r requirements.txt
   ```

2. **Make sure Chrome browser is installed** (ChromeDriver will be auto-downloaded)

3. **Start your Laravel development server:**
   ```bash
   php artisan serve
   ```

4. **Make sure the application is running on `http://localhost:8000`**

## Running Tests

### Run Individual Test Files

**Login Tests:**
```bash
python test_login.py
```

**Signup Tests:**
```bash
python test_signup.py
```

### Run All Tests
```bash
python run_all_tests.py
```

## Test Coverage

### Login Tests (`test_login.py`)
- ✅ Successful login with valid credentials (`fool@gmail.com` / `password`)
- ✅ Invalid login handling and error display
- ✅ Password visibility toggle functionality
- ✅ Form validation

### Signup Tests (`test_signup.py`)
- ✅ Successful user registration with unique email
- ✅ Existing email validation
- ✅ Password mismatch validation
- ✅ Password visibility toggle (dual field sync)
- ✅ Navigation between login/signup pages

## Configuration

### Test Credentials
- **Login Email:** `fool@gmail.com`
- **Login Password:** `password`
- **Base URL:** `http://localhost:8000`

### Browser Settings
- **Default Mode:** Visible browser (headless=False)
- **Window Size:** 1920x1080
- **Auto Screenshots:** Enabled for debugging

### Changing to Headless Mode
Edit the `setup_driver()` calls in test files:
```python
self.setup_driver(headless=True)  # For headless mode
self.setup_driver(headless=False) # For visible browser
```

## Screenshots

Screenshots are automatically saved to `tests/Selenium/screenshots/` directory:
- Timestamped filenames for easy tracking
- Captured at key test points and on errors
- Useful for debugging failed tests

## Troubleshooting

### Common Issues

1. **ChromeDriver not found:**
   - The `webdriver-manager` package should auto-download ChromeDriver
   - Make sure Chrome browser is installed

2. **Connection refused:**
   - Ensure Laravel server is running: `php artisan serve`
   - Check that the base URL is correct

3. **Element not found:**
   - Screenshots will show the page state
   - Check if page elements have changed

4. **Tests running too fast:**
   - Adjust `time.sleep()` values if needed
   - Increase WebDriverWait timeouts

### Debug Mode
For debugging, you can:
1. Set `headless=False` to see browser actions
2. Add `time.sleep(5)` to pause execution
3. Check screenshots in the `screenshots/` folder

## Example Output

```
🚀 Starting SecureDocs Authentication Test Suite
============================================================

📝 RUNNING LOGIN TESTS
----------------------------------------
1. Testing successful login...
✓ Login page loaded successfully
✓ Filled login form with email: fool@gmail.com
✓ Clicked login button
✓ Login successful - redirected to dashboard

🏆 FINAL TEST RESULTS
============================================================
📝 LOGIN TESTS:
  ✅ Successful Login: PASS
  ✅ Invalid Login: PASS
  ✅ Password Toggle: PASS

📊 SUMMARY:
  Total Tests: 8
  Passed: 8
  Failed: 0
  Success Rate: 100.0%

🎉 ALL TESTS PASSED! 🎉
```
