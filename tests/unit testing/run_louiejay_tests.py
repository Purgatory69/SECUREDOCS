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
        'dashboard': [
            'User_Profile_Module.UP-UD_001_dashboard_stats',
        ],
        'navigation': [
            'User_Profile_Module.UP-N_002_navigation_menu',
        ],
        'breadcrumbs': [
            'User_Profile_Module.UP-B_003_breadcrumb_navigation',
        ],
        'language': [
            'User_Profile_Module.UP-LS_004_language_switch',
        ]
    },
    'document_management': {
        'file_upload': [
            'Document_Management_Module.File_Upload.DM-FU_001_single_upload',
            'Document_Management_Module.File_Upload.DM-FU_002_multiple_upload',
            'Document_Management_Module.File_Upload.DM-FU_003_file_restrictions',
            'Document_Management_Module.File_Upload.DM-FU_004_file_size_limits',
        ],
        'file_download': [
            'Document_Management_Module.File_Download.DM-FD_005_file_download',
        ],
        'file_preview': [
            'Document_Management_Module.File_Preview.DM-FP_006_file_preview',
        ],
        'file_rename': [
            'Document_Management_Module.File_Rename.DM-FR_007_file_rename',
        ],
        'file_delete': [
            'Document_Management_Module.File_Delete.DM-FD_008_file_soft_delete',
        ],
        'file_restore': [
            'Document_Management_Module.File_Restore.DM-FR_009_file_restore',
        ],
        'file_permanent_delete': [
            'Document_Management_Module.File_Permanent_Delete.DM-FPD_010_permanent_deletion',
        ],
        'folder_management': [
            'Document_Management_Module.Folder_Management.DM-FM_011_folder_creation',
            'Document_Management_Module.Folder_Management.DM-FM_012_folder_navigation',
            'Document_Management_Module.Folder_Management.DM-FM_013_folder_renaming',
            'Document_Management_Module.Folder_Management.DM-FM_014_empty_folder_delete',
            'Document_Management_Module.Folder_Management.DM-FM_015_non_empty_folder_delete',
            'Document_Management_Module.Folder_Management.DM-FM_016_move_files_between_folders',
        ]
    }
}

# Test case ID to module mapping
TEST_ID_MAP = {
    # User Profile Module IDs (Updated)
    'UP-UD_001': 'User_Profile_Module.UP-UD_001_dashboard_stats',
    'UP-N_002': 'User_Profile_Module.UP-N_002_navigation_menu',
    'UP-B_003': 'User_Profile_Module.UP-B_003_breadcrumb_navigation',
    'UP-LS_004': 'User_Profile_Module.UP-LS_004_language_switch',

    # Document Management Module IDs (Updated)
    'DM-FU_001': 'Document_Management_Module.File_Upload.DM-FU_001_single_upload',
    'DM-FU_002': 'Document_Management_Module.File_Upload.DM-FU_002_multiple_upload',
    'DM-FU_003': 'Document_Management_Module.File_Upload.DM-FU_003_file_restrictions',
    'DM-FU_004': 'Document_Management_Module.File_Upload.DM-FU_004_file_size_limits',
    'DM-FD_005': 'Document_Management_Module.File_Download.DM-FD_005_file_download',
    'DM-FP_006': 'Document_Management_Module.File_Preview.DM-FP_006_file_preview',
    'DM-FR_007': 'Document_Management_Module.File_Rename.DM-FR_007_file_rename',
    'DM-FD_008': 'Document_Management_Module.File_Delete.DM-FD_008_file_soft_delete',
    'DM-FR_009': 'Document_Management_Module.File_Restore.DM-FR_009_file_restore',
    'DM-FPD_010': 'Document_Management_Module.File_Permanent_Delete.DM-FPD_010_permanent_deletion',
    'DM-FM_011': 'Document_Management_Module.Folder_Management.DM-FM_011_folder_creation',
    'DM-FM_012': 'Document_Management_Module.Folder_Management.DM-FM_012_folder_navigation',
    'DM-FM_013': 'Document_Management_Module.Folder_Management.DM-FM_013_folder_renaming',
    'DM-FM_014': 'Document_Management_Module.Folder_Management.DM-FM_014_empty_folder_delete',
    'DM-FM_015': 'Document_Management_Module.Folder_Management.DM-FM_015_non_empty_folder_delete',
    'DM-FM_016': 'Document_Management_Module.Folder_Management.DM-FM_016_move_files_between_folders',
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
        # Convert hyphens to underscores for Python function names
        function_name_python = function_name.replace('-', '_')
        
        # Import the module dynamically
        module_file_path = module_path.replace('.', os.sep) + '.py'
        
        if not os.path.exists(module_file_path):
            print(f"✗ Test file not found: {module_file_path}")
            return False, 0
        
        # Load module spec and execute
        spec = importlib.util.spec_from_file_location(function_name, module_file_path)
        test_module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(test_module)
        
        # Get the main test function (try with underscores)
        test_function = getattr(test_module, function_name_python)
        
        # Run the test
        print(f"[TEST] Executing: {function_name}")
        result = test_function()
        
        # Each test is worth 1 point
        points = 1 if result else 0
        
        return result, points
        
    except ImportError as e:
        print(f"[ERROR] Failed to import {module_path}: {e}")
        return False, 0
    except AttributeError as e:
        print(f"[ERROR] Test function not found in {module_path}: {e}")
        return False, 0
    except Exception as e:
        print(f"[ERROR] Error running {module_path}: {e}")
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
        status = "[PASS]" if success else "[FAIL]"
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
        print("\n\n[STOPPED] Tests interrupted by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n[BOMB] Unexpected error: {e}")
        sys.exit(1)
    finally:
        # Always cleanup session
        session.cleanup()

if __name__ == "__main__":
    main()
