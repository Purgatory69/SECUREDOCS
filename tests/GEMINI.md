# GEMINI.md - SecureDocs Test Suite

This document provides a comprehensive overview of the SecureDocs test suite, including its structure, technologies, and instructions for running the various tests.

## Project Overview

This is a comprehensive test suite for the "SecureDocs" web application. It's a hybrid project with three main parts:

1.  **Python Selenium Tests (`tests/Selenium`):** A focused test suite for the authentication system (login and signup).
2.  **Louiejay's Python Modules (`tests/unit testing`):** An extensive, modular Python-based test suite covering User Profile and Document Management features. This suite is highly structured and uses a points-based system for tracking progress.
3.  **PHPUnit Tests (`tests/Feature` and `tests/Unit`):** A standard Laravel testing setup for feature and unit tests.

The application under test is expected to be a Laravel application running at `http://localhost:8000`.

## Building and Running

### Python Tests

**1. Installation:**

First, install the required Python dependencies from the two `requirements.txt` files:

```bash
pip install -r C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\requirements.txt
pip install -r C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\Selenium\requirements.txt
```

**2. Running the Tests:**

There are three main ways to run the Python tests:

*   **Run all top-level tests:**

    ```bash
    python C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\run_tests.py
    ```

*   **Run specific top-level modules:**

    ```bash
    python C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\run_tests.py auth
    python C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\run_tests.py file
    python C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\run_tests.py folder
    python C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\run_tests.py search
    ```

*   **Run the Selenium authentication tests:**

    ```bash
    python C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\Selenium\run_all_tests.py
    ```

*   **Run "Louiejay's Modules" tests:**

    ```bash
    # Run all of Louiejay's tests
    python "C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\unit testing\run_louiejay_tests.py"

    # Run a specific module
    python "C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\unit testing\run_louiejay_tests.py" user_profile

    # Run a specific test case by ID
    python "C:\Users\LENOVO\Desktop\codes\SECUREDOCS\tests\unit testing\run_louiejay_tests.py" UP-UD_001
    ```

### PHP Tests

**1. Installation:**

Assuming a standard Laravel setup, install the Composer dependencies:

```bash
composer install
```

**2. Running the Tests:**

Run the PHPUnit tests using the following command from the project root:

```bash
./vendor/bin/phpunit
```

## Development Conventions

*   The project is organized into distinct testing modules, each with its own runner and test files.
*   The Python Selenium tests inherit from a `BaseTest` class, which provides common functionality for setting up and tearing down the WebDriver.
*   "Louiejay's Modules" are highly structured, with a clear naming convention and a mapping of test IDs to test files. This suite also includes a `global_session.py` for managing a shared browser session across tests.
*   The test runners provide detailed output, including success rates, execution times, and, in the case of "Louiejay's Modules," a points-based scoring system.

## Debugging and Best Practices for Selenium Tests

This section outlines common issues encountered during Selenium test development and provides best practices to ensure robust and reliable tests.

### Debugging Process Summary

During the development of `test_language_change.py`, several issues were encountered and resolved:

1.  **`[WinError 193] %1 is not a valid Win32 application`**: This error indicated a problem with the `chromedriver.exe`. It was resolved by:
    *   Clearing the `webdriver-manager` cache (`Remove-Item -Recurse -Force "C:\Users\LENOVO\.wdm"`).
    *   Manually constructing the `chromedriver.exe` path using `os.path.join(os.path.dirname(wdm_path), "chromedriver.exe")` as `ChromeDriverManager().install()` was returning a malformed path.
2.  **`SyntaxError` and `IndentationError`**: These were introduced during manual edits and were resolved by carefully correcting the Python syntax and indentation.
3.  **`selenium.common.exceptions.TimeoutException`**: This occurred when elements were not found or not clickable within the specified timeout. This was resolved by:
    *   Using more robust XPath selectors (e.g., `//a[@href='/login']` instead of `//a[text()='LOGIN']`).
    *   Adding `WebDriverWait` for elements to be present, visible, or clickable.
    *   Adding `WebDriverWait` for URL changes (e.g., `EC.url_contains("/dashboard")`) to ensure page navigation is complete.
4.  **`selenium.common.exceptions.ElementClickInterceptedException`**: This happened when another element (e.g., an overlay or parent form) intercepted a click. This was resolved by using JavaScript to click the element directly (`driver.execute_script("arguments[0].click();", element)`).
5.  **Google Password Safety Alert**: A browser alert from Google interfered with test execution. This was handled by waiting for the alert to be present and then dismissing it (`WebDriverWait(driver, 5).until(EC.alert_is_present()); alert.dismiss()`)

### Suggestions for Creating Robust Unit Tests

To create reliable and maintainable Selenium unit tests, consider the following best practices:

*   **Explicit Waits (`WebDriverWait`)**: Always use `WebDriverWait` with `expected_conditions` to wait for elements to be in the desired state (e.g., `presence_of_element_located`, `element_to_be_clickable`, `visibility_of_element_located`). Avoid using `time.sleep()` unless absolutely necessary for debugging or specific timing scenarios.
*   **Robust Locators**:
    *   **Prioritize IDs**: If an element has a unique `id` attribute, use `By.ID` as it's the fastest and most reliable locator.
    *   **Specific CSS Selectors**: Use CSS selectors that are unique and less likely to change.
    *   **Precise XPaths**: When using XPath, be as specific as possible. Avoid generic XPaths. Use attributes like `href`, `data-*`, or specific text content. For example, `//a[@href='https://securedocs.live/set-language/fil']` is more robust than `//a[contains(text(), 'Filipino')]` if the text might change.
    *   **Avoid relying solely on text**: Text content can change due to localization or minor UI updates. Combine text with other attributes for more robust selectors.
*   **Handle Browser Alerts**: If your application triggers browser alerts (e.g., JavaScript `alert()`, `confirm()`, `prompt()`), use `WebDriverWait(driver, timeout).until(EC.alert_is_present())` to wait for them and then interact using `driver.switch_to.alert.accept()` or `driver.switch_to.alert.dismiss()`.
*   **JavaScript Clicks for Intercepted Elements**: If you encounter `ElementClickInterceptedException`, try clicking the element using JavaScript: `driver.execute_script("arguments[0].click();", element)`. This can often bypass issues where an element is visually obscured but functionally present.
*   **Page Navigation Waits**: After performing actions that lead to a page navigation (e.g., clicking a login button), wait for the new page to load. This can be done by waiting for a specific element on the new page, or by waiting for the URL to change (`EC.url_contains("expected_path")`).
*   **Debugging with Snapshots**: When encountering issues with element visibility or interaction, take a snapshot of the page's HTML content (`driver.execute_script("return document.documentElement.outerHTML")`) and save it to a file. This allows you to inspect the page's structure and identify potential problems.
*   **Driver Management**: Avoid hardcoding `chromedriver` versions. Use libraries like `webdriver-manager` to automatically download and manage the correct WebDriver executable for your browser version.
*   **Integration with Existing Frameworks**: If a project has an existing test framework (like "Louiejay's Modules" with `global_session.py`), integrate new tests into that framework. This ensures consistent WebDriver management, session handling, and adherence to project conventions.
*   **Provide Specific Identifiers**: When creating new tests or reporting issues, provide specific identifiers for elements such as:
    *   **IDs**: `id="someElementId"`
    *   **Class Names**: `class="some-class-name"`
    *   **`data-*` attributes**: `data-test-id="login-button"`
    *   **Exact text content**: For buttons or links, `text()='LOGIN'` or `text()='Filipino'`.
    *   **`href` attributes**: For links, `href="https://securedocs.live/some-link"`

By following these guidelines, you can create more robust, reliable, and easier-to-debug Selenium tests.