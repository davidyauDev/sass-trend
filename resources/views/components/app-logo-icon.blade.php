@php
    $settings = \App\Models\WebsiteSetting::current();
    $logoUrl = $settings->logoUrl() ?? asset('images/trendbelleza-favicon.png');
@endphp

<img src="{{ $logoUrl }}" alt="{{ $settings->site_name }}" {{ $attributes->merge(['class' => 'h-8 w-auto object-contain']) }}>
