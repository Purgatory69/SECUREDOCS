@extends('layouts.webauthn')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Register Biometric Device') }}</div>

                <div class="card-body">
                    <p>{{ __('Registering device:') }} <strong>{{ $name }}</strong></p>
                    
                    <div class="alert alert-info mt-3">
                        <p>{{ __('Please follow your browser\'s instructions to register your biometric device.') }}</p>
                        <p>{{ __('You may be prompted to use your fingerprint, face recognition, or security key.') }}</p>
                    </div>
                    
                    <div id="status-message" class="alert alert-warning mt-3" style="display: none;"></div>
                    
                    <div class="mt-4 text-center">
                        <div id="loading-indicator" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">{{ __('Processing...') }}</p>
                        </div>
                        
                        <div id="success-message" class="alert alert-success" style="display: none;">
                            <p>{{ __('Device registered successfully!') }}</p>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger" style="display: none;">
                            <p>{{ __('Error registering device.') }}</p>
                            <p id="error-details"></p>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="{{ route('webauthn.index') }}" id="back-button" class="btn btn-secondary">
                            {{ __('Back to Biometric Devices') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/webauthn-register.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusMessage = document.getElementById('status-message');
        const loadingIndicator = document.getElementById('loading-indicator');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        const errorDetails = document.getElementById('error-details');
        const backButton = document.getElementById('back-button');
        
        // Show initial status
        loadingIndicator.style.display = 'block';
        statusMessage.textContent = '{{ __("Preparing registration...") }}';
        statusMessage.style.display = 'block';
        
        // Function to show error
        function showError(message) {
            console.error('WebAuthn Error:', message);
            loadingIndicator.style.display = 'none';
            errorMessage.style.display = 'block';
            errorDetails.textContent = message || 'An unknown error occurred during registration.';
            statusMessage.style.display = 'none';
            backButton.style.display = 'inline-block';
        }
        
        // Start the registration process
        const registerButton = document.createElement('button');
        registerButton.id = 'register-button';
        registerButton.className = 'btn btn-primary';
        registerButton.textContent = '{{ __("Register Device") }}';
        
        // Add button to the page
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'mt-3 text-center';
        buttonContainer.appendChild(registerButton);
        document.querySelector('.card-body').appendChild(buttonContainer);
        
        // Hide the back button initially
        backButton.style.display = 'none';
        
        // Add click handler to the button
        registerButton.addEventListener('click', function() {
            // Show loading state
            loadingIndicator.style.display = 'block';
            statusMessage.textContent = '{{ __("Please follow your browser\'s instructions to register your device...") }}';
            statusMessage.style.display = 'block';
            errorMessage.style.display = 'none';
            registerButton.disabled = true;
            
            // Call the registration function from our custom handler
            window.webauthnRegister("{{ $name }}")
                .then(() => {
                    // Success - show success message and redirect
                    loadingIndicator.style.display = 'none';
                    successMessage.style.display = 'block';
                    statusMessage.style.display = 'none';
                    registerButton.style.display = 'none';
                    
                    // Redirect to the devices list after a short delay
                    setTimeout(() => {
                        window.location.href = "{{ route('webauthn.index') }}";
                    }, 2000);
                })
                .catch(error => {
                    console.error('Registration error:', error);
                    showError(error.message || 'Registration failed. Please try again.');
                    registerButton.disabled = false;
                });
        });
        
        // Show the register button
        loadingIndicator.style.display = 'none';
        statusMessage.textContent = '{{ __("Click the button below to start the registration process.") }}';
        statusMessage.style.display = 'block';
    });
</script>
@endpush
