@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-lock-open mr-2"></i> {{ __('Secure Area - Biometric Authentication Required') }}
                </div>

                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i> {{ __('You have successfully authenticated using your biometric device!') }}
                    </div>

                    <h4 class="mt-4">{{ __('Welcome to the Secure Area') }}</h4>
                    <p>
                        {{ __('This page is only accessible to users who have authenticated using their biometric device.') }}
                        {{ __('This provides an additional layer of security for sensitive operations.') }}
                    </p>

                    <div class="mt-4">
                        <h5>{{ __('Your Registered Biometric Devices:') }}</h5>
                        <ul class="list-group mt-3">
                            @foreach(Auth::user()->webauthnKeys as $device)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $device->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ __('Registered on:') }} {{ $device->created_at->format('M d, Y H:i') }}</small>
                                    </div>
                                    <span class="badge bg-success rounded-pill">{{ __('Active') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-4">
                        <h5>{{ __('Security Benefits of Biometric Authentication:') }}</h5>
                        <ul class="list-group mt-3">
                            <li class="list-group-item">{{ __('Phishing-resistant authentication') }}</li>
                            <li class="list-group-item">{{ __('No passwords to remember or potentially expose') }}</li>
                            <li class="list-group-item">{{ __('Unique cryptographic keys for each site and service') }}</li>
                            <li class="list-group-item">{{ __('Protection against credential stuffing attacks') }}</li>
                            <li class="list-group-item">{{ __('Convenient and fast authentication') }}</li>
                        </ul>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="{{ route('webauthn.index') }}" class="btn btn-primary">
                            {{ __('Manage Biometric Devices') }}
                        </a>
                        <a href="{{ route('user.dashboard') }}" class="btn btn-secondary ml-2">
                            {{ __('Return to Dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
