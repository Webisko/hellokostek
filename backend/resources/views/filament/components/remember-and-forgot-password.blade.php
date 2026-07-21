<div class="flex items-center justify-between gap-4 py-1 w-full">
    <label class="custom-checkbox-container select-none">
        <input
            type="checkbox"
            wire:model="data.remember"
            id="remember"
            class="custom-checkbox-input"
        />
        <div class="custom-checkbox-box shadow-sm">
            <svg class="custom-checkbox-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('filament-panels::auth/pages/login.form.remember.label') }}
        </span>
    </label>

    @if (filament()->hasPasswordReset())
        <x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="-1">
            {{ __('filament-panels::auth/pages/login.actions.request_password_reset.label') }}
        </x-filament::link>
    @endif
</div>
