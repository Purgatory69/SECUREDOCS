#!/usr/bin/env python3
"""
Individual Test Case Runner for SecureDocs
Usage:
    python run_individual_tests.py                    # Run all test cases
    python run_individual_tests.py auth               # Run authentication tests only
    python run_individual_tests.py file               # Run file management tests only
    python run_individual_tests.py folder             # Run folder management tests only
    python run_individual_tests.py search             # Run search tests only
    python run_individual_tests.py AUTH_001           # Run specific test case
"""

import sys
import time
import importlib
import os
from datetime import datetime
from global_session import session

# Test case mapping by category
TEST_CASES = {
    'auth': [
        'test_case_successful_login',
        'test_case_invalid_email_login', 
        'test_case_invalid_password_login',
        'test_case_empty_fields_login',
        'test_case_user_logout'
    ],
    'file': [
        'test_case_single_file_upload',
        # Add more file test cases here
    ],
    'folder': [
        'test_case_create_folder',
        # Add more folder test cases here
    ],
    'search': [
        'test_case_basic_search',
        # Add more search test cases here
    ]
}

# Test case ID to module mapping
TEST_ID_MAP = {
    'AUTH_001': 'test_case_successful_login',
    'AUTH_002': 'test_case_invalid_email_login',
    'AUTH_003': 'test_case_invalid_password_login',
    'AUTH_004': 'test_case_empty_fields_login',
    'AUTH_010': 'test_case_user_logout',
    'FILE_001': 'test_case_single_file_upload',
    'FOLD_001': 'test_case_create_folder',
    'SRCH_001': 'test_case_basic_search'
}

def print_header():
    """Print test runner header"""
    print("=" * 70)
    print("SecureDocs Individual Test Case Runner")
    print("=" * 70)
    print(f"Started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()

def print_footer(total_time):
    """Print test runner footer"""
    print()
    print("=" * 70)
    print(f"Tests completed at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"Total execution time: {total_time:.2f} seconds")
    print("=" * 70)

def run_test_case(module_name):
    """Run a single test case"""
    try:
        # Import the test module
        test_module = importlib.import_module(module_name)
        
        # Get the main test function (should be same name as module)
        test_function_name = module_name  # Function name matches module name
        test_function = getattr(test_module, test_function_name)
        
        # Run the test
        print(f"🧪 Executing: {module_name}")
        result = test_function()
        
        return result
        
    except ImportError as e:
        print(f"✗ Failed to import {module_name}: {e}")
        return False
    except AttributeError as e:
        print(f"✗ Test function not found in {module_name}: {e}")
        return False
    except Exception as e:
        print(f"✗ Error running {module_name}: {e}")
        return False

def run_category_tests(category):
    """Run all tests in a category"""
    if category not in TEST_CASES:
        print(f"Unknown test category: {category}")
        print(f"Available categories: {', '.join(TEST_CASES.keys())}")
        return False
    
    print(f"Running {category.upper()} Tests")
    print("-" * 50)
    
    test_modules = TEST_CASES[category]
    passed = 0
    total = len(test_modules)
    
    for module_name in test_modules:
        if run_test_case(module_name):
            passed += 1
        print()  # Add spacing between tests
    
    print(f"{category.upper()} Tests Summary: {passed}/{total} passed")
    return passed == total

def run_all_tests():
    """Run all test cases"""
    print_header()
    start_time = time.time()
    
    category_results = []
    
    for category in TEST_CASES.keys():
        print(f"\n{'=' * 50}")
        print(f"RUNNING {category.upper()} TESTS")
        print('=' * 50)
        
        result = run_category_tests(category)
        category_results.append((category, result))
        
        print()
    
    # Print overall summary
    print(f"{'=' * 50}")
    print("OVERALL TEST SUMMARY")
    print('=' * 50)
    
    total_categories = len(category_results)
    passed_categories = sum(1 for _, result in category_results if result)
    
    for category, result in category_results:
        status = "✓ PASSED" if result else "✗ FAILED"
        print(f"{category.upper()}: {status}")
    
    print(f"\nOverall: {passed_categories}/{total_categories} test categories passed")
    
    total_time = time.time() - start_time
    print_footer(total_time)
    
    return passed_categories == total_categories

def run_specific_test_id(test_id):
    """Run a specific test case by ID (e.g., AUTH_001)"""
    if test_id not in TEST_ID_MAP:
        print(f"Unknown test ID: {test_id}")
        print(f"Available test IDs: {', '.join(TEST_ID_MAP.keys())}")
        return False
    
    module_name = TEST_ID_MAP[test_id]
    
    print_header()
    start_time = time.time()
    
    print(f"Running Single Test: {test_id}")
    print("-" * 50)
    
    result = run_test_case(module_name)
    
    status = "PASSED" if result else "FAILED"
    print(f"\n{test_id}: {status}")
    
    total_time = time.time() - start_time
    print_footer(total_time)
    
    return result

def main():
    """Main function"""
    try:
        if len(sys.argv) == 1:
            # Run all tests
            success = run_all_tests()
        elif len(sys.argv) == 2:
            arg = sys.argv[1].upper()
            
            # Check if it's a test ID (e.g., AUTH_001)
            if arg in TEST_ID_MAP:
                success = run_specific_test_id(arg)
            # Check if it's a category
            elif arg.lower() in TEST_CASES:
                print_header()
                start_time = time.time()
                success = run_category_tests(arg.lower())
                total_time = time.time() - start_time
                print_footer(total_time)
            else:
                print("Usage:")
                print("  python run_individual_tests.py                    # Run all tests")
                print("  python run_individual_tests.py auth               # Run authentication tests")
                print("  python run_individual_tests.py file               # Run file management tests")
                print("  python run_individual_tests.py folder             # Run folder management tests")
                print("  python run_individual_tests.py search             # Run search tests")
                print("  python run_individual_tests.py AUTH_001           # Run specific test case")
                print()
                print("Available test categories:", ', '.join(TEST_CASES.keys()))
                print("Available test IDs:", ', '.join(TEST_ID_MAP.keys()))
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
