<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main data-app-main class="min-w-0 overflow-x-clip pb-28 lg:pb-8 {{ auth()->user()->isCustomer() || auth()->user()->isTechnician() ? 'max-lg:!px-0 max-lg:!pt-0' : '' }} {{ auth()->user()->isCustomer() ? 'lg:!p-0' : '' }}">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
