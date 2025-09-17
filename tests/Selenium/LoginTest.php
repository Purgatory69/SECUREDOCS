<?php

namespace Tests\Selenium;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class LoginTest extends BaseSeleniumTest
{
    private const TEST_EMAIL = 'fool@gmail.com';
    private const TEST_PASSWORD = 'password';
    
    public function testSuccessfulLogin(): void
    {
        // Navigate to login page
        $this->navigateTo('/login');
        $this->waitForPageLoad();
        
        // Verify we're on the login page
        $this->assertStringContains('Login', $this->getPageTitle());
        
        // Take screenshot for debugging
        $this->takeScreenshot('login_page_loaded');
        
        // Fill in login credentials
        $this->fillInput(WebDriverBy::id('email'), self::TEST_EMAIL);
        $this->fillInput(WebDriverBy::id('password'), self::TEST_PASSWORD);
        
        // Take screenshot before submitting
        $this->takeScreenshot('login_form_filled');
        
        // Submit the form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        // Wait for redirect after login
        $this->wait->until(function() {
            return !str_contains($this->getCurrentUrl(), '/login');
        });
        
        // Take screenshot after login
        $this->takeScreenshot('after_login');
        
        // Verify successful login by checking if we're redirected to dashboard
        $currentUrl = $this->getCurrentUrl();
        $this->assertTrue(
            str_contains($currentUrl, '/user/dashboard') || 
            str_contains($currentUrl, '/admin/dashboard') ||
            str_contains($currentUrl, '/redirect-after-login'),
            "Expected to be redirected to dashboard after login, but current URL is: {$currentUrl}"
        );
    }
    
    public function testLoginWithInvalidCredentials(): void
    {
        // Navigate to login page
        $this->navigateTo('/login');
        $this->waitForPageLoad();
        
        // Fill in invalid credentials
        $this->fillInput(WebDriverBy::id('email'), 'invalid@email.com');
        $this->fillInput(WebDriverBy::id('password'), 'wrongpassword');
        
        // Take screenshot before submitting
        $this->takeScreenshot('invalid_login_form_filled');
        
        // Submit the form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        // Wait for error message or stay on login page
        sleep(2); // Give time for form processing
        
        // Take screenshot after failed login
        $this->takeScreenshot('after_failed_login');
        
        // Verify we're still on login page (failed login)
        $currentUrl = $this->getCurrentUrl();
        $this->assertStringContains('/login', $currentUrl, 'Should remain on login page after failed login');
        
        // Check for validation errors (Laravel shows validation errors)
        $hasValidationErrors = $this->elementExists(WebDriverBy::className('text-red-600')) ||
                              $this->elementExists(WebDriverBy::xpath('//*[contains(@class, "error")]'));
        
        $this->assertTrue($hasValidationErrors, 'Should display validation errors for invalid login');
    }
    
    public function testLoginFormValidation(): void
    {
        // Navigate to login page
        $this->navigateTo('/login');
        $this->waitForPageLoad();
        
        // Try to submit empty form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        sleep(1); // Wait for browser validation
        
        // Take screenshot
        $this->takeScreenshot('empty_form_validation');
        
        // Check that required field validation works (HTML5 validation)
        $emailField = $this->driver->findElement(WebDriverBy::id('email'));
        $passwordField = $this->driver->findElement(WebDriverBy::id('password'));
        
        // Check if fields have required attribute
        $this->assertEquals('true', $emailField->getAttribute('required'));
        $this->assertEquals('true', $passwordField->getAttribute('required'));
    }
    
    public function testPasswordToggleVisibility(): void
    {
        // Navigate to login page
        $this->navigateTo('/login');
        $this->waitForPageLoad();
        
        // Find password field and toggle button
        $passwordField = $this->waitForElement(WebDriverBy::id('password'));
        $toggleButton = $this->waitForElement(WebDriverBy::id('togglePassword'));
        
        // Initially should be password type
        $this->assertEquals('password', $passwordField->getAttribute('type'));
        
        // Click toggle button
        $toggleButton->click();
        
        // Should now be text type
        $this->assertEquals('text', $passwordField->getAttribute('type'));
        
        // Click again to toggle back
        $toggleButton->click();
        
        // Should be password type again
        $this->assertEquals('password', $passwordField->getAttribute('type'));
        
        $this->takeScreenshot('password_toggle_test');
    }
    
    public function testBiometricLoginButton(): void
    {
        // Navigate to login page
        $this->navigateTo('/login');
        $this->waitForPageLoad();
        
        // Check if biometric login button exists
        $biometricButton = $this->waitForElement(WebDriverBy::id('biometric-login-button'));
        $this->assertTrue($biometricButton->isDisplayed(), 'Biometric login button should be visible');
        
        // Fill email first (required for biometric login)
        $this->fillInput(WebDriverBy::id('email'), self::TEST_EMAIL);
        
        // Click biometric login button
        $biometricButton->click();
        
        sleep(2); // Wait for any response
        
        // Take screenshot
        $this->takeScreenshot('biometric_login_test');
        
        // Check if status message appears (might show error if WebAuthn not available)
        $statusElement = $this->driver->findElement(WebDriverBy::id('biometric-login-status'));
        $this->assertTrue($statusElement->isDisplayed(), 'Biometric login status should be displayed');
    }
    
    public function testLanguageToggle(): void
    {
        // Navigate to login page
        $this->navigateTo('/login');
        $this->waitForPageLoad();
        
        // Find and click language toggle
        $languageToggle = $this->waitForElement(WebDriverBy::id('language-toggle'));
        $languageToggle->click();
        
        // Wait for dropdown to appear
        $dropdown = $this->waitForElement(WebDriverBy::id('language-dropdown'));
        $this->assertTrue($dropdown->isDisplayed(), 'Language dropdown should be visible after clicking toggle');
        
        // Take screenshot
        $this->takeScreenshot('language_dropdown_open');
        
        // Test switching to Filipino
        $filipinoLink = $this->driver->findElement(WebDriverBy::xpath('//a[@href="' . $this->baseUrl . '/set-language/fil"]'));
        $filipinoLink->click();
        
        // Wait for page reload
        $this->waitForPageLoad();
        
        // Take screenshot after language change
        $this->takeScreenshot('language_changed_to_filipino');
        
        // Switch back to English
        $this->waitForElement(WebDriverBy::id('language-toggle'))->click();
        $englishLink = $this->waitForElement(WebDriverBy::xpath('//a[@href="' . $this->baseUrl . '/set-language/en"]'));
        $englishLink->click();
        
        $this->waitForPageLoad();
        $this->takeScreenshot('language_changed_to_english');
    }
}
