<?php

namespace Tests\Selenium;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use PHPUnit\Framework\TestCase;

abstract class BaseSeleniumTest extends TestCase
{
    protected RemoteWebDriver $driver;
    protected WebDriverWait $wait;
    protected string $baseUrl = 'http://localhost:8000';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up Chrome options
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments([
            '--headless', // Run in headless mode
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--window-size=1920,1080',
            '--disable-web-security',
            '--ignore-certificate-errors',
            '--ignore-ssl-errors',
            '--ignore-certificate-errors-spki-list'
        ]);
        
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        
        // Initialize the WebDriver
        $this->driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities, 5000, 5000);
        $this->wait = new WebDriverWait($this->driver, 10);
    }
    
    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
        parent::tearDown();
    }
    
    /**
     * Navigate to a specific URL
     */
    protected function navigateTo(string $path): void
    {
        $this->driver->get($this->baseUrl . $path);
    }
    
    /**
     * Wait for an element to be present and visible
     */
    protected function waitForElement(WebDriverBy $locator, int $timeoutInSeconds = 10): \Facebook\WebDriver\WebDriverElement
    {
        $wait = new WebDriverWait($this->driver, $timeoutInSeconds);
        return $wait->until(WebDriverExpectedCondition::visibilityOfElementLocated($locator));
    }
    
    /**
     * Wait for an element to be clickable
     */
    protected function waitForClickableElement(WebDriverBy $locator, int $timeoutInSeconds = 10): \Facebook\WebDriver\WebDriverElement
    {
        $wait = new WebDriverWait($this->driver, $timeoutInSeconds);
        return $wait->until(WebDriverExpectedCondition::elementToBeClickable($locator));
    }
    
    /**
     * Fill an input field
     */
    protected function fillInput(WebDriverBy $locator, string $value): void
    {
        $element = $this->waitForElement($locator);
        $element->clear();
        $element->sendKeys($value);
    }
    
    /**
     * Click an element
     */
    protected function clickElement(WebDriverBy $locator): void
    {
        $element = $this->waitForClickableElement($locator);
        $element->click();
    }
    
    /**
     * Get current page title
     */
    protected function getPageTitle(): string
    {
        return $this->driver->getTitle();
    }
    
    /**
     * Get current URL
     */
    protected function getCurrentUrl(): string
    {
        return $this->driver->getCurrentURL();
    }
    
    /**
     * Check if element exists
     */
    protected function elementExists(WebDriverBy $locator): bool
    {
        try {
            $this->driver->findElement($locator);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Take a screenshot for debugging
     */
    protected function takeScreenshot(string $filename): void
    {
        $screenshotPath = __DIR__ . '/screenshots/' . $filename . '_' . date('Y-m-d_H-i-s') . '.png';
        
        // Create screenshots directory if it doesn't exist
        if (!is_dir(dirname($screenshotPath))) {
            mkdir(dirname($screenshotPath), 0777, true);
        }
        
        $this->driver->takeScreenshot($screenshotPath);
    }
    
    /**
     * Wait for page to load completely
     */
    protected function waitForPageLoad(int $timeoutInSeconds = 10): void
    {
        $wait = new WebDriverWait($this->driver, $timeoutInSeconds);
        $wait->until(function() {
            return $this->driver->executeScript('return document.readyState') === 'complete';
        });
    }
}
