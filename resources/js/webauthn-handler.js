/**
 * WebAuthn handler for user interactions
 * Provides UI feedback during WebAuthn operations
 */

// Import WebAuthn if using as a module
import './vendor/webauthn';

// Helper function to convert base64url string to ArrayBuffer
function base64urlToArrayBuffer(base64url) {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const binaryString = window.atob(base64);
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

// Helper function to show error messages
function showError(button, message, statusDisplay = null) {
    console.error('WebAuthn error:', message);

    if (statusDisplay) {
        statusDisplay.textContent = message;
        statusDisplay.className = 'text-sm text-red-600 mt-2 text-center';
    }

    if (button && typeof button.classList !== 'undefined') {
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed', 'bg-indigo-600', 'hover:bg-indigo-700', 'bg-green-600'); // Remove general and success classes
        button.classList.add('bg-red-600'); // Error color

        let icon = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`;
        let text = 'Error';
        if (button.id === 'biometric-login-button') text = 'Login Failed';
        if (button.id === 'register-device-button') text = 'Reg. Failed';
        button.innerHTML = `${icon} ${text}`;
    }
}

// Helper function to show success messages
function showSuccess(button, message, statusDisplay = null) {
    console.log("WebAuthn Success:", message);

    if (statusDisplay) {
        statusDisplay.textContent = message;
        statusDisplay.className = 'text-sm text-green-600 mt-2 text-center';
    }

    if (button && typeof button.classList !== 'undefined') {
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed', 'bg-indigo-600', 'hover:bg-indigo-700', 'bg-red-600'); // Remove general and error classes
        button.classList.add('bg-green-600'); // Success color

        let icon = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
        let text = 'Success!';
        // if (button.id === 'biometric-login-button') text = 'Login Succeeded'; // Already handled by redirect
        if (button.id === 'register-device-button') text = 'Registered!';
        button.innerHTML = `${icon} ${text}`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize WebAuthn object
    const webAuthn = window.WebAuthn;

    if (!webAuthn) {
        console.error('WebAuthn library not found!');
        // Optionally, disable WebAuthn features or show a general error message
        // You might want to update a general status area here if the button isn't available yet
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;

    // Expose the biometric login handler globally
    window.handleBiometricLogin = async function(userEmail, biometricButton, statusDisplay) {
        // Clear previous messages
        if (statusDisplay) {
            statusDisplay.textContent = '';
            statusDisplay.className = 'text-sm text-red-600 mt-2 text-center'; // Reset to default error class
        }

        console.log('[WebAuthn] Attempting biometrics login for email:', userEmail);

        if (!userEmail) {
            showError(biometricButton, 'Please enter your email address to use biometric login.', statusDisplay);
            return;
        }

        const originalButtonContent = biometricButton.innerHTML; // Store original button content

        biometricButton.disabled = true;
        biometricButton.classList.add('opacity-75', 'cursor-not-allowed');
        biometricButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Verifying...
        `;

        try {
            const optionsResponse = await fetch('/webauthn/login/options', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email: userEmail }),
            });

            if (!optionsResponse.ok) {
                const errorData = await optionsResponse.json().catch(() => ({ message: 'Failed to parse server error response.' }));
                console.error('Server response (options):', errorData);
                throw new Error(`Login options request failed: ${optionsResponse.status} ${errorData.message || ''}`.trim());
            }

            const options = await optionsResponse.json();

            // Check if user has any registered keys
            if (!options.challenge || !options.allowedCredentials || options.allowedCredentials.length === 0) {
                showError(biometricButton, 'No biometric keys registered for this account. Please register a key first.', statusDisplay);
                return; // Exit early
            }

            // Decode challenge and allowedCredentials IDs from base64url to ArrayBuffer
            options.challenge = base64urlToArrayBuffer(options.challenge);
            options.allowedCredentials = options.allowedCredentials.map(cred => ({
                ...cred,
                id: base64urlToArrayBuffer(cred.id),
            }));

            const credential = await navigator.credentials.get({ publicKey: options });

            // Prepare credential for server (convert ArrayBuffers back to base64url)
            const attestationResponseForServer = {
                id: credential.id, // This is already base64url encoded by the browser
                rawId: credential.id, // rawId is the base64url version of the ArrayBuffer id
                type: credential.type,
                response: {
                    authenticatorData: Array.prototype.map.call(new Uint8Array(credential.response.authenticatorData), x => ('00' + x.toString(16)).slice(-2)).join(''), // hex string for easier server handling if needed, or send as base64url
                    clientDataJSON: new TextDecoder().decode(credential.response.clientDataJSON),
                    signature: Array.prototype.map.call(new Uint8Array(credential.response.signature), x => ('00' + x.toString(16)).slice(-2)).join(''), // hex string or base64url
                    userHandle: credential.response.userHandle ? new TextDecoder().decode(credential.response.userHandle) : null,
                },
            };

            const verificationResponse = await fetch('/webauthn/login/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(attestationResponseForServer),
            });

            if (!verificationResponse.ok) {
                const errorData = await verificationResponse.json().catch(() => ({ message: 'Failed to parse server error response.' }));
                console.error('Server response (verify):', errorData);
                throw new Error(`Login verification failed: ${verificationResponse.status} ${errorData.message || ''}`.trim());
            }

            const verificationData = await verificationResponse.json();

            if (verificationData.verified) {
                showSuccess(biometricButton, verificationData.message || 'Login successful! Redirecting...', statusDisplay);
                setTimeout(() => {
                    window.location.href = verificationData.redirect_url || '/dashboard';
                }, 1500);
            } else {
                showError(biometricButton, verificationData.message || 'Login verification failed.', statusDisplay);
            }

        } catch (err) {
            console.error('WebAuthn login process error:', err);
            let displayErrorMessage = 'An unexpected error occurred during biometric login. Please try again.';

            if (err.name === 'NotAllowedError') {
                displayErrorMessage = 'Biometric login canceled or no matching authenticator found. Please try again, or ensure your authenticator is connected and recognized.';
            } else if (err.name === 'SecurityError') {
                displayErrorMessage = 'Biometric login failed due to a security policy. Ensure you\'re on a secure connection (HTTPS or localhost) and the domain is correctly configured.';
            } else if (err.name === 'InvalidStateError') {
                displayErrorMessage = 'Biometric login failed: your authenticator is in an invalid state. Please try again. If the issue persists, you may need to re-register your key.';
            } else if (err.message) {
                // Use specific messages from fetch errors if available
                if (err.message.startsWith('Login options request failed:') || err.message.startsWith('Login verification failed:')) {
                    displayErrorMessage = err.message;
                } else {
                    // For other generic errors with a message property
                    displayErrorMessage = 'Biometric login failed: ' + (err.message.startsWith('Error: ') ? err.message.substring('Error: '.length) : err.message);
                }
            }
            showError(biometricButton, displayErrorMessage, statusDisplay);
        } finally {
            // The button state is now handled by showError/showSuccess, but if no error/success, reset it
            if (!biometricButton.classList.contains('bg-green-600') && !biometricButton.classList.contains('bg-red-600')) {
                biometricButton.disabled = false;
                biometricButton.classList.remove('opacity-75', 'cursor-not-allowed');
                biometricButton.innerHTML = originalButtonContent;
            }
        }
    };

    // Handle biometric login button
    const biometricLoginButton = document.getElementById('biometric-login-button');
    const statusDisplay = document.getElementById('status-display');
    if (biometricLoginButton) {
        biometricLoginButton.addEventListener('click', async function(e) {
            e.preventDefault();

            const userEmailInput = document.getElementById('email');
            const userEmail = userEmailInput ? userEmailInput.value : null;

            await window.handleBiometricLogin(userEmail, biometricLoginButton, statusDisplay);
        });
    }

    // Handle device registration button (existing code, ensure showError calls are updated if needed)
    const registerDeviceButton = document.getElementById('register-device-button');
    if (registerDeviceButton) {
        const originalRegisterButtonContent = registerDeviceButton.innerHTML;
        registerDeviceButton.addEventListener('click', function(e) {
            e.preventDefault();

            const deviceName = document.getElementById('device-name').value.trim();
            if (!deviceName) {
                showError(registerDeviceButton, 'Please enter a name for your device.'); // No statusDisplay for this context yet
                return;
            }

            registerDeviceButton.disabled = true;
            registerDeviceButton.classList.add('opacity-75', 'cursor-not-allowed');
            registerDeviceButton.innerHTML = `Registering...`; // Simplified loading text

            fetch('/webauthn/register/options', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: deviceName }) // Assuming backend expects 'name'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Failed to get registration options.'); });
                }
                return response.json();
            })
            .then(options => {
                // Decode challenge
                options.challenge = base64urlToArrayBuffer(options.challenge);
                options.user.id = base64urlToArrayBuffer(options.user.id);

                return navigator.credentials.create({ publicKey: options });
            })
            .then(credential => {
                // Prepare credential for server
                const attestationResponseForServer = {
                    id: credential.id,
                    rawId: credential.id,
                    type: credential.type,
                    response: {
                        clientDataJSON: new TextDecoder().decode(credential.response.clientDataJSON),
                        attestationObject: Array.prototype.map.call(new Uint8Array(credential.response.attestationObject), x => ('00' + x.toString(16)).slice(-2)).join(''),
                    },
                };

                return fetch('/webauthn/register/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: deviceName,
                        data: attestationResponseForServer
                    }),
                });
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Failed to verify registration.'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.verified) { // Ensure your backend returns 'verified' or adjust key accordingly
                    showSuccess(registerDeviceButton, data.message || 'Device registered successfully!');
                    // Optionally redirect or update UI further, e.g., reload to show the new key
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showError(registerDeviceButton, data.message || 'Device registration failed.');
                }
            })
            .catch(error => {
                console.error('WebAuthn registration process error:', error);
                let displayErrorMessage = 'An unexpected error occurred during device registration. Please try again.';

                if (error.name === 'NotAllowedError') {
                    displayErrorMessage = 'Device registration canceled or not allowed. Please try again.';
                } else if (error.name === 'SecurityError') {
                    displayErrorMessage = 'Device registration failed due to a security policy. Ensure you\'re on a secure connection (HTTPS or localhost).';
                } else if (error.message) {
                    // Check if it's a server-generated error message from our throw statements
                    if (error.message.includes('Failed to get registration options') || error.message.includes('Failed to verify registration')) {
                        displayErrorMessage = error.message;
                    } else {
                        displayErrorMessage = 'Device registration failed: ' + error.message;
                    }
                }
                showError(registerDeviceButton, displayErrorMessage);
            })
            .finally(() => {
                // Re-enable button and restore original content if not showing success/error message
                if (!registerDeviceButton.classList.contains('bg-green-600') && !registerDeviceButton.classList.contains('bg-red-600')) {
                    registerDeviceButton.disabled = false;
                    registerDeviceButton.innerHTML = originalRegisterButtonContent;
                }
            });
        });
    }
});
