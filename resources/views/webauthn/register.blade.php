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
<script src="{{ asset('vendor/webauthn/webauthn.js') }}"></script>
<script src="{{ asset('js/webauthn-handler.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusMessage = document.getElementById('status-message');
        const loadingIndicator = document.getElementById('loading-indicator');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        const errorDetails = document.getElementById('error-details');
        
        // Start the registration process
        loadingIndicator.style.display = 'block';
        statusMessage.textContent = '{{ __("Starting registration process...") }}';
        statusMessage.style.display = 'block';
        
        // Call WebAuthn registration
        webauthn.register("{{ $name }}")
            .then(function(response) {
                // Handle successful registration
                loadingIndicator.style.display = 'none';
                successMessage.style.display = 'block';
                
                // Redirect after a short delay
                setTimeout(function() {
                    window.location.href = "{{ route('webauthn.index') }}";
                }, 2000);
            })
            .catch(function(error) {
                // Handle registration error
                loadingIndicator.style.display = 'none';
                errorMessage.style.display = 'block';
                errorDetails.textContent = error.message || 'Unknown error';
            });
    });
</script>
@endpush
