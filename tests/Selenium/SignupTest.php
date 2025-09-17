<?php

namespace Tests\Selenium;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class SignupTest extends BaseSeleniumTest
{
    private const TEST_NAME = 'Test User';
    private const TEST_EMAIL = 'testuser@example.com';
    private const TEST_PASSWORD = 'SecurePassword123!';
    
    public function testSuccessfulSignup(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Verify we're on the signup page
        $this->assertStringContains('Register', $this->getPageTitle());
        
        // Take screenshot for debugging
        $this->takeScreenshot('signup_page_loaded');
        
        // Fill in signup form
        $this->fillInput(WebDriverBy::id('name'), self::TEST_NAME);
        $this->fillInput(WebDriverBy::id('email'), self::TEST_EMAIL);
        $this->fillInput(WebDriverBy::id('password'), self::TEST_PASSWORD);
        $this->fillInput(WebDriverBy::id('password_confirmation'), self::TEST_PASSWORD);
        
        // Take screenshot before submitting
        $this->takeScreenshot('signup_form_filled');
        
        // Submit the form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        // Wait for redirect or processing
        sleep(3); // Allow time for form processing and potential redirect
        
        // Take screenshot after signup
        $this->takeScreenshot('after_signup');
        
        $currentUrl = $this->getCurrentUrl();
        
        // Verify successful signup - could redirect to verification page, login page, or dashboard
        $this->assertTrue(
            !str_contains($currentUrl, '/register') || 
            str_contains($currentUrl, '/email/verify') ||
            str_contains($currentUrl, '/login') ||
            str_contains($currentUrl, '/dashboard'),
            "Expected to be redirected after signup, but current URL is: {$currentUrl}"
        );
    }
    
    public function testSignupWithExistingEmail(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Use the existing test email from login test
        $this->fillInput(WebDriverBy::id('name'), 'Another User');
        $this->fillInput(WebDriverBy::id('email'), 'fool@gmail.com'); // Email that already exists
        $this->fillInput(WebDriverBy::id('password'), self::TEST_PASSWORD);
        $this->fillInput(WebDriverBy::id('password_confirmation'), self::TEST_PASSWORD);
        
        // Take screenshot before submitting
        $this->takeScreenshot('existing_email_signup_form');
        
        // Submit the form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        sleep(2); // Wait for validation
        
        // Take screenshot after failed signup
        $this->takeScreenshot('after_existing_email_signup');
        
        // Should stay on register page with validation error
        $currentUrl = $this->getCurrentUrl();
        $this->assertStringContains('/register', $currentUrl, 'Should remain on register page when email already exists');
        
        // Check for validation errors
        $hasValidationErrors = $this->elementExists(WebDriverBy::className('text-red-600')) ||
                              $this->elementExists(WebDriverBy::xpath('//*[contains(@class, "error")]'));
        
        $this->assertTrue($hasValidationErrors, 'Should display validation errors for existing email');
    }
    
    public function testSignupFormValidation(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Try to submit empty form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        sleep(1); // Wait for browser validation
        
        // Take screenshot
        $this->takeScreenshot('empty_signup_form_validation');
        
        // Check that required field validation works
        $nameField = $this->driver->findElement(WebDriverBy::id('name'));
        $emailField = $this->driver->findElement(WebDriverBy::id('email'));
        $passwordField = $this->driver->findElement(WebDriverBy::id('password'));
        $confirmPasswordField = $this->driver->findElement(WebDriverBy::id('password_confirmation'));
        
        // Check if fields have required attribute
        $this->assertEquals('true', $nameField->getAttribute('required'));
        $this->assertEquals('true', $emailField->getAttribute('required'));
        $this->assertEquals('true', $passwordField->getAttribute('required'));
        $this->assertEquals('true', $confirmPasswordField->getAttribute('required'));
    }
    
    public function testPasswordMismatchValidation(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Fill form with mismatched passwords
        $this->fillInput(WebDriverBy::id('name'), self::TEST_NAME);
        $this->fillInput(WebDriverBy::id('email'), 'unique@test.com');
        $this->fillInput(WebDriverBy::id('password'), 'Password123!');
        $this->fillInput(WebDriverBy::id('password_confirmation'), 'DifferentPassword123!');
        
        // Take screenshot before submitting
        $this->takeScreenshot('password_mismatch_form');
        
        // Submit the form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        sleep(2); // Wait for validation
        
        // Take screenshot after validation
        $this->takeScreenshot('after_password_mismatch_validation');
        
        // Should stay on register page
        $currentUrl = $this->getCurrentUrl();
        $this->assertStringContains('/register', $currentUrl, 'Should remain on register page when passwords do not match');
        
        // Check for validation errors
        $hasValidationErrors = $this->elementExists(WebDriverBy::className('text-red-600')) ||
                              $this->elementExists(WebDriverBy::xpath('//*[contains(@class, "error")]'));
        
        $this->assertTrue($hasValidationErrors, 'Should display validation errors for password mismatch');
    }
    
    public function testPasswordToggleVisibility(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Find password fields and toggle buttons
        $passwordField = $this->waitForElement(WebDriverBy::id('password'));
        $confirmPasswordField = $this->waitForElement(WebDriverBy::id('password_confirmation'));
        $toggleButtons = $this->driver->findElements(WebDriverBy::className('toggle-both'));
        
        // Initially should be password type
        $this->assertEquals('password', $passwordField->getAttribute('type'));
        $this->assertEquals('password', $confirmPasswordField->getAttribute('type'));
        
        // Click first toggle button (should toggle both fields)
        $toggleButtons[0]->click();
        
        // Both should now be text type
        $this->assertEquals('text', $passwordField->getAttribute('type'));
        $this->assertEquals('text', $confirmPasswordField->getAttribute('type'));
        
        // Click again to toggle back
        $toggleButtons[1]->click();
        
        // Both should be password type again
        $this->assertEquals('password', $passwordField->getAttribute('type'));
        $this->assertEquals('password', $confirmPasswordField->getAttribute('type'));
        
        $this->takeScreenshot('signup_password_toggle_test');
    }
    
    public function testInvalidEmailFormat(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Fill form with invalid email format
        $this->fillInput(WebDriverBy::id('name'), self::TEST_NAME);
        $this->fillInput(WebDriverBy::id('email'), 'invalid-email-format');
        $this->fillInput(WebDriverBy::id('password'), self::TEST_PASSWORD);
        $this->fillInput(WebDriverBy::id('password_confirmation'), self::TEST_PASSWORD);
        
        // Take screenshot before submitting
        $this->takeScreenshot('invalid_email_format_form');
        
        // Submit the form
        $this->clickElement(WebDriverBy::xpath('//button[@type="submit"]'));
        
        sleep(2); // Wait for validation
        
        // Take screenshot after validation
        $this->takeScreenshot('after_invalid_email_validation');
        
        // Should stay on register page due to HTML5 email validation or server validation
        $currentUrl = $this->getCurrentUrl();
        $this->assertStringContains('/register', $currentUrl, 'Should remain on register page with invalid email format');
    }
    
    public function testNavigationToLoginPage(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Take screenshot
        $this->takeScreenshot('signup_page_before_navigation');
        
        // Find and click the login link in footer
        $loginLink = $this->waitForElement(WebDriverBy::xpath('//a[@href="' . $this->baseUrl . '/login"]'));
        $loginLink->click();
        
        // Wait for navigation
        $this->waitForPageLoad();
        
        // Take screenshot
        $this->takeScreenshot('navigated_to_login_from_signup');
        
        // Verify we're on login page
        $currentUrl = $this->getCurrentUrl();
        $this->assertStringContains('/login', $currentUrl, 'Should navigate to login page when clicking login link');
    }
    
    public function testTermsAndPrivacyPolicyLinks(): void
    {
        // Navigate to signup page
        $this->navigateTo('/register');
        $this->waitForPageLoad();
        
        // Check if terms and privacy policy links exist (if enabled in Jetstream)
        $termsLinks = $this->driver->findElements(WebDriverBy::partialLinkText('Terms'));
        $privacyLinks = $this->driver->findElements(WebDriverBy::partialLinkText('Privacy'));
        
        if (count($termsLinks) > 0) {
            $this->assertTrue($termsLinks[0]->isDisplayed(), 'Terms of Service link should be visible');
            // Check if link has target="_blank"
            $this->assertEquals('_blank', $termsLinks[0]->getAttribute('target'));
        }
        
        if (count($privacyLinks) > 0) {
            $this->assertTrue($privacyLinks[0]->isDisplayed(), 'Privacy Policy link should be visible');
            // Check if link has target="_blank"
            $this->assertEquals('_blank', $privacyLinks[0]->getAttribute('target'));
        }
        
        $this->takeScreenshot('terms_privacy_links_test');
    }
}
