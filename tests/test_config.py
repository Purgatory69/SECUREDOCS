"""Shared configuration for SecureDocs automated tests.

Update these values when the default test document changes so
all dependent tests pick up the new target automatically.
"""

import os

# Primary test document metadata used across document-management tests
TARGET_FILE_ID = "192"
TARGET_FILE_NAME = "Louiejay_Test_Plan.csv"

# Primary test folder metadata used across document-management tests
TARGET_FOLDER_ID = "159"
TARGET_FOLDER_NAME = "PDF's"

# Computed helpers
TARGET_FILE_SELECTOR = f"[data-item-id='{TARGET_FILE_ID}']"
TARGET_FOLDER_SELECTOR = f"[data-item-id='{TARGET_FOLDER_ID}']"

# Path to the test file
TARGET_FILE_PATH = os.path.abspath(os.path.join(os.path.dirname(__file__), 'unit testing', 'Louiejay_Test_Plan.csv'))


__all__ = [
    "TARGET_FILE_ID",
    "TARGET_FILE_NAME",
    "TARGET_FILE_SELECTOR",
    "TARGET_FOLDER_ID",
    "TARGET_FOLDER_NAME",
    "TARGET_FOLDER_SELECTOR",
    "TARGET_FILE_PATH",
]
