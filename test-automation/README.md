# SecureDocs - Selenium Test Automation Framework

## Overview
This is a comprehensive Selenium WebDriver automation framework for testing the SecureDocs document management system. The framework follows industry best practices including Page Object Model (POM), data-driven testing, and modular test design.

## Framework Architecture

```
test-automation/
├── config/
│   ├── testng.xml                    # TestNG suite configuration
│   ├── test.properties               # Test environment properties
│   └── log4j2.xml                   # Logging configuration
├── src/
│   ├── main/java/
│   │   ├── pages/                   # Page Object Model classes
│   │   ├── utils/                   # Utility classes and helpers
│   │   ├── listeners/               # TestNG listeners
│   │   └── data/                    # Test data providers
│   └── test/java/
│       ├── tests/                   # Test classes organized by module
│       ├── base/                    # Base test classes
│       └── suites/                  # Test suite configurations
├── test-data/
│   ├── users.json                   # User test data
│   ├── files/                       # Test files for upload
│   └── excel/                       # Excel data sheets
├── reports/                         # Test execution reports
├── screenshots/                     # Screenshot captures
├── logs/                           # Execution logs
├── selenium-test-plan.md            # Comprehensive test plan
└── README.md                       # This file
```

## Prerequisites

### Software Requirements
- **Java**: JDK 11 or higher
- **Maven**: 3.6 or higher
- **Browsers**: Chrome, Firefox, Edge (latest versions)
- **WebDriver**: Managed automatically via WebDriverManager

### Application Requirements
- SecureDocs application running on `http://localhost:8000`
- Test database with sample data
- Email service configured for OTP testing
- Valid test user accounts

## Setup Instructions

### 1. Clone and Configure
```bash
git clone <repository-url>
cd SECUREDOCS/test-automation
```

### 2. Install Dependencies
```bash
mvn clean install
```

### 3. Configure Test Properties
Edit `config/test.properties`:
```properties
# Base Configuration
base.url=http://localhost:8000
browser=chrome
headless=false
implicit.wait=10
explicit.wait=30

# Test Data
test.user.email=testuser@example.com
test.user.password=SecurePass123!
admin.email=admin@example.com
admin.password=AdminPass123!

# Environment
environment=local
test.data.path=./test-data/
screenshot.path=./screenshots/
report.path=./reports/
```

### 4. Verify Setup
```bash
mvn test -Dtest=LoginTest#testValidLogin
```

## Test Execution

### Run Specific Test Suites

#### Smoke Tests (Critical functionality)
```bash
mvn test -Dsuite=smoke
```

#### Full Regression Tests
```bash
mvn test -Dsuite=regression
```

#### Module-Specific Tests
```bash
# Authentication tests
mvn test -Dgroups=authentication

# File management tests  
mvn test -Dgroups=file_management

# Admin panel tests
mvn test -Dgroups=admin

# Security features
mvn test -Dgroups=security,otp
```

#### Cross-Browser Testing
```bash
# Chrome
mvn test -Dbrowser=chrome -Dgroups=compatibility

# Firefox
mvn test -Dbrowser=firefox -Dgroups=compatibility

# Edge
mvn test -Dbrowser=edge -Dgroups=compatibility
```

#### Parallel Execution
```bash
mvn test -DsuiteXmlFile=config/testng.xml -Dparallel=methods -DthreadCount=3
```

### Run with Custom Parameters
```bash
mvn test \
  -Dbase.url=https://staging.securedocs.com \
  -Dbrowser=chrome \
  -Dheadless=true \
  -Dgroups=smoke
```

## Test Data Management

### User Accounts
```json
{
  "valid_users": [
    {
      "email": "testuser@example.com",
      "password": "SecurePass123!",
      "name": "Test User",
      "role": "user",
      "verified": true
    },
    {
      "email": "premium@example.com", 
      "password": "PremiumPass123!",
      "name": "Premium User",
      "role": "user",
      "premium": true,
      "verified": true
    }
  ],
  "admin_users": [
    {
      "email": "admin@example.com",
      "password": "AdminPass123!",
      "name": "Admin User", 
      "role": "admin"
    }
  ]
}
```

### Test Files
Place test files in `test-data/files/`:
- `sample-document.pdf` (1MB)
- `test-image.jpg` (500KB)
- `large-file.zip` (10MB)
- `text-document.txt` (1KB)

## Page Object Model

### Example Page Class
```java
@Component
public class LoginPage extends BasePage {
    
    @FindBy(id = "email")
    private WebElement emailField;
    
    public void login(String email, String password) {
        clearAndType(emailField, email);
        clearAndType(passwordField, password);
        clickElement(loginButton);
    }
    
    public boolean isLoginSuccessful() {
        return waitForUrlContains("/dashboard", 10);
    }
}
```

### Base Page Class
All page classes extend `BasePage` which provides:
- Common WebDriver operations
- Wait mechanisms
- Element interaction methods
- Screenshot capture utilities

## Test Structure

### Test Class Example
```java
public class LoginTest extends BaseTest {
    
    @Test(groups = {"smoke", "critical"})
    public void testValidLogin() {
        // Arrange
        String email = TestDataProvider.getValidUser().getEmail();
        String password = TestDataProvider.getValidUser().getPassword();
        
        // Act
        loginPage.login(email, password);
        
        // Assert
        Assert.assertTrue(loginPage.isLoginSuccessful());
        Assert.assertTrue(dashboardPage.isDashboardLoaded());
    }
}
```

## Reporting

### Extent Reports
HTML reports generated at: `reports/extent-report.html`

Features:
- Test execution summary
- Pass/Fail statistics  
- Screenshots on failures
- Test duration metrics
- Environment details

### TestNG Reports
XML/HTML reports at: `test-output/`

### Screenshots
Automatic screenshot capture:
- On test failures
- At key verification points
- Manual capture via `takeScreenshot()` method

## Continuous Integration

### GitHub Actions
```yaml
name: Selenium Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Set up JDK 11
        uses: actions/setup-java@v2
        with:
          java-version: '11'
      - name: Run tests
        run: mvn test -Dsuite=smoke
      - name: Upload reports
        uses: actions/upload-artifact@v2
        with:
          name: test-reports
          path: reports/
```

### Jenkins Pipeline
```groovy
pipeline {
    agent any
    stages {
        stage('Test') {
            steps {
                sh 'mvn clean test -Dsuite=regression'
            }
            post {
                always {
                    publishHTML([
                        allowMissing: false,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: 'reports',
                        reportFiles: 'extent-report.html',
                        reportName: 'Selenium Test Report'
                    ])
                }
            }
        }
    }
}
```

## Test Coverage

### Functional Modules Covered
- ✅ **Authentication** (Login, Registration, Password Reset)
- ✅ **File Management** (Upload, Download, Delete, Restore)
- ✅ **Folder Operations** (Create, Navigate, Move)
- ✅ **Search Functionality** (Basic, Advanced, Filters)
- ✅ **Security Features** (OTP, WebAuthn, Access Control)
- ✅ **Admin Panel** (User Management, Analytics)
- ✅ **Premium Features** (Blockchain, AI Vectorization)
- ✅ **Version Control** (File History, Restore)

### Test Types Covered
- **Functional Testing**: Core feature validation
- **UI Testing**: Interface and usability
- **Security Testing**: Authentication and authorization
- **Cross-Browser Testing**: Chrome, Firefox, Edge
- **Responsive Testing**: Mobile and tablet layouts
- **API Integration**: Backend service validation
- **Performance Testing**: Page load and operation speed

## Best Practices Implemented

### Framework Design
- **Page Object Model**: Maintainable page representations
- **Data-Driven Testing**: Externalized test data
- **Modular Design**: Reusable components and utilities
- **Configuration Management**: Environment-specific settings

### Test Design
- **Independent Tests**: No test dependencies
- **Descriptive Naming**: Clear test and method names
- **Comprehensive Assertions**: Multiple validation points
- **Error Handling**: Graceful failure management

### Maintenance
- **Version Control**: Git integration
- **Code Review**: Pull request workflows
- **Documentation**: Inline and external docs
- **Continuous Updates**: Regular framework enhancements

## Troubleshooting

### Common Issues

#### Browser Driver Issues
```bash
# Clear WebDriverManager cache
rm -rf ~/.cache/selenium/
mvn clean test -Dwebdrivermanager.clearCache=true
```

#### Test Data Issues
- Verify test users exist in database
- Check email service configuration for OTP tests
- Ensure test files are present in test-data/files/

#### Environment Issues
- Confirm SecureDocs application is running
- Check database connectivity
- Verify network access to external services

### Debug Mode
```bash
# Run with debug logging
mvn test -Dlog.level=DEBUG -Dtest=LoginTest

# Run with browser visible (non-headless)
mvn test -Dheadless=false -Dtest=LoginTest
```

### Screenshot Analysis
Failed test screenshots are saved to `screenshots/` with timestamp and test name.

## Contributing

### Adding New Tests
1. Create page object for new features
2. Add test data to appropriate JSON files
3. Write test class extending BaseTest
4. Update TestNG suite configurations
5. Add documentation

### Framework Enhancements
1. Follow existing code patterns
2. Add utility methods to appropriate classes
3. Update configuration files as needed
4. Document changes in README

## Support

For framework issues or questions:
- Review existing documentation
- Check GitHub issues
- Create new issue with detailed description
- Include logs and screenshots for debugging

---

**Framework Version**: 1.0  
**Last Updated**: January 2025  
**Maintained By**: QA Automation Team
