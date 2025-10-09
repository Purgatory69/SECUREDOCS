package com.securedocs.pages;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.support.FindBy;
import org.openqa.selenium.support.PageFactory;
import org.openqa.selenium.support.ui.WebDriverWait;
import org.openqa.selenium.support.ui.ExpectedConditions;
import java.time.Duration;

/**
 * Page Object Model for SecureDocs Login Page
 * Covers test cases: AUTH_001 to AUTH_010
 */
public class LoginPage {
    
    private WebDriver driver;
    private WebDriverWait wait;
    
    // Page URL
    private static final String LOGIN_URL = "/login";
    
    // Page Elements using @FindBy annotations
    @FindBy(id = "email")
    private WebElement emailField;
    
    @FindBy(id = "password") 
    private WebElement passwordField;
    
    @FindBy(css = "button[type='submit']")
    private WebElement loginButton;
    
    @FindBy(linkText = "Register")
    private WebElement registerLink;
    
    @FindBy(linkText = "Forgot Your Password?")
    private WebElement forgotPasswordLink;
    
    @FindBy(css = ".alert-danger")
    private WebElement errorMessage;
    
    @FindBy(css = ".alert-success")
    private WebElement successMessage;
    
    @FindBy(id = "remember")
    private WebElement rememberMeCheckbox;
    
    // Language switching elements
    @FindBy(css = "select[name='language']")
    private WebElement languageSelector;
    
    // WebAuthn login button
    @FindBy(id = "webauthn-login-btn")
    private WebElement webAuthnLoginButton;
    
    // Email verification elements
    @FindBy(css = ".verification-notice")
    private WebElement verificationNotice;
    
    // Constructor
    public LoginPage(WebDriver driver) {
        this.driver = driver;
        this.wait = new WebDriverWait(driver, Duration.ofSeconds(10));
        PageFactory.initElements(driver, this);
    }
    
    // Navigation Methods
    public void navigateToLogin() {
        driver.get(System.getProperty("base.url", "http://localhost:8000") + LOGIN_URL);
        waitForPageLoad();
    }
    
    public void waitForPageLoad() {
        wait.until(ExpectedConditions.presenceOfElementLocated(By.id("email")));
    }
    
    // Authentication Actions
    
    /**
     * Perform login with credentials
     * Test Cases: AUTH_001, AUTH_002, AUTH_003, AUTH_004
     */
    public void login(String email, String password) {
        clearAndType(emailField, email);
        clearAndType(passwordField, password);
        loginButton.click();
    }
    
    /**
     * Perform login with remember me option
     * Test Case: AUTH_001 (variant)
     */
    public void loginWithRememberMe(String email, String password) {
        clearAndType(emailField, email);
        clearAndType(passwordField, password);
        if (!rememberMeCheckbox.isSelected()) {
            rememberMeCheckbox.click();
        }
        loginButton.click();
    }
    
    /**
     * Attempt login with empty fields
     * Test Case: AUTH_004
     */
    public void attemptLoginWithEmptyFields() {
        loginButton.click();
    }
    
    /**
     * Navigate to registration page
     * Test Case: AUTH_005
     */
    public void clickRegisterLink() {
        registerLink.click();
    }
    
    /**
     * Navigate to password reset page  
     * Test Case: AUTH_009
     */
    public void clickForgotPasswordLink() {
        forgotPasswordLink.click();
    }
    
    /**
     * Switch language
     * Related to: DASH_004
     */
    public void switchLanguage(String language) {
        languageSelector.sendKeys(language);
    }
    
    /**
     * Initiate WebAuthn login
     * Test Cases: WEBAUTH_002
     */
    public void clickWebAuthnLogin() {
        webAuthnLoginButton.click();
    }
    
    // Validation Methods
    
    /**
     * Check if login was successful by verifying redirect
     * Test Case: AUTH_001
     */
    public boolean isLoginSuccessful() {
        try {
            wait.until(ExpectedConditions.urlContains("/dashboard"));
            return true;
        } catch (Exception e) {
            return false;
        }
    }
    
    /**
     * Get error message text
     * Test Cases: AUTH_002, AUTH_003, AUTH_004
     */
    public String getErrorMessage() {
        try {
            wait.until(ExpectedConditions.visibilityOf(errorMessage));
            return errorMessage.getText();
        } catch (Exception e) {
            return "";
        }
    }
    
    /**
     * Get success message text
     * Test Case: AUTH_005
     */
    public String getSuccessMessage() {
        try {
            wait.until(ExpectedConditions.visibilityOf(successMessage));
            return successMessage.getText();
        } catch (Exception e) {
            return "";
        }
    }
    
    /**
     * Check if email verification notice is displayed
     * Test Case: OTP_006
     */
    public boolean isEmailVerificationNoticeVisible() {
        try {
            return verificationNotice.isDisplayed();
        } catch (Exception e) {
            return false;
        }
    }
    
    /**
     * Check if login form has validation errors
     * Test Case: AUTH_004
     */
    public boolean hasValidationErrors() {
        try {
            WebElement emailError = driver.findElement(By.cssSelector("#email + .error-message"));
            WebElement passwordError = driver.findElement(By.cssSelector("#password + .error-message"));
            return emailError.isDisplayed() || passwordError.isDisplayed();
        } catch (Exception e) {
            return false;
        }
    }
    
    /**
     * Verify specific field validation error
     * Test Case: AUTH_004
     */
    public String getFieldValidationError(String fieldId) {
        try {
            WebElement errorElement = driver.findElement(By.cssSelector("#" + fieldId + " + .error-message"));
            return errorElement.getText();
        } catch (Exception e) {
            return "";
        }
    }
    
    /**
     * Check if WebAuthn is supported/available
     * Test Case: WEBAUTH_004
     */
    public boolean isWebAuthnAvailable() {
        try {
            return webAuthnLoginButton.isDisplayed();
        } catch (Exception e) {
            return false;
        }
    }
    
    // Utility Methods
    
    /**
     * Clear field and type new value
     */
    private void clearAndType(WebElement element, String text) {
        element.clear();
        element.sendKeys(text);
    }
    
    /**
     * Wait for element to be clickable
     */
    private void waitForClickable(WebElement element) {
        wait.until(ExpectedConditions.elementToBeClickable(element));
    }
    
    /**
     * Get current page title
     */
    public String getPageTitle() {
        return driver.getTitle();
    }
    
    /**
     * Get current URL
     */
    public String getCurrentUrl() {
        return driver.getCurrentUrl();
    }
    
    /**
     * Check if element is visible
     */
    public boolean isElementVisible(WebElement element) {
        try {
            return element.isDisplayed();
        } catch (Exception e) {
            return false;
        }
    }
}
