/**
 * WebAuthn Registration Handler
 * 
 * This script handles the WebAuthn registration flow, including:
 * 1. Fetching registration options from the server
 * 2. Calling the WebAuthn API to create a new credential
 * 3. Sending the credential to the server for verification
 */

/**
 * Main registration function
 * @param {string} name - The name of the device to register
 * @returns {Promise} A promise that resolves when registration is complete
 */
async function webauthnRegister(name) {
    try {
        // 1. Get registration options from the server
        console.log('Fetching registration options...');
        const optionsResponse = await fetch('/webauthn/register/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ name }),
            credentials: 'same-origin'
        });

        if (!optionsResponse.ok) {
            console.error('Failed to get registration options. Status:', optionsResponse.status);
            let errorMessage = `Server responded with status ${optionsResponse.status}`;
            try {
                const errorData = await optionsResponse.json();
                console.error('Error details:', errorData);
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                const text = await optionsResponse.text();
                console.error('Failed to parse error response:', text);
                errorMessage = `Failed to parse error response: ${text.substring(0, 200)}...`;
            }
            throw new Error(`Failed to get registration options: ${errorMessage}`);
        }

        const options = await optionsResponse.json();
        console.log('Received registration options:', JSON.stringify({
            ...options,
            challenge: '...',
            user: { ...options.user, id: '...' },
            excludeCredentials: options.excludeCredentials ? '[...]' : '[]'
        }, null, 2));

        // 2. Prepare the public key options for the WebAuthn API
        try {
            const publicKey = {
                ...options,
                challenge: base64UrlToUint8Array(options.challenge),
                user: {
                    ...options.user,
                    id: base64UrlToUint8Array(options.user.id),
                },
                excludeCredentials: options.excludeCredentials ? options.excludeCredentials.map(cred => ({
                    ...cred,
                    id: base64UrlToUint8Array(cred.id)
                })) : []
            };
            console.log('Prepared public key options for WebAuthn API');

            // 3. Call the WebAuthn API to create a new credential
            console.log('Calling navigator.credentials.create()...');
            const credential = await navigator.credentials.create({
                publicKey: publicKey
            });
            console.log('WebAuthn credential created successfully');
        } catch (error) {
            console.error('Error during WebAuthn credential creation:', error);
            if (error.name === 'NotAllowedError') {
                throw new Error('Registration was cancelled or no authenticator was selected.');
            } else if (error.name === 'InvalidStateError') {
                throw new Error('This device appears to be already registered.');
            } else if (error.name === 'NotSupportedError') {
                throw new Error('WebAuthn is not supported by your browser.');
            } else if (error.name === 'ConstraintError') {
                throw new Error('Authentication failed due to a constraint not being met.');
            } else if (error.name === 'TypeError') {
                console.error('TypeError details:', error.message);
                console.error('Stack:', error.stack);
                throw new Error('An error occurred while processing the registration. Please check the console for details.');
            } else {
                console.error('Unexpected error:', error);
                throw new Error(`Registration failed: ${error.message || 'Unknown error'}`);
            }
        }

        // 4. Convert the credential to a format that can be sent to the server
        const credentialData = {
            id: credential.id,
            rawId: arrayBufferToBase64Url(credential.rawId),
            type: credential.type,
            response: {
                attestationObject: arrayBufferToBase64Url(credential.response.attestationObject),
                clientDataJSON: arrayBufferToBase64Url(credential.response.clientDataJSON),
                transports: credential.response.getTransports ? credential.response.getTransports() : []
            }
        };

        // 5. Send the credential to the server for verification
        console.log('Sending credential to server for verification...');
        try {
            const verifyResponse = await fetch('/webauthn/register/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: name,
                    data: credentialData
                }),
                credentials: 'same-origin'
            });

            console.log('Verification response status:', verifyResponse.status);
            
            if (!verifyResponse.ok) {
                let errorMessage = `Server responded with status ${verifyResponse.status}`;
                try {
                    const errorData = await verifyResponse.json();
                    console.error('Verification error details:', errorData);
                    errorMessage = errorData.message || errorData.error || errorMessage;
                    
                    // Include validation errors if available
                    if (errorData.errors) {
                        const validationErrors = Object.values(errorData.errors).flat();
                        errorMessage += ' ' + validationErrors.join(' ');
                    }
                } catch (e) {
                    const text = await verifyResponse.text();
                    console.error('Failed to parse error response:', text);
                    errorMessage = `Failed to parse error response: ${text.substring(0, 200)}...`;
                }
                throw new Error(`Registration verification failed: ${errorMessage}`);
            }

            const result = await verifyResponse.json();
            console.log('Verification successful:', result);
            return result;
        } catch (error) {
            console.error('Error during verification request:', error);
            if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                throw new Error('Failed to connect to the server. Please check your internet connection.');
            }
            throw error; // Re-throw to be caught by the outer try-catch
        }
    } catch (error) {
        console.error('WebAuthn registration error:', error);
        throw error;
    }
}

/**
 * Convert a base64url string to a Uint8Array
 * @param {string} base64url - The base64url string to convert
 * @returns {Uint8Array} The decoded Uint8Array
 */
function base64UrlToUint8Array(base64url) {
    const base64 = base64url
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    
    // Add padding if needed
    const pad = base64.length % 4;
    const padded = pad ? 
        base64.padEnd(base64.length + (4 - pad), '=') : 
        base64;
    
    const binary = atob(padded);
    const bytes = new Uint8Array(binary.length);
    
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    
    return bytes;
}

/**
 * Convert an ArrayBuffer to a base64url string
 * @param {ArrayBuffer} buffer - The buffer to convert
 * @returns {string} The base64url string
 */
function arrayBufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    
    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    
    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

// Make the function available globally
window.webauthnRegister = webauthnRegister;
