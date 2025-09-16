@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-4">
        <div class="text-lg font-medium text-gray-900">
            {{ $title }}
        </div>

        <div class="modal-content-text mt-4 text-sm" style="color: rgba(255,255,255,0.7) !important;">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 text-end">
        {{ $footer }}
    </div>

    <style>
        .settings-form-wrapper [role="dialog"] .modal-content-text { color: rgba(255,255,255,0.7) !important; }
    </style>
</x-modal>
