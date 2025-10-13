# Updated Test Structure - User vs Admin Dashboard

## 🎯 **Enhanced Test Plan (29 Test Cases Total)**

### **Admin vs User Dashboard Separation**

I've separated the dashboard tests to properly handle both **admin** and **user** account types:

## 📁 **Updated Structure**

```
cd "tests/unit testing"
├── 📋 Louiejay_Test_Plan.csv                         # Updated with 29 test cases
├── 🚀 run_louiejay_tests.py                          # Updated test runner
├── 🔧 global_session.py                              # Enhanced with admin/user login
├──
├── 01_User_Profile_Modules/ (15 tests = 7 points total)
│   ├── 00_Admin_Dashboard/                           # NEW: Admin Dashboard Tests
│   │   ├── AD_001_admin_dashboard_loads_navigation.py        ✅ Created
│   │   └── AD_002_admin_dashboard_shows_statistics.py       ✅ Created
│   ├── 01_User_Dashboard/                            # UPDATED: User Dashboard Tests
│   │   ├── UP_001_dashboard_loads_navigation.py             ✅ Updated (user-specific)
│   │   └── UP_002_dashboard_shows_statistics.py            ✅ Updated (user-specific)
│   ├── 02_File_Preview/ ... (unchanged)
│   ├── 03_Profile_Settings/ ... (unchanged)
│   ├── 04_Biometrics/ ... (unchanged)
│   └── 05_Buy_Premium/ ... (unchanged)
│
└── 02_Document_Management_Modules/ (14 tests = 5 points total)
    └── ... (unchanged)
```

## 🔐 **Account Type Handling**

### **Global Session Updates:**
- ✅ **User Credentials**: `premium@example.com` / `password`
- ✅ **Admin Credentials**: `admin@example.com` / `admin_password`
- ✅ **Account Type Switching**: Automatically switches between user/admin sessions
- ✅ **URL Detection**: User dashboard vs Admin dashboard detection

### **Test Case Differences:**

#### **Admin Dashboard Tests (AD_001, AD_002):**
- 🔑 Login with admin credentials
- 🌐 Navigate to `/admin/dashboard`
- 🔍 Look for admin-specific elements:
  - Admin navigation items (user management, system stats)
  - System-wide statistics
  - User management metrics
  - Admin-only features

#### **User Dashboard Tests (UP_001, UP_002):**
- 👤 Login with user credentials
- 🌐 Navigate to `/dashboard`
- 🔍 Look for user-specific elements:
  - User navigation (no admin items)
  - Personal storage usage
  - User-specific metrics
  - ❌ **Security Check**: Ensure NO admin items visible

## 🚀 **Running Tests**

### **Run All Tests (29 cases)**
```bash
python run_louiejay_tests.py
```

### **Run by Account Type**
```bash
python run_louiejay_tests.py admin_dashboard        # AD_001, AD_002
python run_louiejay_tests.py user_dashboard         # UP_001, UP_002
```

### **Run Specific Tests**
```bash
python run_louiejay_tests.py AD_001                 # Admin dashboard navigation
python run_louiejay_tests.py AD_002                 # Admin dashboard statistics
python run_louiejay_tests.py UP_001                 # User dashboard navigation
python run_louiejay_tests.py UP_002                 # User dashboard statistics
```

## ✅ **Key Security Features**

### **User Dashboard Security (UP_001):**
- ✅ Verifies NO admin navigation items present
- ✅ Ensures URL doesn't contain "admin"
- ✅ Validates user-only content displayed

### **Admin Dashboard Access (AD_001):**
- ✅ Verifies admin-specific navigation
- ✅ Ensures URL contains "admin" 
- ✅ Validates admin-only features visible

## 📊 **Updated Point System**

- **Total Points**: **29 points** (was 27)
- **User Profile Module**: **7 points** (was 5)
  - Admin Dashboard: 2 points
  - User Dashboard: 2 points  
  - File Preview: 2 points
  - Profile Settings: 3 points
  - Biometrics: 3 points
  - Buy Premium: 3 points
- **Document Management**: **5 points** (unchanged)

## 🔧 **Technical Implementation**

### **Enhanced global_session.py:**
```python
# Login with account type
session.login(account_type="admin")    # Admin login
session.login(account_type="user")     # User login

# Navigate to appropriate dashboard
session.navigate_to_dashboard(account_type="admin")  # /admin/dashboard
session.navigate_to_dashboard(account_type="user")   # /dashboard
```

### **Automatic Session Management:**
- ✅ Switches between user/admin automatically
- ✅ Maintains separate login states
- ✅ Handles URL routing correctly
- ✅ Cleans up sessions properly

You now have comprehensive admin/user dashboard testing with proper security validation and account separation!
