<div class="space-y-4">
    <div class="space-y-3">
        <flux:input
            wire:model.live.debounce.180ms="search"
            icon="magnifying-glass"
            placeholder="Busca entre servicios, productos e ítems recientes"
            class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
        />

        <div class="grid grid-cols-3 gap-2">
            @foreach (['recent' => 'Recientes', 'services' => 'Servicios', 'products' => 'Productos'] as $tab => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $tab }}')"
                    wire:loading.attr="disabled"
                    wire:target="setTab,selectService,selectProduct"
                    class="{{ $this->tab === $tab
                        ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm dark:border-emerald-500 dark:bg-emerald-600'
                        : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-[#0f1720] dark:text-zinc-300 dark:hover:bg-white/[0.05]' }} inline-flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-semibold transition duration-200 ease-out disabled:cursor-not-allowed disabled:opacity-70"
                >
                    {{ $label }}
                    @if ($tab === 'services')
                        <span class="{{ $this->tab === $tab ? 'bg-white/20 text-white' : 'bg-emerald-500 text-white dark:bg-emerald-600' }} rounded-full px-1.5 text-xs">
                            {{ $this->servicesCatalog->count() }}
                        </span>
                    @elseif ($tab === 'products')
                        <span class="{{ $this->tab === $tab ? 'bg-white/20 text-white' : 'bg-emerald-500 text-white dark:bg-emerald-600' }} rounded-full px-1.5 text-xs">
                            {{ $this->productsCatalog->count() }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white transition-[opacity,transform,filter] duration-200 ease-out motion-reduce:transition-none dark:border-white/10 dark:bg-[#111820]"
         wire:loading.class="opacity-60 blur-[1px] scale-[0.995]"
         wire:target="search,setTab,selectService,selectProduct">
        <div
            wire:loading.delay.shorter
            wire:target="search,setTab,selectService,selectProduct"
            class="absolute inset-0 z-10 flex items-start justify-center bg-white/70 px-4 pt-10 backdrop-blur-[2px] dark:bg-[#111820]/80"
        >
            <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-[0_20px_40px_rgba(15,23,42,0.12)] dark:border-white/10 dark:bg-[#0f1720] dark:shadow-none">
                <div class="size-5 animate-spin rounded-full border-2 border-zinc-200 border-t-violet-600 dark:border-white/10 dark:border-t-emerald-500"></div>
                <div>
                    <div class="text-sm font-semibold text-zinc-900 dark:text-white">Filtrando resultados</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Actualizando la lista sin frenar la pantalla.</div>
                </div>
            </div>
        </div>

        @if ($this->tab === 'services')
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-white/10">
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Servicios</div>
                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $this->servicesCatalog->count() }} resultados</div>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($this->servicesCatalog as $service)
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <div class="min-w-0">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $service->name }}</div>
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $service->category?->name ?: 'Servicio' }}</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $service->duration_minutes }} min</div>

                            @if ($service->professionalProfiles->isNotEmpty())
                                <button type="button" wire:click="selectService({{ $service->id }})" wire:loading.attr="disabled" class="inline-flex size-10 items-center justify-center rounded-xl border border-zinc-200 bg-white shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
                                    <flux:icon.plus class="size-5 text-emerald-600" />
                                </button>
                            @else
                                <button type="button" disabled class="inline-flex size-10 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 text-zinc-300 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-600 dark:shadow-none">
                                    <flux:icon.plus class="size-5" />
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500 dark:text-zinc-400">No se encontraron servicios.</div>
                @endforelse
            </div>
        @elseif ($this->tab === 'products')
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-white/10">
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Productos</div>
                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $this->productsCatalog->count() }} resultados</div>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($this->productsCatalog as $product)
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $product->name }}</div>
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $product->item_detail ?? $product->category?->name ?? 'Venta retail' }}</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">S/{{ number_format((float) $product->public_sale_price, 2) }}</div>

                            @if ($this->cartQuantityForProduct($product->id) > 0)
                                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-600 text-sm font-semibold text-white shadow-sm">
                                    {{ $this->cartQuantityForProduct($product->id) }}
                                </div>
                            @endif

                            <button type="button" wire:click="selectProduct({{ $product->id }})" wire:loading.attr="disabled" class="inline-flex size-10 items-center justify-center rounded-xl border border-zinc-200 bg-white shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
                                <flux:icon.plus class="size-5 text-emerald-600" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500 dark:text-zinc-400">No se encontraron productos.</div>
                @endforelse
            </div>
        @else
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-white/10">
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Recientes</div>
                @if ($this->filteredRecentItems->isNotEmpty())
                    <button type="button" wire:click="setTab('services')" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        Ver todos
                    </button>
                @endif
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse ($this->filteredRecentItems as $item)
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $item->item_name }}</div>
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->item_detail ?: 'Ítem reciente' }}</div>
                        </div>

                        @if ($item->item_type === 'service' && $item->service_id)
                            @if ($item->service?->professionalProfiles?->isNotEmpty())
                                <div class="flex items-center gap-3">
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item->service?->duration_minutes ? $item->service->duration_minutes.' min' : 'Servicio' }}</div>
                                    <button type="button" wire:click="selectService({{ $item->service_id }})" wire:loading.attr="disabled" class="inline-flex size-10 items-center justify-center rounded-xl border border-zinc-200 bg-white shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
                                        <flux:icon.plus class="size-5 text-emerald-600" />
                                    </button>
                                </div>
                            @else
                                <button type="button" disabled class="inline-flex size-10 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 text-zinc-300 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-600 dark:shadow-none">
                                    <flux:icon.plus class="size-5" />
                                </button>
                            @endif
                        @elseif ($item->item_type === 'product' && $item->product_id)
                            <div class="flex items-center gap-3">
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    S/{{ number_format((float) ($item->product?->public_sale_price ?? 0), 2) }}
                                </div>
                                @if ($this->cartQuantityForProduct($item->product_id) > 0)
                                    <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-600 text-sm font-semibold text-white shadow-sm">
                                        {{ $this->cartQuantityForProduct($item->product_id) }}
                                    </div>
                                @endif

                                <button type="button" wire:click="selectProduct({{ $item->product_id }})" wire:loading.attr="disabled" class="inline-flex size-10 items-center justify-center rounded-xl border border-zinc-200 bg-white shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
                                    <flux:icon.plus class="size-5 text-emerald-600" />
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500 dark:text-zinc-400">No hay ítems recientes.</div>
                @endforelse
            </div>
        @endif
    </div>
</div>
