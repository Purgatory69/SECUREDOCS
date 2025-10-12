from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def web_driver():
    options = webdriver.ChromeOptions()
    options.add_argument('--verbose')
    options.add_argument('--no-sandbox')
    options.add_argument('--headless')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--window-size=1920,1200')
    options.add_argument('--lang=en')
    driver = webdriver.Chrome(options=options)
    return driver

def wait_for_element(driver, by, value, timeout=10):
    """Helper function to wait for elements"""
    return WebDriverWait(driver, timeout).until(
        EC.presence_of_element_located((by, value))
    )

def wait_for_clickable(driver, by, value, timeout=10):
    """Helper function to wait for clickable elements"""
    return WebDriverWait(driver, timeout).until(
        EC.element_to_be_clickable((by, value))
    )
