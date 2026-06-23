<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(75,54,38,0.14),_transparent_38%),linear-gradient(180deg,_#fffaf6_0%,_#fff_55%,_#f7f3ef_100%)] text-zinc-900 dark:bg-zinc-950 dark:text-zinc-50">
        {{ $slot }}

        @fluxScripts
    </body>
</html>
