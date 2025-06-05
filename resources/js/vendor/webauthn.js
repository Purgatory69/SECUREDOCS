/**
 * WebAuthn client.
 *
 * This file is part of asbiin/laravel-webauthn project.
 *
 * @copyright Alexis SAETTLER © 2019
 * @license MIT
 */

'use strict';

class WebAuthn {
    constructor() {
        this._notifyCallback = null;
    }

    /**
     * Register a new key.
     *
     * @param {PublicKeyCredentialCreationOptions} publicKey  - see https://www.w3.org/TR/webauthn/#dictdef-publickeycredentialcreationoptions
     * @param {function(PublicKeyCredential)} callback  User callback
     */
    register(publicKey, callback) {
        let publicKeyCredential = Object.assign({}, publicKey);
        publicKeyCredential.challenge = this._bufferDecode(publicKey.challenge);
        publicKeyCredential.user.id = this._bufferDecode(publicKey.user.id);
        if (publicKey.excludeCredentials) {
            publicKeyCredential.excludeCredentials = this._credentialDecode(publicKey.excludeCredentials);
        }

        const self = this;
        navigator.credentials.create({
            publicKey: publicKeyCredential
        }).then(
            data => self._registerCallback(data, callback),
            error => {
                if (self._notifyCallback) {
                    self._notify(error.name, true);
                }
                callback(null, error);
            }
        );
    }

    /**
     * Register callback on register key.
     *
     * @param {PublicKeyCredential} publicKey @see https://www.w3.org/TR/webauthn/#publickeycredential
     * @param {function(PublicKeyCredential)} callback  User callback
     */
    _registerCallback(publicKey, callback) {
        const publicKeyCredential = {
            id: publicKey.id,
            type: publicKey.type,
            rawId: this._bufferEncode(publicKey.rawId),
            response: {
                clientDataJSON: this._bufferEncode(publicKey.response.clientDataJSON),
                attestationObject: this._bufferEncode(publicKey.response.attestationObject)
            }
        };

        callback(publicKeyCredential);
    }

    /**
     * Authenticate a user.
     *
     * @param {PublicKeyCredentialRequestOptions} publicKey  - see https://www.w3.org/TR/webauthn/#dictdef-publickeycredentialrequestoptions
     * @param {function(PublicKeyCredential)} callback  User callback
     */
    sign(publicKey, callback) {
        let publicKeyCredential = Object.assign({}, publicKey);
        publicKeyCredential.challenge = this._bufferDecode(publicKey.challenge);
        if (publicKey.allowCredentials) {
            publicKeyCredential.allowCredentials = this._credentialDecode(publicKey.allowCredentials);
        }

        const self = this;
        navigator.credentials.get({
            publicKey: publicKeyCredential
        }).then(
            data => self._signCallback(data, callback),
            error => {
                if (self._notifyCallback) {
                    self._notify(error.name, true);
                }
                callback(null, error);
            }
        );
    }

    /**
     * Sign callback on authenticate.
     *
     * @param {PublicKeyCredential} publicKey @see https://www.w3.org/TR/webauthn/#publickeycredential
     * @param {function(PublicKeyCredential)} callback  User callback
     */
    _signCallback(publicKey, callback) {
        const publicKeyCredential = {
            id: publicKey.id,
            type: publicKey.type,
            rawId: this._bufferEncode(publicKey.rawId),
            response: {
                authenticatorData: this._bufferEncode(publicKey.response.authenticatorData),
                clientDataJSON: this._bufferEncode(publicKey.response.clientDataJSON),
                signature: this._bufferEncode(publicKey.response.signature),
                userHandle: publicKey.response.userHandle ? this._bufferEncode(publicKey.response.userHandle) : null
            }
        };

        callback(publicKeyCredential);
    }

    /**
     * Buffer encode.
     *
     * @param {ArrayBuffer} value
     * @return {string}
     */
    _bufferEncode(value) {
        return btoa(String.fromCharCode.apply(null, new Uint8Array(value)))
            .replace(/\+/g, "-")
            .replace(/\//g, "_")
            .replace(/=/g, "");
    }

    /**
     * Buffer decode.
     *
     * @param {ArrayBuffer} value
     * @return {string}
     */
    _bufferDecode(value) {
        return Uint8Array.from(atob(this._base64Decode(value)), c => c.charCodeAt(0));
    }

    /**
     * Convert a base64url to a base64 string.
     *
     * @param {string} input
     * @return {string}
     */
    _base64Decode(input) {
        // Replace non-url compatible chars with base64 standard chars
        input = input
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        // Pad out with standard base64 required padding characters
        const pad = input.length % 4;
        if (pad) {
            if (pad === 1) {
                throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
            }
            input += new Array(5-pad).join('=');
        }

        return input;
    }

    /**
     * Credential decode.
     *
     * @param {PublicKeyCredentialDescriptor} credentials
     * @return {PublicKeyCredentialDescriptor}
     */
    _credentialDecode(credentials) {
        let result = [];
        for (let i = 0; i < credentials.length; i++) {
            result.push({
                id: this._bufferDecode(credentials[i].id),
                type: credentials[i].type,
                transports: credentials[i].transports
            });
        }
        return result;
    }

    /**
     * Test is WebAuthn is supported by this navigator.
     *
     * @return {bool}
     */
    webAuthnSupport() {
        return window.PublicKeyCredential !== undefined &&
            typeof window.PublicKeyCredential === 'function';
    }

    /**
     * Get the message in case WebAuthn is not supported.
     *
     * @return {string}
     */
    notSupportedMessage() {
        if (window.location.protocol !== 'https:') {
            return 'WebAuthn only supports https connections. Please use https to use this feature.';
        }
        return 'Your browser doesn\'t currently support WebAuthn. Please use a supported browser and try again.';
    }

    /**
     * Call the notify callback.
     *
     * @param {string} message
     * @param {bool} isError
     */
    _notify(message, isError) {
        if (this._notifyCallback) {
            this._notifyCallback('webauthn', message, isError);
        }
    }

    /**
     * Set the notify callback.
     *
     * @param {function(name: string, message: string, isError: bool)} callback
     */
    setNotify(callback) {
        this._notifyCallback = callback;
    }
}

// Export the WebAuthn instance to the window
window.WebAuthn = new WebAuthn();
