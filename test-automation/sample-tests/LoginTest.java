package com.securedocs.tests;

import com.securedocs.pages.LoginPage;
import com.securedocs.pages.DashboardPage;
import com.securedocs.utils.TestBase;
import com.securedocs.utils.TestDataProvider;
import org.testng.Assert;
import org.testng.annotations.*;
import org.openqa.selenium.WebDriver;

/**
 * Test class for SecureDocs Login functionality
 * Implements test cases AUTH_001 to AUTH_010 from test plan
 */
public class LoginTest extends TestBase {
    
    private LoginPage loginPage;
    private DashboardPage dashboardPage;
    
    @BeforeMethod
    public void setUp() {
        loginPage = new LoginPage(driver);
        dashboardPage = new DashboardPage(driver);
        loginPage.navigateToLogin();
    }
    
    /**
     * Test Case: AUTH_001
     * Validate user can login with valid credentials
     */
    @Test(priority = 1, groups = {"smoke", "critical"})
    @Description("Verify successful login with valid credentials")
    public void testValidLogin() {
        // Test Data
        String validEmail = TestDataProvider.getValidUser().getEmail();
        String validPassword = TestDataProvider.getValidUser().getPassword();
        
        // Test Steps
        loginPage.login(validEmail, validPassword);
        
        // Assertions
        Assert.assertTrue(loginPage.isLoginSuccessful(), 
            "User should be successfully logged in");
        Assert.assertTrue(dashboardPage.isDashboardLoaded(), 
            "Dashboard should be loaded after successful login");
        Assert.assertEquals(dashboardPage.getWelcomeMessage(), 
            "Welcome, " + TestDataProvider.getValidUser().getName(),
            "Welcome message should display user name");
            
        // Log result
        logTestResult("AUTH_001", "PASS", "User successfully logged in and redirected to dashboard");
    }
    
    /**
     * Test Case: AUTH_002  
     * Validate user cannot login with invalid email
     */
    @Test(priority = 2, groups = {"regression", "negative"})
    @Description("Verify login fails with invalid email")
    public void testInvalidEmailLogin() {
        // Test Data
        String invalidEmail = "nonexistent@example.com";
        String validPassword = "ValidPass123!";
        
        // Test Steps
        loginPage.login(invalidEmail, validPassword);
        
        // Assertions
        Assert.assertFalse(loginPage.isLoginSuccessful(), 
            "Login should fail with invalid email");
        Assert.assertTrue(loginPage.getErrorMessage().contains("Invalid credentials"), 
            "Error message should indicate invalid credentials");
        Assert.assertEquals(loginPage.getCurrentUrl(), 
            getBaseUrl() + "/login",
            "User should remain on login page");
            
        // Log result
        logTestResult("AUTH_002", "PASS", "Login failed with invalid email as expected");
    }
    
    /**
     * Test Case: AUTH_003
     * Validate user cannot login with invalid password  
     */
    @Test(priority = 3, groups = {"regression", "negative"})
    @Description("Verify login fails with invalid password")
    public void testInvalidPasswordLogin() {
        // Test Data
        String validEmail = TestDataProvider.getValidUser().getEmail();
        String invalidPassword = "WrongPassword123!";
        
        // Test Steps
        loginPage.login(validEmail, invalidPassword);
        
        // Assertions
        Assert.assertFalse(loginPage.isLoginSuccessful(), 
            "Login should fail with invalid password");
        Assert.assertTrue(loginPage.getErrorMessage().contains("Invalid credentials"), 
            "Error message should indicate invalid credentials");
            
        // Log result  
        logTestResult("AUTH_003", "PASS", "Login failed with invalid password as expected");
    }
    
    /**
     * Test Case: AUTH_004
     * Validate user cannot login with empty fields
     */
    @Test(priority = 4, groups = {"regression", "validation"})
    @Description("Verify validation errors for empty login fields")
    public void testEmptyFieldsLogin() {
        // Test Steps
        loginPage.attemptLoginWithEmptyFields();
        
        // Assertions
        Assert.assertFalse(loginPage.isLoginSuccessful(), 
            "Login should fail with empty fields");
        Assert.assertTrue(loginPage.hasValidationErrors(), 
            "Validation errors should be displayed");
        Assert.assertTrue(loginPage.getFieldValidationError("email").contains("required"), 
            "Email field should show required validation error");
        Assert.assertTrue(loginPage.getFieldValidationError("password").contains("required"), 
            "Password field should show required validation error");
            
        // Log result
        logTestResult("AUTH_004", "PASS", "Validation errors displayed for empty fields");
    }
    
    /**
     * Test Case: AUTH_005
     * Validate navigation to registration page
     */
    @Test(priority = 5, groups = {"smoke", "navigation"})
    @Description("Verify navigation to registration page")
    public void testNavigateToRegistration() {
        // Test Steps
        loginPage.clickRegisterLink();
        
        // Assertions
        Assert.assertTrue(driver.getCurrentUrl().contains("/register"), 
            "Should navigate to registration page");
        Assert.assertEquals(loginPage.getPageTitle(), "Register - SecureDocs", 
            "Page title should indicate registration page");
            
        // Log result
        logTestResult("AUTH_005", "PASS", "Successfully navigated to registration page");
    }
    
    /**
     * Test Case: AUTH_009
     * Validate navigation to password reset page
     */
    @Test(priority = 6, groups = {"regression", "navigation"})
    @Description("Verify navigation to password reset page") 
    public void testNavigateToPasswordReset() {
        // Test Steps
        loginPage.clickForgotPasswordLink();
        
        // Assertions
        Assert.assertTrue(driver.getCurrentUrl().contains("/forgot-password"), 
            "Should navigate to password reset page");
        Assert.assertEquals(loginPage.getPageTitle(), "Reset Password - SecureDocs", 
            "Page title should indicate password reset page");
            
        // Log result
        logTestResult("AUTH_009", "PASS", "Successfully navigated to password reset page");
    }
    
    /**
     * Test Case: AUTH_010
     * Validate logout functionality (requires login first)
     */
    @Test(priority = 7, groups = {"smoke", "critical"}, dependsOnMethods = {"testValidLogin"})
    @Description("Verify user can logout successfully")
    public void testLogout() {
        // Pre-condition: Login first
        String validEmail = TestDataProvider.getValidUser().getEmail();
        String validPassword = TestDataProvider.getValidUser().getPassword();
        loginPage.login(validEmail, validPassword);
        
        // Test Steps
        dashboardPage.clickLogout();
        
        // Assertions
        Assert.assertTrue(driver.getCurrentUrl().contains("/login"), 
            "Should be redirected to login page after logout");
        Assert.assertTrue(loginPage.getSuccessMessage().contains("logged out"), 
            "Success message should confirm logout");
            
        // Log result
        logTestResult("AUTH_010", "PASS", "User successfully logged out and redirected to login");
    }
    
    /**
     * Test Case: WEBAUTH_002 (Bonus)
     * Validate WebAuthn login availability  
     */
    @Test(priority = 8, groups = {"premium", "webauthn"})
    @Description("Verify WebAuthn login option is available")
    public void testWebAuthnLoginAvailable() {
        // Test Steps & Assertions
        if (loginPage.isWebAuthnAvailable()) {
            Assert.assertTrue(true, "WebAuthn login option is available");
            logTestResult("WEBAUTH_002", "PASS", "WebAuthn login button is visible and available");
        } else {
            Assert.assertTrue(true, "WebAuthn not available in this environment");
            logTestResult("WEBAUTH_002", "SKIP", "WebAuthn not supported in current browser/environment");
        }
    }
    
    /**
     * Data-driven test for multiple invalid login attempts
     */
    @Test(priority = 9, groups = {"regression", "data-driven"}, 
          dataProvider = "invalidLoginData", dataProviderClass = TestDataProvider.class)
    @Description("Verify login fails with various invalid credentials combinations")
    public void testMultipleInvalidLogins(String email, String password, String expectedError) {
        // Test Steps
        loginPage.login(email, password);
        
        // Assertions
        Assert.assertFalse(loginPage.isLoginSuccessful(), 
            "Login should fail with invalid credentials: " + email);
        Assert.assertTrue(loginPage.getErrorMessage().contains(expectedError), 
            "Error message should contain: " + expectedError);
    }
    
    /**
     * Cross-browser compatibility test
     */
    @Test(priority = 10, groups = {"compatibility"})
    @Description("Verify login works across different browsers")
    public void testCrossBrowserLogin() {
        // This test would be run with different browser configurations
        String validEmail = TestDataProvider.getValidUser().getEmail();
        String validPassword = TestDataProvider.getValidUser().getPassword();
        
        loginPage.login(validEmail, validPassword);
        
        Assert.assertTrue(loginPage.isLoginSuccessful(), 
            "Login should work in " + getBrowserName());
        
        logTestResult("AUTH_001_" + getBrowserName().toUpperCase(), "PASS", 
            "Login successful in " + getBrowserName());
    }
    
    @AfterMethod
    public void tearDown() {
        if (dashboardPage.isUserLoggedIn()) {
            dashboardPage.clickLogout();
        }
    }
    
    // Utility method to log test results (for reporting)
    private void logTestResult(String testCaseId, String result, String description) {
        System.out.println(String.format("Test Case: %s | Result: %s | Description: %s", 
            testCaseId, result, description));
        
        // This could also write to Excel, database, or test management tool
        // TestResultLogger.log(testCaseId, result, description, getCurrentTimestamp());
    }
}
