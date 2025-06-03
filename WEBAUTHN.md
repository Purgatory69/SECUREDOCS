# WebAuthn Biometric Authentication Integration

This document provides an overview of the WebAuthn biometric authentication integration in the SecureDocs application.

## Overview

WebAuthn (Web Authentication) is a web standard published by the World Wide Web Consortium (W3C) that allows servers to register and authenticate users using public key cryptography instead of passwords. This implementation uses the `asbiin/laravel-webauthn` package to provide biometric authentication capabilities.

## Features

- **Biometric Login**: Users can authenticate using fingerprint, face recognition, or security keys
- **Device Management**: Users can register, view, and remove their biometric devices
- **Secure Area**: Access to sensitive areas can be restricted to require biometric authentication
- **Phishing-Resistant**: WebAuthn provides phishing-resistant authentication
- **No Biometric Data Storage**: No biometric data is stored on the server, only public keys

## Implementation Details

### Database

The implementation uses the following tables:
- `webauthn_keys`: Stores WebAuthn credentials for users

### Models

- `User` model has a `webauthnKeys()` relationship to access registered devices

### Controllers

- `WebAuthnController`: Handles WebAuthn registration and authentication flows

### Routes

- `/webauthn`: Device management page
- `/webauthn/register`: Device registration page
- `/webauthn/register/options`: API endpoint for registration options
- `/webauthn/register/verify`: API endpoint for registration verification
- `/webauthn/login/options`: API endpoint for login options
- `/webauthn/login/verify`: API endpoint for login verification
- `/secure-area`: Example of a WebAuthn-protected area

### Middleware

- `WebAuthnAuthenticated`: Ensures a user has registered biometric devices

### Views

- `webauthn.manage`: Device management page
- `webauthn.register`: Device registration page
- `secure-area`: Example of a protected area

### JavaScript

- `vendor/webauthn/webauthn.js`: Core WebAuthn functionality from the package
- `js/webauthn-handler.js`: Custom handler for WebAuthn UI interactions

## Usage

### For Users

1. **Register a Device**:
   - Navigate to the Biometric Login page from the navigation menu
   - Enter a name for your device and click "Register Device"
   - Follow your browser's prompts to complete registration

2. **Login with Biometrics**:
   - On the login page, click "Login with Biometrics"
   - Follow your browser's prompts to authenticate

3. **Manage Devices**:
   - Navigate to the Biometric Login page
   - View all registered devices
   - Remove devices as needed

### For Developers

#### Adding WebAuthn Protection to Routes

To require WebAuthn authentication for specific routes:

```php
Route::middleware(['auth', 'auth.webauthn'])->group(function () {
    Route::get('/protected-route', function () {
        // This route requires biometric authentication
    });
});
```

#### Checking WebAuthn Status

Use the provided Artisan command to check the WebAuthn integration status:

```bash
php artisan webauthn:status
```

## Browser Compatibility

WebAuthn is supported in:
- Chrome 67+
- Firefox 60+
- Edge 18+
- Safari 13+
- Most modern mobile browsers

## Security Considerations

- WebAuthn credentials are bound to the origin (domain) they were created on
- Each credential is unique to the combination of user, device, and website
- No sensitive biometric data is transmitted or stored on the server
- Authentication is resistant to phishing, replay, and man-in-the-middle attacks

## Troubleshooting

### Common Issues

1. **Device not detected**:
   - Ensure your device has biometric capabilities
   - Check browser permissions for biometric access

2. **Registration fails**:
   - Try using a different browser
   - Ensure your device's biometric system is working properly

3. **Login fails**:
   - Verify you're using the same device used during registration
   - Try registering a new device if the issue persists

### Debug Commands

```bash
# Check WebAuthn integration status
php artisan webauthn:status

# Clear cache if experiencing issues
php artisan cache:clear
php artisan config:clear
```

## References

- [WebAuthn Specification](https://www.w3.org/TR/webauthn-2/)
- [Laravel WebAuthn Package](https://github.com/asbiin/laravel-webauthn)
- [WebAuthn.io](https://webauthn.io/) - WebAuthn demo and testing tool
