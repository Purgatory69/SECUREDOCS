# SecureDocs - Python Selenium Test Automation Framework

## 🚀 Overview

A comprehensive Python-based Selenium WebDriver automation framework for testing the SecureDocs document management system. This framework follows industry best practices with **Page Object Model (POM)**, **pytest**, **Allure reporting**, and **data-driven testing**.

## 📊 Test Coverage

**70+ Test Cases** covering all major SecureDocs functionality:
- ✅ **Authentication** (Login, Registration, Email Verification)
- ✅ **File Management** (Upload, Download, Delete, Restore)
- ✅ **Security Features** (OTP, WebAuthn, Email Verification)
- ✅ **Admin Panel** (User Management, Analytics)
- ✅ **Premium Features** (Blockchain, AI Vectorization)
- ✅ **Search & Navigation** (Advanced Search, Filters)

## 🏗️ Framework Architecture

```
python/
├── pages/                          # Page Object Model classes
│   ├── base_page.py               # Base page with common functionality
│   ├── login_page.py              # Login page objects
│   └── dashboard_page.py          # Dashboard page objects
├── tests/                         # Test classes
│   └── test_login.py              # Login functionality tests
├── utils/                         # Utility classes
│   ├── config.py                  # Configuration management
│   ├── test_data.py               # Test data providers
│   ├── test_base.py               # Base test class
│   └── logger.py                  # Logging utilities
├── config/                        # Configuration files
│   └── test.ini                   # Test settings
├── test-data/                     # Test data files
│   ├── files/                     # Sample files for upload
│   └── *.json                     # JSON test data
├── reports/                       # Test execution reports
├── screenshots/                   # Screenshot captures
├── logs/                          # Execution logs
├── requirements.txt               # Python dependencies
├── pytest.ini                    # Pytest configuration
└── README.md                      # This file
```

## 🛠️ Setup Instructions

### 1. Prerequisites
- **Python 3.8+** installed
- **SecureDocs application** running on `http://localhost:8000`
- **Chrome/Firefox/Edge** browser installed

### 2. Installation

```bash
# Navigate to Python framework directory
cd test-automation/python

# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Install Allure (for reporting)
# Windows: Download from https://github.com/allure-framework/allure2/releases
# Linux: sudo apt-get install allure
# Mac: brew install allure
```

### 3. Configuration

Create `config/test.ini`:
```ini
[DEFAULT]
base_url = http://localhost:8000
browser = chrome
headless = false
implicit_wait = 10
explicit_wait = 30

[TEST_DATA]
valid_user_email = testuser@example.com
valid_user_password = SecurePass123!
admin_email = admin@example.com
admin_password = AdminPass123!
```

### 4. Environment Variables (Optional)
```bash
# Set environment variables
export BASE_URL=http://localhost:8000
export BROWSER=chrome
export HEADLESS=false
export VALID_USER_EMAIL=testuser@example.com
export VALID_USER_PASSWORD=SecurePass123!
```

## 🚀 Running Tests

### Basic Test Execution

```bash
# Run all tests
pytest

# Run specific test file
pytest tests/test_login.py

# Run specific test method
pytest tests/test_login.py::TestLogin::test_valid_login

# Run with verbose output
pytest -v
```

### Test Categories

```bash
# Run smoke tests (critical functionality)
pytest -m smoke

# Run regression tests
pytest -m regression

# Run security tests
pytest -m security

# Run OTP tests
pytest -m otp

# Run admin tests
pytest -m admin

# Run premium features tests
pytest -m premium
```

### Browser Selection

```bash
# Run with Chrome
pytest --browser=chrome

# Run with Firefox
pytest --browser=firefox

# Run with Edge
pytest --browser=edge

# Run in headless mode
pytest --browser=chrome --headless=true
```

### Parallel Execution

```bash
# Run tests in parallel (requires pytest-xdist)
pytest -n auto

# Run with specific number of workers
pytest -n 4

# Run with load balancing
pytest -n auto --dist=loadfile
```

### Test Retry on Failures

```bash
# Retry failed tests 2 times
pytest --reruns 2

# Retry with delay
pytest --reruns 2 --reruns-delay 5
```

## 📊 Reporting

### HTML Reports
```bash
# Generate HTML report
pytest --html=reports/report.html --self-contained-html

# View report
open reports/report.html
```

### Allure Reports
```bash
# Generate Allure results
pytest --alluredir=reports/allure-results

# Serve Allure report
allure serve reports/allure-results

# Generate static Allure report
allure generate reports/allure-results -o reports/allure-report --clean
```

### Screenshot Capture
- Automatic screenshots on test failures
- Screenshots saved to `screenshots/` directory
- Attached to Allure reports automatically

## 🧪 Writing Tests

### Example Test Class
```python
import pytest
import allure
from pages.login_page import LoginPage
from utils.test_data import TestData
from utils.test_base import TestBase

@allure.feature("Authentication")
class TestLogin(TestBase):
    
    def setup_method(self):
        self.login_page = LoginPage(self.driver)
        self.login_page.navigate_to_login()
    
    @allure.story("Valid Login")
    @pytest.mark.smoke
    @pytest.mark.critical
    def test_valid_login(self):
        """Test Case: AUTH_001 - Validate user can login with valid credentials"""
        
        # Test Data
        user = TestData.get_valid_user()
        
        with allure.step("Enter valid credentials"):
            self.login_page.login(user['email'], user['password'])
            
        with allure.step("Verify successful login"):
            assert self.login_page.is_login_successful()
            
        # Log result to CSV
        self.log_test_result("AUTH_001", "PASS", "Login successful")
```

### Page Object Example
```python
from selenium.webdriver.common.by import By
from pages.base_page import BasePage

class LoginPage(BasePage):
    
    # Locators
    EMAIL_FIELD = (By.ID, "email")
    PASSWORD_FIELD = (By.ID, "password")
    LOGIN_BUTTON = (By.CSS_SELECTOR, "button[type='submit']")
    
    def login(self, email, password):
        """Perform login with credentials"""
        self.clear_and_type(self.EMAIL_FIELD, email)
        self.clear_and_type(self.PASSWORD_FIELD, password)
        self.click_element(self.LOGIN_BUTTON)
        
    def is_login_successful(self):
        """Check if login was successful"""
        return self.wait_for_url_contains("/dashboard", 10)
```

## 📊 Test Data Management

### JSON Test Data
```python
# Get test data
user = TestData.get_valid_user()
files = TestData.get_test_files()
search_data = TestData.get_search_data()

# Custom test data
custom_data = TestData.load_data_from_json('custom_data.json')
```

### Environment-Specific Data
```python
# Configuration-based data
email = Config().get_valid_user_email()
password = Config().get_valid_user_password()
base_url = Config().get_base_url()
```

## 🔧 Advanced Features

### Email Verification Testing
```python
def test_otp_email_verification_required(self):
    """Test Case: OTP_006 - Email verification required for OTP"""
    unverified_user = TestData.get_unverified_user()
    
    self.login_page.login(unverified_user['email'], unverified_user['password'])
    
    # Check for verification notice
    assert self.login_page.is_email_verification_notice_visible()
```

### Cross-Browser Testing
```python
@pytest.mark.parametrize("browser", ["chrome", "firefox", "edge"])
def test_login_cross_browser(self, browser):
    """Test login across different browsers"""
    # Test implementation
```

### Performance Testing
```python
def test_dashboard_load_performance(self):
    """Test dashboard page load performance"""
    start_time = time.time()
    self.dashboard_page.navigate_to_dashboard()
    load_time = time.time() - start_time
    
    assert load_time < 5.0, f"Dashboard loaded in {load_time:.2f}s (should be < 5s)"
```

### API Integration Testing
```python
import requests

def test_api_file_upload(self):
    """Test file upload via API"""
    endpoint = TestData.get_api_endpoints()['upload']
    response = requests.post(endpoint, files={'file': open('test.pdf', 'rb')})
    assert response.status_code == 200
```

## 🐛 Debugging

### Debug Mode
```bash
# Run with debug logging
pytest --log-cli-level=DEBUG

# Run single test with debug
pytest -s -v tests/test_login.py::TestLogin::test_valid_login

# Keep browser open on failure
pytest --pdb
```

### Screenshot Analysis
```python
# Manual screenshot capture
self.take_screenshot("debug_screenshot")

# Screenshot with assertion
self.assert_with_screenshot(condition, "Assertion failed message")
```

### Browser Developer Tools
```python
# Execute JavaScript for debugging
result = self.driver.execute_script("return document.readyState;")

# Get browser logs
logs = self.driver.get_log('browser')
```

## 📈 Continuous Integration

### GitHub Actions
```yaml
name: SecureDocs Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Set up Python
        uses: actions/setup-python@v4
        with:
          python-version: '3.9'
      - name: Install dependencies
        run: |
          cd test-automation/python
          pip install -r requirements.txt
      - name: Run tests
        run: |
          cd test-automation/python
          pytest -m smoke --html=reports/report.html
      - name: Upload reports
        uses: actions/upload-artifact@v3
        with:
          name: test-reports
          path: test-automation/python/reports/
```

### Jenkins Pipeline
```groovy
pipeline {
    agent any
    stages {
        stage('Setup') {
            steps {
                sh 'cd test-automation/python && pip install -r requirements.txt'
            }
        }
        stage('Test') {
            steps {
                sh 'cd test-automation/python && pytest -m regression --alluredir=reports/allure-results'
            }
        }
        stage('Report') {
            steps {
                allure([
                    includeProperties: false,
                    jdk: '',
                    properties: [],
                    reportBuildPolicy: 'ALWAYS',
                    results: [[path: 'test-automation/python/reports/allure-results']]
                ])
            }
        }
    }
}
```

## 🔧 Troubleshooting

### Common Issues

#### WebDriver Issues
```bash
# Clear WebDriver cache
rm -rf ~/.cache/selenium/

# Update WebDriver
pip install --upgrade webdriver-manager
```

#### Browser Compatibility
```bash
# Check browser version
google-chrome --version
firefox --version

# Update browsers
sudo apt update && sudo apt upgrade
```

#### Test Environment
```bash
# Verify SecureDocs is running
curl http://localhost:8000

# Check Python version
python --version

# Verify dependencies
pip check
```

#### Permission Issues
```bash
# Fix screenshot directory permissions
chmod 755 screenshots/

# Fix log file permissions
chmod 644 logs/*.log
```

### Debug Commands
```bash
# Run with maximum verbosity
pytest -vvv --tb=long

# Show local variables in traceback
pytest --tb=auto --showlocals

# Run specific failing test
pytest --lf  # last failed
pytest --ff  # failed first
```

## 📚 Best Practices

### Test Organization
- One test class per page/feature
- Clear test method names following pattern: `test_<action>_<expected_result>`
- Use descriptive docstrings with test case IDs
- Group related tests with pytest markers

### Page Objects
- Keep page objects focused on single pages
- Use descriptive locator names
- Include wait conditions in page actions
- Return meaningful data from page methods

### Test Data
- Externalize test data in JSON/CSV files
- Use data providers for parameterized tests
- Keep sensitive data in environment variables
- Create test data setup/teardown methods

### Assertions
- Use descriptive assertion messages
- Take screenshots on assertion failures
- Log test results for tracking
- Use soft assertions for multiple checks

## 📞 Support

### Documentation
- Framework README (this file)
- Page Object documentation in code
- Test case mapping in CSV file
- Configuration examples

### Logging
- Check `logs/` directory for execution logs
- Review Allure reports for detailed test information
- Screenshot evidence in `screenshots/` directory

### Contact
For framework issues:
1. Check existing logs and reports
2. Review configuration settings
3. Verify test environment
4. Create detailed issue with reproduction steps

---

**Framework Version**: 1.0  
**Last Updated**: January 2025  
**Python Version**: 3.8+  
**Selenium Version**: 4.15+
