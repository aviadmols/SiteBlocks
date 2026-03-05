<x-filament-panels::page>
    {{ $this->table }}

    @if ($this->getFooterWidgets())
        <div class="mt-6">
            @foreach ($this->getFooterWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
