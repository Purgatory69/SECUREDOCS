"""
Test data management for SecureDocs automation
"""

import json
import os
from typing import Dict, List, Any
from utils.config import Config


class TestData:
    """Test data provider class"""
    
    _config = Config()
    _test_data_dir = _config.get_test_data_dir()
    
    @classmethod
    def get_valid_user(cls) -> Dict[str, str]:
        """Get valid user data for testing"""
        return {
            'email': cls._config.get_valid_user_email(),
            'password': cls._config.get_valid_user_password(),
            'name': 'Test User',
            'role': 'user',
            'verified': True,
            'premium': False
        }
        
    @classmethod
    def get_admin_user(cls) -> Dict[str, str]:
        """Get admin user data for testing"""
        return {
            'email': cls._config.get_admin_email(),
            'password': cls._config.get_admin_password(),
            'name': 'Admin User',
            'role': 'admin',
            'verified': True,
            'premium': True
        }
        
    @classmethod
    def get_premium_user(cls) -> Dict[str, str]:
        """Get premium user data for testing"""
        return {
            'email': cls._config.get_premium_user_email(),
            'password': cls._config.get_premium_user_password(),
            'name': 'Premium User',
            'role': 'user',
            'verified': True,
            'premium': True
        }
        
    @classmethod
    def get_unverified_user(cls) -> Dict[str, str]:
        """Get unverified user data for testing"""
        return {
            'email': 'unverified@example.com',
            'password': 'UnverifiedPass123!',
            'name': 'Unverified User',
            'role': 'user',
            'verified': False,
            'premium': False
        }
        
    @classmethod
    def get_invalid_users(cls) -> List[Dict[str, str]]:
        """Get list of invalid user data for negative testing"""
        return [
            {
                'email': 'nonexistent@example.com',
                'password': 'WrongPassword123!',
                'expected_error': 'Invalid credentials'
            },
            {
                'email': 'invalid-email',
                'password': 'password123',
                'expected_error': 'Invalid credentials'
            },
            {
                'email': '',
                'password': '',
                'expected_error': 'required'
            },
            {
                'email': 'test@example.com',
                'password': '',
                'expected_error': 'required'
            },
            {
                'email': '',
                'password': 'password123',
                'expected_error': 'required'
            }
        ]
        
    @classmethod
    def get_test_files(cls) -> List[Dict[str, Any]]:
        """Get test files for upload testing"""
        return [
            {
                'name': 'sample-document.pdf',
                'path': os.path.join(cls._test_data_dir, 'files', 'sample-document.pdf'),
                'size_kb': 1024,
                'type': 'application/pdf',
                'category': 'document',
                'valid': True
            },
            {
                'name': 'test-image.jpg',
                'path': os.path.join(cls._test_data_dir, 'files', 'test-image.jpg'),
                'size_kb': 500,
                'type': 'image/jpeg',
                'category': 'image',
                'valid': True
            },
            {
                'name': 'text-document.txt',
                'path': os.path.join(cls._test_data_dir, 'files', 'text-document.txt'),
                'size_kb': 10,
                'type': 'text/plain',
                'category': 'text',
                'valid': True
            },
            {
                'name': 'large-file.zip',
                'path': os.path.join(cls._test_data_dir, 'files', 'large-file.zip'),
                'size_kb': 51200,  # 50MB
                'type': 'application/zip',
                'category': 'archive',
                'valid': False,  # Exceeds size limit
                'expected_error': 'File too large'
            },
            {
                'name': 'malicious.exe',
                'path': os.path.join(cls._test_data_dir, 'files', 'malicious.exe'),
                'size_kb': 100,
                'type': 'application/exe',
                'category': 'executable',
                'valid': False,  # Invalid file type
                'expected_error': 'File type not allowed'
            }
        ]
        
    @classmethod
    def get_search_data(cls) -> List[Dict[str, str]]:
        """Get search test data"""
        return [
            {
                'query': 'document',
                'match_type': 'contains',
                'case_sensitive': False,
                'whole_word': False,
                'expected_results': 'Multiple files containing "document"'
            },
            {
                'query': 'Document',
                'match_type': 'exact',
                'case_sensitive': True,
                'whole_word': True,
                'expected_results': 'Exact case-sensitive match'
            },
            {
                'query': 'test',
                'match_type': 'starts_with',
                'case_sensitive': False,
                'whole_word': False,
                'expected_results': 'Files starting with "test"'
            },
            {
                'query': '.pdf',
                'match_type': 'ends_with',
                'case_sensitive': False,
                'whole_word': False,
                'expected_results': 'PDF files'
            }
        ]
        
    @classmethod
    def get_folder_data(cls) -> List[Dict[str, str]]:
        """Get folder test data"""
        return [
            {
                'name': 'Test Folder',
                'description': 'Test folder for automation',
                'parent_id': None
            },
            {
                'name': 'Documents',
                'description': 'Document storage folder',
                'parent_id': None
            },
            {
                'name': 'Images',
                'description': 'Image storage folder',
                'parent_id': None
            },
            {
                'name': 'Subfolder',
                'description': 'Nested subfolder',
                'parent_id': 1  # Child of first folder
            }
        ]
        
    @classmethod
    def get_otp_data(cls) -> Dict[str, Any]:
        """Get OTP security test data"""
        return {
            'valid_otp': '123456',
            'invalid_otp': '000000',
            'expired_otp': '999999',
            'duration_minutes': 10,
            'max_attempts': 3
        }
        
    @classmethod
    def get_payment_data(cls) -> Dict[str, Any]:
        """Get payment test data for premium features"""
        return {
            'test_card': {
                'number': '4111111111111111',  # Test Visa card
                'expiry': '12/25',
                'cvv': '123',
                'name': 'Test User'
            },
            'invalid_card': {
                'number': '4000000000000002',  # Declined card
                'expiry': '12/25',
                'cvv': '123',
                'name': 'Test User'
            },
            'plans': [
                {
                    'name': 'premium',
                    'price': 299.00,
                    'currency': 'PHP',
                    'billing_cycle': 'monthly'
                }
            ]
        }
        
    @classmethod
    def get_webauthn_data(cls) -> Dict[str, Any]:
        """Get WebAuthn test data"""
        return {
            'authenticator_name': 'Test Security Key',
            'challenge': 'test-challenge-string',
            'user_id': 'test-user-id'
        }
        
    @classmethod
    def get_admin_test_data(cls) -> Dict[str, Any]:
        """Get admin panel test data"""
        return {
            'user_search_queries': [
                'test',
                'admin',
                'premium',
                'nonexistent'
            ],
            'user_actions': [
                'approve',
                'revoke',
                'toggle_premium',
                'reset_premium'
            ],
            'metrics_periods': [
                '7d',
                '30d',
                '90d',
                '1y'
            ]
        }
        
    @classmethod
    def get_api_endpoints(cls) -> Dict[str, str]:
        """Get API endpoints for testing"""
        base_url = cls._config.get_base_url()
        return {
            'login': f'{base_url}/login',
            'register': f'{base_url}/register',
            'logout': f'{base_url}/logout',
            'dashboard': f'{base_url}/user/dashboard',
            'files': f'{base_url}/files',
            'upload': f'{base_url}/files/upload',
            'search': f'{base_url}/search',
            'admin_dashboard': f'{base_url}/admin/dashboard',
            'admin_users': f'{base_url}/admin/users',
            'otp_enable': f'{base_url}/file-otp/enable',
            'otp_verify': f'{base_url}/file-otp/verify',
            'blockchain_upload': f'{base_url}/blockchain/upload',
            'webauthn_register': f'{base_url}/webauthn/register/options'
        }
        
    @classmethod
    def load_data_from_json(cls, filename: str) -> Dict[str, Any]:
        """Load test data from JSON file"""
        file_path = os.path.join(cls._test_data_dir, filename)
        
        if not os.path.exists(file_path):
            raise FileNotFoundError(f"Test data file not found: {file_path}")
            
        with open(file_path, 'r', encoding='utf-8') as f:
            return json.load(f)
            
    @classmethod
    def save_data_to_json(cls, data: Dict[str, Any], filename: str) -> None:
        """Save test data to JSON file"""
        file_path = os.path.join(cls._test_data_dir, filename)
        
        # Create directory if it doesn't exist
        os.makedirs(os.path.dirname(file_path), exist_ok=True)
        
        with open(file_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
            
    @classmethod
    def get_browser_data(cls) -> List[str]:
        """Get supported browsers for cross-browser testing"""
        return ['chrome', 'firefox', 'edge']
        
    @classmethod
    def get_mobile_devices(cls) -> List[Dict[str, Any]]:
        """Get mobile device configurations for responsive testing"""
        return [
            {
                'name': 'iPhone 12',
                'width': 390,
                'height': 844,
                'pixel_ratio': 3,
                'user_agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)'
            },
            {
                'name': 'Samsung Galaxy S21',
                'width': 384,
                'height': 854,
                'pixel_ratio': 2.75,
                'user_agent': 'Mozilla/5.0 (Linux; Android 11; SM-G991B)'
            },
            {
                'name': 'iPad Air',
                'width': 820,
                'height': 1180,
                'pixel_ratio': 2,
                'user_agent': 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)'
            }
        ]
        
    @classmethod
    def get_performance_thresholds(cls) -> Dict[str, float]:
        """Get performance testing thresholds"""
        return {
            'page_load_time_seconds': 5.0,
            'file_upload_time_seconds': 10.0,
            'search_response_time_seconds': 2.0,
            'login_time_seconds': 3.0,
            'dashboard_load_time_seconds': 4.0
        }
        
    @classmethod
    def create_test_data_files(cls) -> None:
        """Create test data files if they don't exist"""
        # Create test data directory
        os.makedirs(cls._test_data_dir, exist_ok=True)
        os.makedirs(os.path.join(cls._test_data_dir, 'files'), exist_ok=True)
        
        # Create sample test files
        files_dir = os.path.join(cls._test_data_dir, 'files')
        
        # Create sample text file
        with open(os.path.join(files_dir, 'text-document.txt'), 'w') as f:
            f.write('This is a sample text document for testing file upload functionality.')
            
        # Create sample data JSON files
        cls.save_data_to_json(cls.get_valid_user(), 'users.json')
        cls.save_data_to_json(cls.get_test_files(), 'files.json')
        cls.save_data_to_json(cls.get_search_data(), 'search.json')
