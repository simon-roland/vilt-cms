<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-4">
            <x-filament::button type="submit">
                {{ __('filament-panels::pages/settings.actions.save.label') }}
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
