<div class="settings-form-wrapper">
<x-form-section submit="updateProfileInformation">
    <x-slot name="title" class="text-white text-opacity-90">
        {{ __('Profile Information') }}
    </x-slot>

    <x-slot name="description">
        <span class="settings-description">{{ __('Update your account\'s profile information.') }}</span>
    </x-slot>

    <x-slot name="form">
        <!-- Initialize birthday state so the date input is prefilled (YYYY-MM-DD) -->
        <div class="hidden" wire:init="$set('state.birthday', '{{ optional($this->user->birthday)->format('Y-m-d') }}')"></div>
        <!-- Profile Photo -->
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div x-data="{photoName: null, photoPreview: null}" class="col-span-6 sm:col-span-4" >
                <!-- Profile Photo File Input -->
                <input type="file" id="photo" class="hidden"
                            wire:model.live="photo"
                            x-ref="photo"
                            x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

                <x-label for="photo" value="{{ __('Photo') }}" />

                <!-- Current Profile Photo -->
                <div class="mt-2" x-show="! photoPreview">
                    <img src="{{ $this->user->profile_photo_url }}" alt="{{ trim(($this->user->firstname ?? '') . ' ' . ($this->user->lastname ?? '')) }}" class="rounded-full size-20 object-cover">
                </div>

                <!-- New Profile Photo Preview -->
                <div class="mt-2" x-show="photoPreview" style="display: none;">
                    <span class="block rounded-full size-20 bg-cover bg-no-repeat bg-center"
                          x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

                <x-secondary-button class="mt-2 me-2" type="button" x-on:click.prevent="$refs.photo.click()">
                    {{ __('Select A New Photo') }}
                </x-secondary-button>

                @if ($this->user->profile_photo_path)
                    <x-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
                        {{ __('Remove Photo') }}
                    </x-secondary-button>
                @endif

                <x-input-error for="photo" class="mt-2" />
            </div>
        @endif

        <!-- First Name -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="firstname" class="settings-label" value="{{ __('First Name') }}" />
            <x-input id="firstname" type="text" class="mt-1 block w-full" wire:model="state.firstname" required autocomplete="given-name" />
            <x-input-error for="firstname" class="mt-2" />
        </div>

        <!-- Last Name -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="lastname" class="settings-label" value="{{ __('Last Name') }}" />
            <x-input id="lastname" type="text" class="mt-1 block w-full" wire:model="state.lastname" required autocomplete="family-name" />
            <x-input-error for="lastname" class="mt-2" />
        </div>

        <!-- Birthday -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="birthday" class="settings-label" value="{{ __('Birthday') }}" />
            <x-input id="birthday" type="date" class="mt-1 block w-full" wire:model="state.birthday" autocomplete="bday" />
            <x-input-error for="birthday" class="mt-2" />
        </div>

        <!-- Email (Read-only) -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="email" class="settings-label" value="{{ __('Email Address') }}" />
            <div class="mt-1 block w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-500 dark:text-gray-400 cursor-not-allowed">
                {{ $this->user->email }}
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Email address cannot be changed after registration.') }}
            </p>

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        {{ __('Your email address is unverified.') }}
                    </p>
                    <button type="button" class="mt-2 text-sm font-medium text-yellow-600 dark:text-yellow-400 hover:text-yellow-500 dark:hover:text-yellow-300 underline" wire:click.prevent="sendEmailVerification">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </div>

                @if ($this->verificationLinkSent)
                    <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            @endif
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button class="settings-button" wire:loading.attr="disabled" wire:target="photo">
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>
</div>