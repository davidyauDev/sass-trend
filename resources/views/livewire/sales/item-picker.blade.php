<div class="space-y-4">
    <div class="space-y-3">
        <flux:input
            wire:model.live.debounce.180ms="search"
            icon="magnifying-glass"
            placeholder="Busca entre servicios, productos e ítems recientes"
        />

        <div class="flex gap-2 overflow-x-auto pb-1">
            @foreach (['recent' => 'Recientes', 'services' => 'Servicios', 'products' => 'Productos'] as $tab => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $tab }}')"
                    wire:loading.attr="disabled"
                    wire:target="setTab,selectService,selectProduct"
                    class="{{ $this->tab === $tab
                        ? 'border-violet-500 bg-violet-600 text-white shadow-[0_8px_20px_rgba(124,58,237,0.25)] ring-2 ring-violet-100 -translate-y-px'
                        : 'border-zinc-200 bg-zinc-50 text-zinc-500 hover:border-zinc-300 hover:bg-zinc-100' }} inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition duration-200 ease-out disabled:cursor-not-allowed disabled:opacity-70"
                >
                    {{ $label }}
                    @if ($tab === 'services')
                        <span class="{{ $this->tab === $tab ? 'bg-white/20 text-white' : 'bg-emerald-500 text-white' }} rounded-full px-1.5 text-xs">
                            {{ $this->servicesCatalog->count() }}
                        </span>
                    @elseif ($tab === 'products')
                        <span class="{{ $this->tab === $tab ? 'bg-white/20 text-white' : 'bg-emerald-500 text-white' }} rounded-full px-1.5 text-xs">
                            {{ $this->productsCatalog->count() }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="relative rounded-2xl border border-zinc-200 bg-white transition-[opacity,transform,filter] duration-200 ease-out motion-reduce:transition-none"
         wire:loading.class="opacity-60 blur-[1px] scale-[0.995]"
         wire:target="search,setTab,selectService,selectProduct">
        <div
            wire:loading.delay.shorter
            wire:target="search,setTab,selectService,selectProduct"
            class="absolute inset-0 z-10 flex items-start justify-center bg-white/70 px-4 pt-10 backdrop-blur-[2px]"
        >
            <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-[0_20px_40px_rgba(15,23,42,0.12)]">
                <div class="size-5 animate-spin rounded-full border-2 border-zinc-200 border-t-violet-600"></div>
                <div>
                    <div class="text-sm font-semibold text-zinc-900">Filtrando resultados</div>
                    <div class="text-xs text-zinc-500">Actualizando la lista sin frenar la pantalla.</div>
                </div>
            </div>
        </div>

        @if ($this->tab === 'services')
            <div class="divide-y divide-zinc-200">
                @forelse ($this->servicesCatalog as $service)
                    <div class="flex items-center justify-between px-4 py-4">
                        <div class="min-w-0">
                            <div class="font-medium text-zinc-900">{{ $service->name }}</div>
                            <div class="mt-1 text-sm text-zinc-500">S/{{ number_format((float) $service->price, 0) }} | {{ $service->duration_minutes }} min</div>
                        </div>
                        @if ($service->professionalProfiles->isNotEmpty())
                            <button type="button" wire:click="selectService({{ $service->id }})" wire:loading.attr="disabled" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70">
                                <flux:icon.plus class="size-5 text-violet-600" />
                            </button>
                        @else
                            <button type="button" disabled class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 text-zinc-300 shadow-sm">
                                <flux:icon.plus class="size-5" />
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500">No se encontraron servicios.</div>
                @endforelse
            </div>
        @elseif ($this->tab === 'products')
            <div class="divide-y divide-zinc-200">
                @forelse ($this->productsCatalog as $product)
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900">{{ $product->name }}</div>
                            <div class="mt-1 text-sm text-zinc-500">S/{{ number_format((float) $product->public_sale_price, 2) }} | {{ $product->category?->name }} | {{ $product->brand?->name }} | {{ $product->presentation?->name }}</div>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($this->cartQuantityForProduct($product->id) > 0)
                                <div class="flex size-10 items-center justify-center rounded-xl bg-violet-600 text-sm font-semibold text-white shadow-sm">
                                    {{ $this->cartQuantityForProduct($product->id) }}
                                </div>
                            @endif

                            <button type="button" wire:click="selectProduct({{ $product->id }})" wire:loading.attr="disabled" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70">
                                <flux:icon.plus class="size-5 text-violet-600" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500">No se encontraron productos.</div>
                @endforelse
            </div>
        @else
            <div class="divide-y divide-zinc-200">
                @forelse ($this->filteredRecentItems as $item)
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900">{{ $item->item_name }}</div>
                            <div class="mt-1 text-sm text-zinc-500">{{ $item->item_detail ?: 'Ítem reciente' }}</div>
                        </div>

                        @if ($item->item_type === 'service' && $item->service_id)
                            @if ($item->service?->professionalProfiles?->isNotEmpty())
                                <button type="button" wire:click="selectService({{ $item->service_id }})" wire:loading.attr="disabled" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70">
                                    <flux:icon.plus class="size-5 text-violet-600" />
                                </button>
                            @else
                                <button type="button" disabled class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 text-zinc-300 shadow-sm">
                                    <flux:icon.plus class="size-5" />
                                </button>
                            @endif
                        @elseif ($item->item_type === 'product' && $item->product_id)
                            <div class="flex items-center gap-3">
                                @if ($this->cartQuantityForProduct($item->product_id) > 0)
                                    <div class="flex size-10 items-center justify-center rounded-xl bg-violet-600 text-sm font-semibold text-white shadow-sm">
                                        {{ $this->cartQuantityForProduct($item->product_id) }}
                                    </div>
                                @endif

                                <button type="button" wire:click="selectProduct({{ $item->product_id }})" wire:loading.attr="disabled" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm transition-transform duration-200 ease-out active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70">
                                    <flux:icon.plus class="size-5 text-violet-600" />
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-6 text-sm text-zinc-500">No hay ítems recientes.</div>
                @endforelse
            </div>
        @endif
    </div>
</div>
