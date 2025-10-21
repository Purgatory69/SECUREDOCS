# Louiejay's SecureDocs Module Testing

## 📋 **Test Plan Overview**
- **Total Test Cases**: 23 (Updated Louiejay Test Plan)
- **Total Points Available**: 23 points (1 point per test case)
- **Current Progress**: 17/23 IMPLEMENTED ✅ | 6/23 NOT INCLUDED ❌
- **Points Earned**: 17/23 points (based on current implementation)
- **Modules**: User Profile (5 tests) + Document Management (18 tests)

## 📊 **Progress Summary**

### ✅ **IMPLEMENTED (17/23)**
**User Profile (3/5)**:
- ✅ UP-UD 001 - Dashboard loads with user stats **[MOVED & RENAMED]**
- ✅ UP-N 002 - Main navigation menu works **[MOVED & RENAMED]**
- ✅ UP-B 003 - Breadcrumb navigation in folders **[MOVED & RENAMED]**
- ❌ UP-LS 004 - Language switching (EN/Filipino) **[NOT INCLUDED]**
- ❌ UP-RD 005 - Dashboard is mobile responsive **[NOT INCLUDED]**

**Document Management (14/18)**:
- ✅ DM-FU 001 - Single file upload functionality **[MOVED & RENAMED]**
- ✅ DM-FU 002 - Multiple file upload **[PARTIALLY IMPLEMENTED]**
- ✅ DM-FU 003 - File type restrictions **[MOVED & RENAMED]**
- ✅ DM-FU 004 - File size limits **[PARTIALLY IMPLEMENTED]**
- ❌ DM-FD 005 - File download functionality **[NOT INCLUDED - PLACEHOLDER CREATED]**
- ✅ DM-FP 006 - File preview for supported formats **[MOVED & RENAMED]**
- ✅ DM-FR 007 - File renaming functionality **[MOVED & RENAMED]**
- ✅ DM-FD 008 - File soft delete (move to trash) **[MOVED & RENAMED]**
- ✅ DM-FR 009 - File restore from trash **[MOVED & RENAMED]**
- ✅ DM-FPD 010 - Permanent file deletion **[MOVED & RENAMED]**
- ❌ DM-FM 011-016 - Complete folder management suite (6 tests) **[NOT INCLUDED - PLACEHOLDERS CREATED]**

### ❌ **NOT INCLUDED (6/23)**
- ❌ UP-LS 004 - Language switching (EN/Filipino)
- ❌ UP-RD 005 - Mobile responsive design
- ❌ DM-FD 005 - File download functionality
- ❌ DM-FM 011-016 - Folder Management (6 tests)

## 📁 **Organized Structure**

```
tests/unit testing/
├── 📋 Louiejay_Test_Plan.csv                    # Your test plan (23 cases)
├── 🚀 run_louiejay_tests.py                     # Dedicated test runner
├── 🔧 global_session.py                         # Global login system
├── 🔧 webdriver_utils.py                        # Shared webdriver
├── 🔧 test_helpers.py                           # Shared helper functions
│
├── User_Profile_Module/ (5 tests - 3 implemented, 2 not included)
│   ├── UP-UD_001_dashboard_stats.py                    ✅ IMPLEMENTED
│   ├── UP-N_002_navigation_menu.py                     ✅ IMPLEMENTED
│   ├── UP-B_003_breadcrumb_navigation.py               ✅ IMPLEMENTED
│   ├── UP-LS_004_language_switching.py                 ❌ NOT INCLUDED
│   └── UP-RD_005_responsive_design.py                   ❌ NOT INCLUDED
│
└── Document_Management_Module/ (18 tests - 12 implemented, 6 not included)
    ├── File_Upload/
    │   ├── DM-FU_001_single_upload.py                   ✅ IMPLEMENTED
    │   ├── DM-FU_002_multiple_upload.py                 ✅ PARTIALLY
    │   ├── DM-FU_003_file_restrictions.py               ✅ IMPLEMENTED
    │   └── DM-FU_004_file_size_limits.py                ✅ PARTIALLY
    ├── File_Download/
    │   └── DM-FD_005_file_download.py                   ❌ NOT INCLUDED
    ├── File_Preview/
    │   └── DM-FP_006_file_preview.py                    ✅ IMPLEMENTED
    ├── File_Rename/
    │   └── DM-FR_007_file_rename.py                     ✅ IMPLEMENTED
    ├── File_Delete/
    │   └── DM-FD_008_file_soft_delete.py                ✅ IMPLEMENTED
    ├── File_Restore/
    │   └── DM-FR_009_file_restore.py                    ✅ IMPLEMENTED
    ├── File_Permanent_Delete/
    │   └── DM-FPD_010_permanent_deletion.py             ✅ IMPLEMENTED
    └── Folder_Management/
        ├── DM-FM_011_folder_creation.py                  ❌ NOT INCLUDED
        ├── DM-FM_012_folder_navigation.py                ❌ NOT INCLUDED
        ├── DM-FM_013_folder_renaming.py                  ❌ NOT INCLUDED
        ├── DM-FM_014_empty_folder_delete.py              ❌ NOT INCLUDED
        ├── DM-FM_015_non_empty_folder_delete.py          ❌ NOT INCLUDED
        └── DM-FM_016_move_files_between_folders.py       ❌ NOT INCLUDED
```

## 🚀 **Running Your Tests**

### **Run All Your Tests**
```bash
cd "tests/unit testing"
python run_louiejay_tests.py
```

### **Run by Module**
```bash
python run_louiejay_tests.py user_profile              # All User Profile tests
python run_louiejay_tests.py document_management       # All Document Management tests
```

### **Run by Sub-Module**
```bash
python run_louiejay_tests.py dashboard                 # UP_001, UP_002
python run_louiejay_tests.py file_preview              # UP_003, UP_004
python run_louiejay_tests.py profile_settings          # UP_005, UP_006, UP_007
python run_louiejay_tests.py biometrics                # UP_008, UP_009, UP_010
python run_louiejay_tests.py buy_premium               # UP_011, UP_012, UP_013
python run_louiejay_tests.py upload_document           # DM_001, DM_002, DM_003, DM_004
python run_louiejay_tests.py view_documents            # DM_005, DM_006, DM_007
python run_louiejay_tests.py edit_documents            # DM_008, DM_009, DM_010
python run_louiejay_tests.py delete_documents          # DM_011, DM_012, DM_013
python run_louiejay_tests.py upload_to_blockchain      # DM_014, DM_015, DM_016
```

### **Run Individual Tests**
```bash
python run_louiejay_tests.py UP_001                    # Specific test case
python run_louiejay_tests.py DM_001                    # Specific test case
```

### **Run Individual Files**
```bash
cd "01_User_Profile_Modules/01_User_Dashboard"
python UP_001_dashboard_loads_navigation.py

cd "02_Document_Management_Modules/01_Upload_Document"
python DM_001_single_document_upload.py
```

## 🧪 **Complete Test Runner Instructions**

### **📋 Prerequisites**
First, install the required Python dependencies:
```bash
cd "tests"
pip install -r requirements.txt
```

---

## **🔧 Test Suite 1: Main Selenium Tests** (`run_tests.py`)

### **Run All Tests:**
```bash
cd "tests"
python run_tests.py
```

### **Run Specific Test Categories:**
```bash
# Authentication tests only
python run_tests.py auth

# File management tests only  
python run_tests.py file

# Folder management tests only
python run_tests.py folder

# Search tests only
python run_tests.py search
```

---

## **🔧 Test Suite 2: Louiejay Unit Tests** (`run_louiejay_tests.py`)

### **Run All Louiejay Tests:**
```bash
cd "tests/unit testing"
python run_louiejay_tests.py
```

### **Run Specific Modules:**
```bash
# User Profile Module tests only (7 points)
python run_louiejay_tests.py user_profile

# Document Management Module tests only (5 points)  
python run_louiejay_tests.py document_management
```

### **Run Specific Units:**
```bash
# Admin Dashboard unit tests
python run_louiejay_tests.py admin_dashboard

# User Dashboard unit tests
python run_louiejay_tests.py user_dashboard

# File Preview unit tests
python run_louiejay_tests.py file_preview

# Profile Settings unit tests
python run_louiejay_tests.py profile_settings

# Biometrics unit tests
python run_louiejay_tests.py biometrics

# Buy Premium unit tests
python run_louiejay_tests.py buy_premium

# Upload Document unit tests
python run_louiejay_tests.py upload_document

# View Documents unit tests
python run_louiejay_tests.py view_documents

# Edit Documents unit tests
python run_louiejay_tests.py edit_documents

# Delete Documents unit tests
python run_louiejay_tests.py delete_documents

# Upload to Blockchain unit tests
python run_louiejay_tests.py upload_to_blockchain
```

---

## **🎯 Run Individual Test Cases**

### **For AD_001 (Admin Dashboard Navigation):**
```bash
cd "tests/unit testing"
python run_louiejay_tests.py AD_001
```

### **Other Individual Test IDs:**
```bash
# All available test IDs:
python run_louiejay_tests.py UP_001    # User Dashboard Navigation
python run_louiejay_tests.py UP_002    # User Dashboard Statistics  
python run_louiejay_tests.py UP_003    # File Preview Modal
python run_louiejay_tests.py UP_004    # File Preview Unsupported Formats
python run_louiejay_tests.py UP_005    # Profile Settings Access
python run_louiejay_tests.py UP_006    # Update Profile Information
python run_louiejay_tests.py UP_007    # Profile Photo Upload
python run_louiejay_tests.py UP_008    # Biometric Setup Access
python run_louiejay_tests.py UP_009    # WebAuthn Key Registration
python run_louiejay_tests.py UP_010    # Biometric Login
python run_louiejay_tests.py UP_011    # Premium Purchase Page
python run_louiejay_tests.py UP_012    # Premium Payment Flow
python run_louiejay_tests.py UP_013    # Premium Status Display

python run_louiejay_tests.py AD_002    # Admin Dashboard Statistics

python run_louiejay_tests.py DM_001    # Single Document Upload
python run_louiejay_tests.py DM_002    # Multiple Document Upload
python run_louiejay_tests.py DM_003    # File Type Restrictions
python run_louiejay_tests.py DM_004    # File Size Limits
python run_louiejay_tests.py DM_005    # Document List Display
python run_louiejay_tests.py DM_006    # Document Search Filter
python run_louiejay_tests.py DM_007    # Document Sorting Options
python run_louiejay_tests.py DM_008    # Document Rename
python run_louiejay_tests.py DM_009    # Document Metadata Editing
python run_louiejay_tests.py DM_010    # Document Content Editing
python run_louiejay_tests.py DM_011    # Document Soft Delete
python run_louiejay_tests.py DM_012    # Document Restore from Trash
python run_louiejay_tests.py DM_013    # Permanent Document Deletion
python run_louiejay_tests.py DM_014    # Blockchain Upload Availability
python run_louiejay_tests.py DM_015    # Blockchain Upload Process
python run_louiejay_tests.py DM_016    # Blockchain Upload Verification
```

---

## **⚙️ Configuration Notes**

### **Browser Setup:**
- Tests use Selenium WebDriver with Chrome
- `webdriver-manager` automatically handles driver downloads
- Make sure Chrome browser is installed

### **Test Environment:**
- Tests expect a local SecureDocs instance running
- Default URL: `http://localhost:8000`
- Modify URLs in test files if needed

### **Test Data:**
- Tests use the `global_session.py` for shared browser sessions
- Some tests require specific user accounts/data to be set up

### **Test Results:**
- ✅ **Green checkmarks** = Tests passed
- ❌ **Red X marks** = Tests failed  
- **Point system**: Louiejay tests award points (max 12 total)

---

## **🚀 Quick Start for AD_001**

To run just the AD_001 test you asked about:

```bash
cd "tests/unit testing"
python run_louiejay_tests.py AD_001
```

This will run only the "Admin Dashboard Loads Navigation" test and show you the results! 🎯

## 📊 **Progress Tracking**

The test runner will show:
- Points earned per test (1 point each)
- Module completion status
- Overall progress toward 23/23 points

You now have a complete, organized test structure ready for implementation and testing!

## ✅ **Implementation Status**

### **Fully Implemented & Tested (17/23)**
- ✅ UP-UD 001 - Dashboard loads with user stats
- ✅ UP-N 002 - Main navigation menu works
- ✅ UP-B 003 - Breadcrumb navigation in folders
- ✅ DM-FU 001 - Single file upload functionality
- ✅ DM-FU 002 - Multiple file upload **[PARTIAL]**
- ✅ DM-FU 003 - File type restrictions
- ✅ DM-FU 004 - File size limits **[PARTIAL]**
- ✅ DM-FP 006 - File preview for supported formats
- ✅ DM-FR 007 - File renaming functionality
- ✅ DM-FD 008 - File soft delete (move to trash)
- ✅ DM-FR 009 - File restore from trash
- ✅ DM-FPD 010 - Permanent file deletion

### **Not Included (6/23)**
- ❌ UP-LS 004 - Language switching (EN/Filipino)
- ❌ UP-RD 005 - Mobile responsive design
- ❌ DM-FD 005 - File download functionality
- ❌ DM-FM 011-016 - Complete folder management suite (6 tests)

### **Migration Notes**
- **Admin Dashboard Tests** (AD_001, AD_002) - Not included in new test plan
- **Premium Features** (UP_009-013, DM_014-016) - Not included in new test plan
- **Biometric Tests** (UP_008-010) - Partially included (UP_008 implemented, others not in new plan)
- **Current Implementation**: 17/23 tests working from previous system
- **Missing Features**: 6 new tests need implementation
