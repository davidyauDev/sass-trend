<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php($isTenantContext = tenant() !== null)
        @php($authUser = auth()->user())

        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white/95 shadow-[0_0_0_1px_rgba(0,0,0,0.02)] dark:border-zinc-800 dark:bg-zinc-950/95">
            <div class="flex h-full flex-col">
                <div class="border-b border-zinc-200/80 px-4 py-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('sales.index') }}" wire:navigate class="flex items-center gap-3">
                            <img
                                src="{{ asset('images/trendbelleza-favicon.png') }}"
                                alt="Trend Belleza"
                                class="size-10 rounded-2xl object-cover shadow-sm"
                            />
                            <div class="grid leading-tight">
                                <span class="text-[15px] font-semibold text-zinc-900 dark:text-zinc-50">Trend Belleza</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Agencia y CRM</span>
                            </div>
                        </a>

                        <flux:sidebar.collapse class="lg:hidden" />
                    </div>

                    <div class="mt-4">
                        <flux:input
                            type="search"
                            placeholder="{{ __('Search') }}"
                            icon="magnifying-glass"
                            class="h-11 rounded-2xl bg-zinc-50 dark:bg-zinc-900"
                        />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-3 py-4">
                    <flux:sidebar.nav>
                        <flux:sidebar.group :heading="__('Platform')" class="grid gap-1">
                            {{-- <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:sidebar.item> --}}

                            @if ($isTenantContext)
                                <flux:sidebar.item icon="shopping-bag" :href="route('sales.index')" :current="request()->routeIs('sales.*')" wire:navigate>
                                    {{ __('Ventas') }}
                                </flux:sidebar.item>
                                {{-- <flux:sidebar.item icon="calendar-days" :href="route('agenda.index')" :current="request()->routeIs('agenda.*')" wire:navigate>
                                    {{ __('Calendar') }}
                                </flux:sidebar.item> --}}
                                <flux:sidebar.item icon="users" :href="route('clientes.index')" :current="request()->routeIs('clientes.*')" wire:navigate>
                                    {{ __('Clients') }}
                                </flux:sidebar.item>
                            @endif
                        </flux:sidebar.group>

                        <flux:sidebar.group :heading="__('Administration')" class="mt-6 grid gap-1">
                            @if ($isTenantContext)
                                <flux:sidebar.item icon="building-storefront" :href="route('locales.index')" :current="request()->routeIs('locales.*')" wire:navigate>
                                    {{ __('Branches') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="banknotes" :href="route('administracion.comisiones.index')" :current="request()->routeIs('administracion.comisiones.*')" wire:navigate>
                                    {{ __('Commissions') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="user-group" :href="route('administracion.profesionales.index')" :current="request()->routeIs('administracion.profesionales.*')" wire:navigate>
                                    {{ __('Professionals') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="sparkles" :href="route('administracion.servicios.index')" :current="request()->routeIs('administracion.servicios.*')" wire:navigate>
                                    {{ __('Services') }}
                                </flux:sidebar.item>
                                <div class="mt-4 space-y-1">
                                    <div class="px-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400 dark:text-zinc-500">
                                        {{ __('Products') }}
                                    </div>

                                    <flux:sidebar.item icon="cube" :href="route('products.index')" :current="request()->routeIs('products.index')" wire:navigate>
                                        {{ __('Inventario') }}
                                    </flux:sidebar.item>
                                    <flux:sidebar.item icon="shopping-cart" :href="route('products.sales.index')" :current="request()->routeIs('products.sales.*')" wire:navigate>
                                        {{ __('Venta de productos') }}
                                    </flux:sidebar.item>
                                    <flux:sidebar.item icon="arrows-right-left" :href="route('products.movements.index')" :current="request()->routeIs('products.movements.*')" wire:navigate>
                                        {{ __('Movimiento de stock') }}
                                    </flux:sidebar.item>
                                </div>
                                <div class="mt-4 space-y-1">
                                    <div class="px-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400 dark:text-zinc-500">
                                        {{ __('Settings') }}
                                    </div>

                                    <flux:sidebar.item icon="shield-check" :href="route('administracion.usuarios.index')" :current="request()->routeIs('administracion.usuarios.*')" wire:navigate>
                                        {{ __('Users') }}
                                    </flux:sidebar.item>
                                    <flux:sidebar.item icon="building-office-2" :href="route('administracion.empresa')" :current="request()->routeIs('administracion.empresa') || request()->routeIs('administracion.sitio-web')" wire:navigate>
                                        {{ __('Empresa') }}
                                    </flux:sidebar.item>
                                </div>
                            @else
                                <flux:sidebar.item icon="building-office-2" :href="route('administracion.tenants.index')" :current="request()->routeIs('administracion.tenants.*')" wire:navigate>
                                    {{ __('Tenants') }}
                                </flux:sidebar.item>
                            @endif
                        </flux:sidebar.group>
                    </flux:sidebar.nav>
                </div>

                <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-800">
                    <flux:sidebar.nav>
                        {{--
                    <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                        {{ __('Repository') }}
                    </flux:sidebar.item>

                        <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                            {{ __('Documentation') }}
                        </flux:sidebar.item>
                        --}}
                    </flux:sidebar.nav>

                    @if ($authUser)
                        <div class="mt-3 rounded-2xl border border-zinc-200/80 bg-zinc-50 px-3 py-3 dark:border-zinc-800 dark:bg-zinc-900">
                            <x-desktop-user-menu class="hidden lg:block" :name="$authUser->name" />
                            <div class="flex items-center gap-3 lg:hidden">
                                <flux:avatar :name="$authUser->name" :initials="$authUser->initials()" />
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-50">{{ $authUser->name }}</div>
                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $authUser->email }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @if ($authUser)
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="$authUser->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="$authUser->name"
                                        :initials="$authUser->initials()"
                                    />

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ $authUser->name }}</flux:heading>
                                        <flux:text class="truncate">{{ $authUser->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
