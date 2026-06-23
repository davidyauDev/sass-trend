@props([
    'sidebar' => false,
])

@php
    $settings = \App\Models\WebsiteSetting::current();
    $logoUrl = $settings->logoUrl() ?? asset('images/trendbelleza-favicon.png');
@endphp

<a {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <img
        src="{{ $logoUrl }}"
        alt="{{ $settings->site_name }}"
        class="{{ $sidebar ? 'h-7' : 'h-8' }} w-auto max-w-none object-contain"
    />
</a>
