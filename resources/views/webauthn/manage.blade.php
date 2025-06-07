@extends('layouts.webauthn')

@section('content')
<div class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12">
    <div class="relative py-3 sm:max-w-xl md:max-w-2xl lg:max-w-4xl mx-auto">
        <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
            <div class="max-w-md mx-auto">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ __('Biometric Authentication') }}</h1>
                </div>
                <div class="divide-y divide-gray-200">
                    <div class="py-8 text-base leading-6 space-y-4 text-gray-700 sm:text-lg sm:leading-7">
                        
                        <h2 class="text-xl font-semibold text-gray-800">{{ __('Registered Devices') }}</h2>
                        
                        @if($webauthnKeys && count($webauthnKeys) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 mt-3">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('Device Name') }}
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('Added On') }}
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('Actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($webauthnKeys as $key)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $key->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $key->created_at->format('M d, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <form method="POST" action="{{ route('webauthn.keys.destroy', $key->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to remove this device?') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                                            {{ __('Remove') }}
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="mt-3 bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
                                <p class="font-bold">{{ __('Info') }}</p>
                                <p>{{ __('No devices registered yet.') }}</p>
                            </div>
                        @endif

                        <hr class="my-8 border-gray-300">

                        <h2 class="text-xl font-semibold text-gray-800 mt-6">{{ __('Register New Device') }}</h2>
                        <p class="text-gray-600 text-sm">{{ __('Add a new device for biometric login (fingerprint, face recognition, etc.)') }}</p>
                        
                        <form method="POST" action="{{ route('webauthn.register.options') }}" class="mt-6 space-y-6" id="webauthn-register-form">
                            @csrf
                            <div>
                                <label for="device-name" class="block text-sm font-medium text-gray-700">{{ __('Device Name') }}</label>
                                <input id="device-name" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') border-red-500 @enderror" name="name" required autofocus placeholder="My iPhone, Work Laptop, etc.">
                                
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <button type="button" id="register-device-button" 
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('Register Device') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Basic CSRF setup for fetch requests if not already handled globally
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Consider moving webauthn-handler.js logic here or ensuring it's loaded and initialized correctly
    // For example, if webauthn-handler.js relies on DOMContentLoaded, it should be fine as a separate file.
    // If it needs specific data from Blade, you might pass it here.
</script>
{{-- Include your webauthn-handler.js if it's not part of the main app.js bundle --}}
{{-- @vite('resources/js/webauthn-handler.js') --}}
@endpush
