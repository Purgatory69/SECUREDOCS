from test_login import TestLogin
from test_signup import TestSignup
import time

def run_all_tests():
    """Run all authentication tests"""
    print("🚀 Starting SecureDocs Authentication Test Suite")
    print("=" * 60)
    
    # Initialize test classes
    login_test = TestLogin()
    signup_test = TestSignup()
    
    # Store results
    results = {}
    
    # Login Tests
    print("\n📝 RUNNING LOGIN TESTS")
    print("-" * 40)
    
    print("1. Testing successful login...")
    results['login_success'] = login_test.test_successful_login()
    time.sleep(1)
    
    print("\n2. Testing invalid login...")
    results['login_invalid'] = login_test.test_invalid_login()
    time.sleep(1)
    
    print("\n3. Testing password toggle...")
    results['login_toggle'] = login_test.test_password_toggle()
    time.sleep(1)
    
    # Signup Tests
    print("\n\n📝 RUNNING SIGNUP TESTS")
    print("-" * 40)
    
    print("1. Testing successful signup...")
    results['signup_success'] = signup_test.test_successful_signup()
    time.sleep(1)
    
    print("\n2. Testing existing email signup...")
    results['signup_existing'] = signup_test.test_existing_email_signup()
    time.sleep(1)
    
    print("\n3. Testing password mismatch...")
    results['signup_mismatch'] = signup_test.test_password_mismatch()
    time.sleep(1)
    
    print("\n4. Testing password toggle...")
    results['signup_toggle'] = signup_test.test_password_toggle()
    time.sleep(1)
    
    print("\n5. Testing navigation to login...")
    results['signup_navigation'] = signup_test.test_navigation_to_login()
    
    # Final Results
    print("\n\n" + "=" * 60)
    print("🏆 FINAL TEST RESULTS")
    print("=" * 60)
    
    # Login results
    print("\n📝 LOGIN TESTS:")
    print(f"  ✅ Successful Login: {'PASS' if results['login_success'] else '❌ FAIL'}")
    print(f"  ✅ Invalid Login: {'PASS' if results['login_invalid'] else '❌ FAIL'}")
    print(f"  ✅ Password Toggle: {'PASS' if results['login_toggle'] else '❌ FAIL'}")
    
    # Signup results
    print("\n📝 SIGNUP TESTS:")
    print(f"  ✅ Successful Signup: {'PASS' if results['signup_success'] else '❌ FAIL'}")
    print(f"  ✅ Existing Email: {'PASS' if results['signup_existing'] else '❌ FAIL'}")
    print(f"  ✅ Password Mismatch: {'PASS' if results['signup_mismatch'] else '❌ FAIL'}")
    print(f"  ✅ Password Toggle: {'PASS' if results['signup_toggle'] else '❌ FAIL'}")
    print(f"  ✅ Navigation: {'PASS' if results['signup_navigation'] else '❌ FAIL'}")
    
    # Summary
    total_tests = len(results)
    passed_tests = sum(1 for result in results.values() if result)
    
    print(f"\n📊 SUMMARY:")
    print(f"  Total Tests: {total_tests}")
    print(f"  Passed: {passed_tests}")
    print(f"  Failed: {total_tests - passed_tests}")
    print(f"  Success Rate: {(passed_tests/total_tests)*100:.1f}%")
    
    if passed_tests == total_tests:
        print("\n🎉 ALL TESTS PASSED! 🎉")
    else:
        print(f"\n⚠️  {total_tests - passed_tests} TEST(S) FAILED")
        
    print("=" * 60)
    
    return results

if __name__ == "__main__":
    run_all_tests()
