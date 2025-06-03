/**
 * WebAuthn Handler - Custom implementation for SecureDocs
 * 
 * This script provides a more user-friendly interface for WebAuthn operations
 * and handles the UI feedback for biometric authentication.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize WebAuthn handlers if the page contains relevant elements
    initBiometricLogin();
    initBiometricRegistration();
});

/**
 * Initialize biometric login functionality
 */
function initBiometricLogin() {
    const biometricLoginButton = document.getElementById('biometric-login-button');
    
    if (biometricLoginButton) {
        biometricLoginButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            biometricLoginButton.disabled = true;
            biometricLoginButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Authenticating...';
            
            // Call WebAuthn authentication
            webauthn.login()
                .then(function(response) {
                    // Handle successful login - the page will redirect automatically
                    console.log('Login successful');
                })
                .catch(function(error) {
                    // Handle login error
                    console.error('Login error:', error);
                    
                    // Show error message
                    const errorContainer = document.createElement('div');
                    errorContainer.className = 'alert alert-danger mt-3';
                    errorContainer.textContent = 'Authentication failed: ' + (error.message || 'Unknown error');
                    
                    // Insert error message after the button
                    biometricLoginButton.parentNode.insertBefore(errorContainer, biometricLoginButton.nextSibling);
                    
                    // Reset button
                    biometricLoginButton.disabled = false;
                    biometricLoginButton.innerHTML = 'Login with Biometrics';
                });
        });
    }
}

/**
 * Initialize biometric registration functionality
 */
function initBiometricRegistration() {
    const registerButton = document.getElementById('register-button');
    
    if (registerButton) {
        registerButton.addEventListener('click', function(e) {
            e.preventDefault();
            const name = document.getElementById('name').value;
            
            if (!name) {
                alert('Please enter a device name');
                return;
            }
            
            // Show loading state
            registerButton.disabled = true;
            registerButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registering...';
            
            // Call WebAuthn registration
            webauthn.register(name)
                .then(function(response) {
                    // Handle successful registration
                    console.log('Registration successful');
                    
                    // Show success message
                    const successContainer = document.createElement('div');
                    successContainer.className = 'alert alert-success mt-3';
                    successContainer.textContent = 'Device registered successfully!';
                    
                    // Insert success message after the form
                    const form = registerButton.closest('form');
                    form.parentNode.insertBefore(successContainer, form.nextSibling);
                    
                    // Reset form
                    form.reset();
                    
                    // Reset button
                    registerButton.disabled = false;
                    registerButton.innerHTML = 'Register Device';
                    
                    // Reload the page after a short delay to show the new device
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                })
                .catch(function(error) {
                    // Handle registration error
                    console.error('Registration error:', error);
                    
                    // Show error message
                    const errorContainer = document.createElement('div');
                    errorContainer.className = 'alert alert-danger mt-3';
                    errorContainer.textContent = 'Registration failed: ' + (error.message || 'Unknown error');
                    
                    // Insert error message after the form
                    const form = registerButton.closest('form');
                    form.parentNode.insertBefore(errorContainer, form.nextSibling);
                    
                    // Reset button
                    registerButton.disabled = false;
                    registerButton.innerHTML = 'Register Device';
                });
        });
    }
}
