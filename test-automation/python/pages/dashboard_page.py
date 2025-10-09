"""
Page Object Model for SecureDocs Dashboard Page
Covers test cases: DASH_001 to DASH_005 and navigation functionality
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from .base_page import BasePage


class DashboardPage(BasePage):
    """Dashboard page object model class"""
    
    # Page URL
    DASHBOARD_URL = "/user/dashboard"
    
    # Navigation Elements
    LOGO = (By.CSS_SELECTOR, ".navbar-brand")
    USER_MENU = (By.CSS_SELECTOR, ".user-menu")
    LOGOUT_BUTTON = (By.LINK_TEXT, "Logout")
    PROFILE_LINK = (By.LINK_TEXT, "Profile")
    SETTINGS_LINK = (By.LINK_TEXT, "Settings")
    
    # Main Navigation Menu
    DASHBOARD_NAV = (By.CSS_SELECTOR, "a[href*='dashboard']")
    FILES_NAV = (By.CSS_SELECTOR, "a[href*='files']")
    SEARCH_NAV = (By.CSS_SELECTOR, "a[href*='search']")
    ADMIN_NAV = (By.CSS_SELECTOR, "a[href*='admin']")
    
    # Dashboard Content
    WELCOME_MESSAGE = (By.CSS_SELECTOR, ".welcome-message, .dashboard-welcome")
    STORAGE_USAGE_WIDGET = (By.CSS_SELECTOR, ".storage-usage")
    RECENT_FILES_WIDGET = (By.CSS_SELECTOR, ".recent-files")
    ACTIVITY_TIMELINE = (By.CSS_SELECTOR, ".activity-timeline")
    QUICK_ACTIONS = (By.CSS_SELECTOR, ".quick-actions")
    
    # Storage Information
    STORAGE_USED = (By.CSS_SELECTOR, ".storage-used")
    STORAGE_TOTAL = (By.CSS_SELECTOR, ".storage-total")
    STORAGE_PERCENTAGE = (By.CSS_SELECTOR, ".storage-percentage")
    STORAGE_PROGRESS_BAR = (By.CSS_SELECTOR, ".progress-bar")
    
    # Recent Files
    RECENT_FILES_LIST = (By.CSS_SELECTOR, ".recent-files-list")
    RECENT_FILE_ITEM = (By.CSS_SELECTOR, ".recent-file-item")
    VIEW_ALL_FILES_LINK = (By.LINK_TEXT, "View All Files")
    
    # Quick Actions
    UPLOAD_FILE_BTN = (By.CSS_SELECTOR, ".btn-upload, #upload-btn")
    CREATE_FOLDER_BTN = (By.CSS_SELECTOR, ".btn-create-folder")
    SEARCH_FILES_BTN = (By.CSS_SELECTOR, ".btn-search")
    
    # Language Switcher
    LANGUAGE_DROPDOWN = (By.CSS_SELECTOR, ".language-selector")
    ENGLISH_OPTION = (By.CSS_SELECTOR, "option[value='en']")
    FILIPINO_OPTION = (By.CSS_SELECTOR, "option[value='fil']")
    
    # Breadcrumbs
    BREADCRUMB_NAV = (By.CSS_SELECTOR, ".breadcrumb")
    BREADCRUMB_ITEMS = (By.CSS_SELECTOR, ".breadcrumb-item")
    BREADCRUMB_ACTIVE = (By.CSS_SELECTOR, ".breadcrumb-item.active")
    
    # Mobile Navigation
    MOBILE_MENU_TOGGLE = (By.CSS_SELECTOR, ".navbar-toggler")
    MOBILE_NAV_MENU = (By.CSS_SELECTOR, ".navbar-collapse")
    
    # Notifications
    NOTIFICATION_AREA = (By.CSS_SELECTOR, ".notification-area")
    ALERT_SUCCESS = (By.CSS_SELECTOR, ".alert-success")
    ALERT_ERROR = (By.CSS_SELECTOR, ".alert-danger")
    ALERT_WARNING = (By.CSS_SELECTOR, ".alert-warning")
    
    # Premium Features
    PREMIUM_BADGE = (By.CSS_SELECTOR, ".premium-badge")
    UPGRADE_BANNER = (By.CSS_SELECTOR, ".upgrade-banner")
    PREMIUM_FEATURES_SECTION = (By.CSS_SELECTOR, ".premium-features")
    
    def __init__(self, driver):
        super().__init__(driver)
        
    def navigate_to_dashboard(self):
        """Navigate to dashboard page"""
        self.driver.get(self.base_url + self.DASHBOARD_URL)
        self.wait_for_dashboard_load()
        
    def wait_for_dashboard_load(self):
        """Wait for dashboard page to fully load"""
        self.wait_for_element_visible(self.WELCOME_MESSAGE)
        self.wait_for_page_load()
        
    def is_dashboard_loaded(self, timeout=10):
        """
        Check if dashboard is loaded
        Test Case: DASH_001
        """
        try:
            self.wait_for_element_visible(self.WELCOME_MESSAGE, timeout)
            return self.is_element_visible(self.STORAGE_USAGE_WIDGET, timeout=2)
        except TimeoutException:
            return False
            
    def get_welcome_message(self):
        """
        Get welcome message text
        Test Case: DASH_001
        """
        try:
            return self.get_text(self.WELCOME_MESSAGE)
        except:
            return ""
            
    def is_user_logged_in(self):
        """Check if user is logged in by checking user menu"""
        return self.is_element_visible(self.USER_MENU, timeout=3)
        
    def click_logout(self):
        """
        Perform logout
        Test Case: AUTH_010
        """
        self.click_element(self.USER_MENU)
        self.wait_for_element_visible(self.LOGOUT_BUTTON)
        self.click_element(self.LOGOUT_BUTTON)
        
    # Navigation Methods
    
    def click_files_navigation(self):
        """
        Navigate to files page
        Test Case: DASH_002
        """
        self.click_element(self.FILES_NAV)
        
    def click_search_navigation(self):
        """
        Navigate to search page
        Test Case: DASH_002
        """
        self.click_element(self.SEARCH_NAV)
        
    def click_admin_navigation(self):
        """
        Navigate to admin panel (if available)
        Test Case: DASH_002
        """
        if self.is_element_visible(self.ADMIN_NAV, timeout=2):
            self.click_element(self.ADMIN_NAV)
            return True
        return False
        
    def verify_navigation_menu(self):
        """
        Verify all navigation menu items are present and clickable
        Test Case: DASH_002
        """
        nav_items = [
            self.DASHBOARD_NAV,
            self.FILES_NAV,
            self.SEARCH_NAV
        ]
        
        for nav_item in nav_items:
            if not self.is_element_visible(nav_item, timeout=2):
                return False
                
        return True
        
    # Breadcrumb Methods
    
    def get_breadcrumb_items(self):
        """
        Get breadcrumb navigation items
        Test Case: DASH_003
        """
        try:
            items = self.find_elements(self.BREADCRUMB_ITEMS)
            return [item.text for item in items]
        except:
            return []
            
    def get_active_breadcrumb(self):
        """
        Get current active breadcrumb
        Test Case: DASH_003
        """
        try:
            return self.get_text(self.BREADCRUMB_ACTIVE)
        except:
            return ""
            
    def click_breadcrumb_item(self, item_text):
        """
        Click specific breadcrumb item
        Test Case: DASH_003
        """
        breadcrumb_items = self.find_elements(self.BREADCRUMB_ITEMS)
        for item in breadcrumb_items:
            if item_text in item.text:
                item.click()
                return True
        return False
        
    # Language Switching
    
    def switch_language(self, language='en'):
        """
        Switch interface language
        Test Case: DASH_004
        """
        if self.is_element_visible(self.LANGUAGE_DROPDOWN, timeout=3):
            self.select_dropdown_by_value(self.LANGUAGE_DROPDOWN, language)
            return True
        return False
        
    def get_current_language(self):
        """Get currently selected language"""
        try:
            dropdown = self.find_element(self.LANGUAGE_DROPDOWN)
            return dropdown.get_attribute('value')
        except:
            return None
            
    # Storage Information
    
    def get_storage_usage(self):
        """
        Get storage usage information
        Test Case: DASH_001
        """
        try:
            used = self.get_text(self.STORAGE_USED)
            total = self.get_text(self.STORAGE_TOTAL)
            percentage = self.get_text(self.STORAGE_PERCENTAGE)
            
            return {
                'used': used,
                'total': total,
                'percentage': percentage
            }
        except:
            return {}
            
    def get_storage_percentage_value(self):
        """Get storage usage percentage as number"""
        try:
            percentage_text = self.get_text(self.STORAGE_PERCENTAGE)
            return float(percentage_text.replace('%', ''))
        except:
            return 0.0
            
    # Recent Files
    
    def get_recent_files_count(self):
        """
        Get number of recent files displayed
        Test Case: DASH_001
        """
        try:
            files = self.find_elements(self.RECENT_FILE_ITEM)
            return len(files)
        except:
            return 0
            
    def get_recent_files_list(self):
        """Get list of recent files"""
        try:
            files = self.find_elements(self.RECENT_FILE_ITEM)
            return [file.text for file in files]
        except:
            return []
            
    def click_view_all_files(self):
        """Click view all files link"""
        self.click_element(self.VIEW_ALL_FILES_LINK)
        
    # Quick Actions
    
    def click_upload_file(self):
        """Click upload file button"""
        self.click_element(self.UPLOAD_FILE_BTN)
        
    def click_create_folder(self):
        """Click create folder button"""
        self.click_element(self.CREATE_FOLDER_BTN)
        
    def click_search_files(self):
        """Click search files button"""
        self.click_element(self.SEARCH_FILES_BTN)
        
    def verify_quick_actions_available(self):
        """Verify quick action buttons are available"""
        return (self.is_element_visible(self.UPLOAD_FILE_BTN, timeout=3) and
                self.is_element_visible(self.CREATE_FOLDER_BTN, timeout=3))
                
    # Mobile Responsive Testing
    
    def is_mobile_menu_visible(self):
        """
        Check if mobile menu toggle is visible
        Test Case: DASH_005
        """
        return self.is_element_visible(self.MOBILE_MENU_TOGGLE, timeout=3)
        
    def toggle_mobile_menu(self):
        """
        Toggle mobile navigation menu
        Test Case: DASH_005
        """
        if self.is_mobile_menu_visible():
            self.click_element(self.MOBILE_MENU_TOGGLE)
            return True
        return False
        
    def is_mobile_nav_expanded(self):
        """Check if mobile navigation is expanded"""
        try:
            nav_menu = self.find_element(self.MOBILE_NAV_MENU)
            return 'show' in nav_menu.get_attribute('class')
        except:
            return False
            
    # Notifications
    
    def get_success_message(self):
        """Get success notification message"""
        try:
            return self.get_text(self.ALERT_SUCCESS)
        except:
            return ""
            
    def get_error_message(self):
        """Get error notification message"""
        try:
            return self.get_text(self.ALERT_ERROR)
        except:
            return ""
            
    def get_warning_message(self):
        """Get warning notification message"""
        try:
            return self.get_text(self.ALERT_WARNING)
        except:
            return ""
            
    def dismiss_notification(self):
        """Dismiss active notification"""
        close_buttons = self.driver.find_elements(By.CSS_SELECTOR, ".alert .close, .alert .btn-close")
        for button in close_buttons:
            if button.is_displayed():
                button.click()
                break
                
    # Premium Features
    
    def is_premium_user(self):
        """Check if user has premium status"""
        return self.is_element_visible(self.PREMIUM_BADGE, timeout=3)
        
    def is_upgrade_banner_visible(self):
        """Check if upgrade to premium banner is visible"""
        return self.is_element_visible(self.UPGRADE_BANNER, timeout=3)
        
    def get_premium_features_info(self):
        """Get premium features section information"""
        try:
            return self.get_text(self.PREMIUM_FEATURES_SECTION)
        except:
            return ""
            
    # Profile and Settings
    
    def click_profile_link(self):
        """Navigate to user profile"""
        self.click_element(self.USER_MENU)
        self.wait_for_element_visible(self.PROFILE_LINK)
        self.click_element(self.PROFILE_LINK)
        
    def click_settings_link(self):
        """Navigate to user settings"""
        self.click_element(self.USER_MENU)
        self.wait_for_element_visible(self.SETTINGS_LINK)
        self.click_element(self.SETTINGS_LINK)
        
    # Activity Timeline
    
    def get_activity_count(self):
        """Get number of activities in timeline"""
        try:
            activities = self.driver.find_elements(By.CSS_SELECTOR, ".activity-timeline .activity-item")
            return len(activities)
        except:
            return 0
            
    def get_latest_activity(self):
        """Get latest activity from timeline"""
        try:
            latest = self.driver.find_element(By.CSS_SELECTOR, ".activity-timeline .activity-item:first-child")
            return latest.text
        except:
            return ""
            
    # Utility Methods
    
    def refresh_dashboard(self):
        """Refresh dashboard page"""
        self.refresh_page()
        self.wait_for_dashboard_load()
        
    def get_page_load_time(self):
        """Get dashboard page load time"""
        return self.execute_script("""
            return window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
        """) / 1000.0  # Convert to seconds
