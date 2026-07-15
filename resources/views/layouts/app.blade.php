<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main @class(['!p-0' => request()->routeIs('agenda.*')])>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
