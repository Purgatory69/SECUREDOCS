#!/usr/bin/env python3
"""
Simple Test Runner for SecureDocs Automation Tests
Usage:
    python run_tests.py                    # Run all tests
    python run_tests.py auth               # Run authentication tests only
    python run_tests.py file               # Run file management tests only
    python run_tests.py folder             # Run folder management tests only
    python run_tests.py search             # Run search tests only
"""

import sys
import time
from datetime import datetime

# Import test modules
try:
    from test_authentication import run_authentication_tests
    from test_file_management import run_file_management_tests
    from test_folder_management import run_folder_management_tests
    from test_search import run_search_tests
except ImportError as e:
    print(f"Error importing test modules: {e}")
    print("Make sure all test files are in the same directory.")
    sys.exit(1)

def print_header():
    """Print test runner header"""
    print("=" * 60)
    print("SecureDocs Automation Test Runner")
    print("=" * 60)
    print(f"Started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()

def print_footer(total_time):
    """Print test runner footer"""
    print()
    print("=" * 60)
    print(f"Tests completed at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"Total execution time: {total_time:.2f} seconds")
    print("=" * 60)

def run_all_tests():
    """Run all test suites"""
    print_header()
    start_time = time.time()
    
    test_results = []
    
    # Run each test suite
    test_suites = [
        ("Authentication", run_authentication_tests),
        ("File Management", run_file_management_tests),
        ("Folder Management", run_folder_management_tests),
        ("Search", run_search_tests)
    ]
    
    for suite_name, test_function in test_suites:
        print(f"\n{'=' * 40}")
        print(f"Running {suite_name} Tests")
        print('=' * 40)
        
        try:
            result = test_function()
            test_results.append((suite_name, result))
            status = "PASSED" if result else "FAILED"
            print(f"{suite_name} Tests: {status}")
        except Exception as e:
            print(f"Error running {suite_name} tests: {e}")
            test_results.append((suite_name, False))
    
    # Print summary
    print(f"\n{'=' * 40}")
    print("TEST SUMMARY")
    print('=' * 40)
    
    total_suites = len(test_results)
    passed_suites = sum(1 for _, result in test_results if result)
    
    for suite_name, result in test_results:
        status = "✓ PASSED" if result else "✗ FAILED"
        print(f"{suite_name}: {status}")
    
    print(f"\nOverall: {passed_suites}/{total_suites} test suites passed")
    
    total_time = time.time() - start_time
    print_footer(total_time)
    
    return passed_suites == total_suites

def run_specific_tests(test_type):
    """Run specific test suite based on argument"""
    print_header()
    start_time = time.time()
    
    test_mapping = {
        'auth': ('Authentication', run_authentication_tests),
        'authentication': ('Authentication', run_authentication_tests),
        'file': ('File Management', run_file_management_tests),
        'folder': ('Folder Management', run_folder_management_tests),
        'search': ('Search', run_search_tests)
    }
    
    if test_type.lower() not in test_mapping:
        print(f"Unknown test type: {test_type}")
        print("Available test types: auth, file, folder, search")
        return False
    
    suite_name, test_function = test_mapping[test_type.lower()]
    
    print(f"Running {suite_name} Tests Only")
    print('=' * 40)
    
    try:
        result = test_function()
        status = "PASSED" if result else "FAILED"
        print(f"\n{suite_name} Tests: {status}")
        
        total_time = time.time() - start_time
        print_footer(total_time)
        
        return result
    except Exception as e:
        print(f"Error running {suite_name} tests: {e}")
        total_time = time.time() - start_time
        print_footer(total_time)
        return False

def main():
    """Main function"""
    if len(sys.argv) == 1:
        # Run all tests
        success = run_all_tests()
    elif len(sys.argv) == 2:
        # Run specific test suite
        test_type = sys.argv[1]
        success = run_specific_tests(test_type)
    else:
        print("Usage:")
        print("  python run_tests.py                    # Run all tests")
        print("  python run_tests.py auth               # Run authentication tests")
        print("  python run_tests.py file               # Run file management tests")
        print("  python run_tests.py folder             # Run folder management tests")
        print("  python run_tests.py search             # Run search tests")
        return
    
    # Exit with appropriate code
    sys.exit(0 if success else 1)

if __name__ == "__main__":
    main()
