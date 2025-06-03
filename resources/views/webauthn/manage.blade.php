@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Biometric Authentication') }}</div>

                <div class="card-body">
                    <h4>{{ __('Registered Devices') }}</h4>
                    
                    @if(count($webauthnKeys) > 0)
                        <table class="table mt-3">
                            <thead>
                                <tr>
                                    <th>{{ __('Device Name') }}</th>
                                    <th>{{ __('Added On') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($webauthnKeys as $key)
                                    <tr>
                                        <td>{{ $key->name }}</td>
                                        <td>{{ $key->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('webauthn.delete', $key->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    {{ __('Remove') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-info mt-3">
                            {{ __('No devices registered yet.') }}
                        </div>
                    @endif

                    <hr>

                    <h4 class="mt-4">{{ __('Register New Device') }}</h4>
                    <p class="text-muted">{{ __('Add a new device for biometric login (fingerprint, face recognition, etc.)') }}</p>
                    
                    <form method="POST" action="{{ route('webauthn.register') }}" class="mt-3">
                        @csrf
                        <div class="form-group">
                            <label for="name">{{ __('Device Name') }}</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" required autofocus placeholder="My iPhone, Work Laptop, etc.">
                            
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mt-3">
                            <button type="button" id="register-button" class="btn btn-primary">
                                {{ __('Register Device') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/webauthn/webauthn.js') }}"></script>
<script src="{{ asset('js/webauthn-handler.js') }}"></script>
@endpush
@endsection
