#!/usr/bin/env python3
"""
Louiejay's Module Test Runner for SecureDocs
Focused on assigned User Profile and Document Management modules

Usage:
    python run_louiejay_tests.py                           # Run all assigned tests
    python run_louiejay_tests.py user_profile              # Run User Profile Module tests
    python run_louiejay_tests.py document_management       # Run Document Management tests
    python run_louiejay_tests.py UP_001                    # Run specific test case
    python run_louiejay_tests.py dashboard                 # Run specific unit tests
"""

import sys
import time
import importlib
import importlib.util
import os
from datetime import datetime
from global_session import session

# Test modules mapping by category
LOUIEJAY_TEST_MODULES = {
    'user_profile': {
        'admin_dashboard': [
            '01_User_Profile_Modules.00_Admin_Dashboard.AD_001_admin_dashboard_loads_navigation',
            '01_User_Profile_Modules.00_Admin_Dashboard.AD_002_admin_dashboard_shows_statistics'
        ],
        'user_dashboard': [
            '01_User_Profile_Modules.01_User_Dashboard.UP_001_dashboard_loads_navigation',
            '01_User_Profile_Modules.01_User_Dashboard.UP_002_dashboard_shows_statistics'
        ],
        'file_preview': [
            '01_User_Profile_Modules.02_File_Preview.UP_003_file_preview_modal_opens',
            '01_User_Profile_Modules.02_File_Preview.UP_004_file_preview_unsupported_formats'
        ],
        'profile_settings': [
            '01_User_Profile_Modules.03_Profile_Settings.UP_005_access_profile_settings',
            '01_User_Profile_Modules.03_Profile_Settings.UP_006_update_profile_information',
            '01_User_Profile_Modules.03_Profile_Settings.UP_007_profile_photo_upload'
        ],
        'biometrics': [
            '01_User_Profile_Modules.04_Biometrics.UP_008_biometric_setup_access',
            '01_User_Profile_Modules.04_Biometrics.UP_009_webauthn_key_registration',
            '01_User_Profile_Modules.04_Biometrics.UP_010_biometric_login_functionality'
        ],
        'buy_premium': [
            '01_User_Profile_Modules.05_Buy_Premium.UP_011_premium_purchase_page',
            '01_User_Profile_Modules.05_Buy_Premium.UP_012_premium_payment_flow',
            '01_User_Profile_Modules.05_Buy_Premium.UP_013_premium_status_display'
        ]
    },
    'document_management': {
        'upload_document': [
            '02_Document_Management_Modules.01_Upload_Document.DM_001_single_document_upload',
            '02_Document_Management_Modules.01_Upload_Document.DM_002_multiple_document_upload',
            '02_Document_Management_Modules.01_Upload_Document.DM_003_file_type_restrictions',
            '02_Document_Management_Modules.01_Upload_Document.DM_004_file_size_limits'
        ],
        'view_documents': [
            '02_Document_Management_Modules.02_View_Documents.DM_005_document_list_display',
            '02_Document_Management_Modules.02_View_Documents.DM_006_document_search_filter',
            '02_Document_Management_Modules.02_View_Documents.DM_007_document_sorting_options'
        ],
        'edit_documents': [
            '02_Document_Management_Modules.03_Edit_Documents.DM_008_document_rename',
            '02_Document_Management_Modules.03_Edit_Documents.DM_009_document_metadata_editing',
            '02_Document_Management_Modules.03_Edit_Documents.DM_010_document_content_editing'
        ],
        'delete_documents': [
            '02_Document_Management_Modules.04_Delete_Documents.DM_011_document_soft_delete',
            '02_Document_Management_Modules.04_Delete_Documents.DM_012_document_restore_trash',
            '02_Document_Management_Modules.04_Delete_Documents.DM_013_permanent_document_deletion'
        ],
        'upload_to_blockchain': [
            '02_Document_Management_Modules.05_Upload_to_Blockchain.DM_014_blockchain_upload_availability',
            '02_Document_Management_Modules.05_Upload_to_Blockchain.DM_015_blockchain_upload_process',
            '02_Document_Management_Modules.05_Upload_to_Blockchain.DM_016_blockchain_upload_verification'
        ]
    }
}

# Test case ID to module mapping
TEST_ID_MAP = {
    # Admin Dashboard IDs
    'AD_001': '01_User_Profile_Modules.00_Admin_Dashboard.AD_001_admin_dashboard_loads_navigation',
    'AD_002': '01_User_Profile_Modules.00_Admin_Dashboard.AD_002_admin_dashboard_shows_statistics',
    
    # User Profile Module IDs
    'UP_001': '01_User_Profile_Modules.01_User_Dashboard.UP_001_dashboard_loads_navigation',
    'UP_002': '01_User_Profile_Modules.01_User_Dashboard.UP_002_dashboard_shows_statistics',
    'UP_003': '01_User_Profile_Modules.02_File_Preview.UP_003_file_preview_modal_opens',
    'UP_004': '01_User_Profile_Modules.02_File_Preview.UP_004_file_preview_unsupported_formats',
    'UP_005': '01_User_Profile_Modules.03_Profile_Settings.UP_005_access_profile_settings',
    'UP_006': '01_User_Profile_Modules.03_Profile_Settings.UP_006_update_profile_information',
    'UP_007': '01_User_Profile_Modules.03_Profile_Settings.UP_007_profile_photo_upload',
    'UP_008': '01_User_Profile_Modules.04_Biometrics.UP_008_biometric_setup_access',
    'UP_009': '01_User_Profile_Modules.04_Biometrics.UP_009_webauthn_key_registration',
    'UP_010': '01_User_Profile_Modules.04_Biometrics.UP_010_biometric_login_functionality',
    'UP_011': '01_User_Profile_Modules.05_Buy_Premium.UP_011_premium_purchase_page',
    'UP_012': '01_User_Profile_Modules.05_Buy_Premium.UP_012_premium_payment_flow',
    'UP_013': '01_User_Profile_Modules.05_Buy_Premium.UP_013_premium_status_display',
    
    # Document Management Module IDs
    'DM_001': '02_Document_Management_Modules.01_Upload_Document.DM_001_single_document_upload',
    'DM_002': '02_Document_Management_Modules.01_Upload_Document.DM_002_multiple_document_upload',
    'DM_003': '02_Document_Management_Modules.01_Upload_Document.DM_003_file_type_restrictions',
    'DM_004': '02_Document_Management_Modules.01_Upload_Document.DM_004_file_size_limits',
    'DM_005': '02_Document_Management_Modules.02_View_Documents.DM_005_document_list_display',
    'DM_006': '02_Document_Management_Modules.02_View_Documents.DM_006_document_search_filter',
    'DM_007': '02_Document_Management_Modules.02_View_Documents.DM_007_document_sorting_options',
    'DM_008': '02_Document_Management_Modules.03_Edit_Documents.DM_008_document_rename',
    'DM_009': '02_Document_Management_Modules.03_Edit_Documents.DM_009_document_metadata_editing',
    'DM_010': '02_Document_Management_Modules.03_Edit_Documents.DM_010_document_content_editing',
    'DM_011': '02_Document_Management_Modules.04_Delete_Documents.DM_011_document_soft_delete',
    'DM_012': '02_Document_Management_Modules.04_Delete_Documents.DM_012_document_restore_trash',
    'DM_013': '02_Document_Management_Modules.04_Delete_Documents.DM_013_permanent_document_deletion',
    'DM_014': '02_Document_Management_Modules.05_Upload_to_Blockchain.DM_014_blockchain_upload_availability',
    'DM_015': '02_Document_Management_Modules.05_Upload_to_Blockchain.DM_015_blockchain_upload_process',
    'DM_016': '02_Document_Management_Modules.05_Upload_to_Blockchain.DM_016_blockchain_upload_verification'
}

def print_header():
    """Print test runner header"""
    print("=" * 80)
    print("SecureDocs - Louiejay's Module Test Runner")
    print("User Profile Modules & Document Management Modules")
    print("=" * 80)
    print(f"Started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()

def print_footer(total_time, total_points):
    """Print test runner footer"""
    print()
    print("=" * 80)
    print(f"Tests completed at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"Total execution time: {total_time:.2f} seconds")
    print(f"Total points earned: {total_points}")
    print("=" * 80)

def run_test_case(module_path):
    """Run a single test case"""
    try:
        # Convert module path to function name
        function_name = module_path.split('.')[-1]  # Get the last part (filename without .py)
        
        # Import the module dynamically
        module_file_path = module_path.replace('.', os.sep) + '.py'
        
        if not os.path.exists(module_file_path):
            print(f"✗ Test file not found: {module_file_path}")
            return False, 0
        
        # Load module spec and execute
        spec = importlib.util.spec_from_file_location(function_name, module_file_path)
        test_module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(test_module)
        
        # Get the main test function
        test_function = getattr(test_module, function_name)
        
        # Run the test
        print(f"🧪 Executing: {function_name}")
        result = test_function()
        
        # Each test is worth 1 point
        points = 1 if result else 0
        
        return result, points
        
    except ImportError as e:
        print(f"✗ Failed to import {module_path}: {e}")
        return False, 0
    except AttributeError as e:
        print(f"✗ Test function not found in {module_path}: {e}")
        return False, 0
    except Exception as e:
        print(f"✗ Error running {module_path}: {e}")
        return False, 0

def run_unit_tests(unit_name, module_category):
    """Run all tests for a specific unit"""
    if module_category not in LOUIEJAY_TEST_MODULES:
        print(f"Unknown module category: {module_category}")
        return False, 0
    
    if unit_name not in LOUIEJAY_TEST_MODULES[module_category]:
        print(f"Unknown unit: {unit_name} in {module_category}")
        print(f"Available units: {', '.join(LOUIEJAY_TEST_MODULES[module_category].keys())}")
        return False, 0
    
    print(f"Running {unit_name.replace('_', ' ').title()} Tests")
    print("-" * 60)
    
    test_modules = LOUIEJAY_TEST_MODULES[module_category][unit_name]
    passed = 0
    total = len(test_modules)
    total_points = 0
    
    for module_path in test_modules:
        success, points = run_test_case(module_path)
        if success:
            passed += 1
        total_points += points
        print()  # Add spacing between tests
    
    print(f"{unit_name.replace('_', ' ').title()} Tests Summary: {passed}/{total} passed, {total_points} points")
    return passed == total, total_points

def run_all_module_tests():
    """Run all tests for Louiejay's assigned modules"""
    print_header()
    start_time = time.time()
    
    module_results = []
    total_points = 0
    
    # Run User Profile Module tests
    print(f"\n{'=' * 60}")
    print("RUNNING USER PROFILE MODULE TESTS (7 points)")
    print('=' * 60)
    
    up_points = 0
    up_success = True
    
    for unit_name in LOUIEJAY_TEST_MODULES['user_profile'].keys():
        success, points = run_unit_tests(unit_name, 'user_profile')
        if not success:
            up_success = False
        up_points += points
        print()
    
    module_results.append(('User Profile Modules', up_success, up_points))
    total_points += up_points
    
    # Run Document Management Module tests  
    print(f"\n{'=' * 60}")
    print("RUNNING DOCUMENT MANAGEMENT MODULE TESTS (5 points)")
    print('=' * 60)
    
    dm_points = 0
    dm_success = True
    
    for unit_name in LOUIEJAY_TEST_MODULES['document_management'].keys():
        success, points = run_unit_tests(unit_name, 'document_management')
        if not success:
            dm_success = False
        dm_points += points
        print()
    
    module_results.append(('Document Management Modules', dm_success, dm_points))
    total_points += dm_points
    
    # Print overall summary
    print(f"{'=' * 60}")
    print("OVERALL MODULE SUMMARY")
    print('=' * 60)
    
    for module_name, success, points in module_results:
        status = "✓ PASSED" if success else "✗ FAILED"
        print(f"{module_name}: {status} ({points} points)")
    
    print(f"\nTotal Points Earned: {total_points}/12 points")
    
    total_time = time.time() - start_time
    print_footer(total_time, total_points)
    
    return len([r for r in module_results if r[1]]) == len(module_results)

def main():
    """Main function"""
    try:
        if len(sys.argv) == 1:
            # Run all tests
            success = run_all_module_tests()
        elif len(sys.argv) == 2:
            arg = sys.argv[1].upper()
            
            # Check if it's a test ID (e.g., UP_001, DM_001)
            if arg in TEST_ID_MAP:
                print_header()
                start_time = time.time()
                success, points = run_test_case(TEST_ID_MAP[arg])
                status = "PASSED" if success else "FAILED"
                print(f"\n{arg}: {status} ({points} points)")
                total_time = time.time() - start_time
                print_footer(total_time, points)
            # Check if it's a module category
            elif arg.lower() in ['user_profile', 'document_management']:
                print_header()
                start_time = time.time()
                
                module_category = arg.lower()
                total_points = 0
                all_success = True
                
                for unit_name in LOUIEJAY_TEST_MODULES[module_category].keys():
                    success, points = run_unit_tests(unit_name, module_category)
                    if not success:
                        all_success = False
                    total_points += points
                    print()
                
                total_time = time.time() - start_time
                print_footer(total_time, total_points)
                success = all_success
            # Check if it's a specific unit
            else:
                unit_arg = arg.lower()
                found_unit = False
                
                for module_category in LOUIEJAY_TEST_MODULES:
                    if unit_arg in LOUIEJAY_TEST_MODULES[module_category]:
                        print_header()
                        start_time = time.time()
                        success, points = run_unit_tests(unit_arg, module_category)
                        total_time = time.time() - start_time
                        print_footer(total_time, points)
                        found_unit = True
                        break
                
                if not found_unit:
                    print("Usage:")
                    print("  python run_louiejay_tests.py                           # Run all tests")
                    print("  python run_louiejay_tests.py user_profile              # User Profile Module tests")
                    print("  python run_louiejay_tests.py document_management       # Document Management tests")
                    print("  python run_louiejay_tests.py UP_001                    # Specific test case")
                    print("  python run_louiejay_tests.py dashboard                 # Specific unit tests")
                    print()
                    print("Available test IDs:", ', '.join(sorted(TEST_ID_MAP.keys())))
                    return
        else:
            print("Too many arguments provided.")
            return
        
        # Exit with appropriate code
        sys.exit(0 if success else 1)
        
    except KeyboardInterrupt:
        print("\n\n🛑 Tests interrupted by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n💥 Unexpected error: {e}")
        sys.exit(1)
    finally:
        # Always cleanup session
        session.cleanup()

if __name__ == "__main__":
    main()
