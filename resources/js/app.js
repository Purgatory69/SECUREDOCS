import './bootstrap';

// Import WebAuthn scripts in the correct order
// First import the vendor WebAuthn library to ensure it's available globally
// import './vendor/webauthn';
// Then import our custom handler that depends on window.WebAuthn
import './webauthn-handler';
