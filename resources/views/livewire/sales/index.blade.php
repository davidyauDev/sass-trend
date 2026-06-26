@php
    $receiptUrl = $selectedSale ? route('sales.receipt.show', $selectedSale->id) : null;
    $selectedClient = $selectedSale?->client;
    $emailReceiptUrl = null;

    if ($selectedSale && $selectedClient?->email) {
        $emailReceiptUrl = 'mailto:'.$selectedClient->email
            .'?subject='.rawurlencode('Comprobante de venta '.$selectedSale->sale_number)
            .'&body='.rawurlencode('Hola, te compartimos tu comprobante: '.$receiptUrl);
    }
@endphp

<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">Ventas</flux:heading>
            </div>

            <flux:button variant="primary" icon="plus" wire:click="openCreateSale">
                Nueva venta
            </flux:button>
        </div>

        <div class="rounded-[28px] border border-zinc-200/80 bg-white p-4 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-[22px] border border-zinc-200 bg-zinc-50 px-6 py-8 text-center">
                    <div class="text-sm uppercase tracking-wide text-zinc-500">Todas las ventas</div>
                    <div class="mt-3 text-4xl font-semibold text-zinc-900">{{ $this->metrics['all'] }}</div>
                </div>
                <div class="rounded-[22px] border border-zinc-200 bg-white px-6 py-8 text-center">
                    <div class="text-sm uppercase tracking-wide text-zinc-500">Pagos parciales</div>
                    <div class="mt-3 text-4xl font-semibold text-zinc-900">{{ $this->metrics['partial'] }}</div>
                </div>
                <div class="rounded-[22px] border border-zinc-200 bg-white px-6 py-8 text-center">
                    <div class="text-sm uppercase tracking-wide text-zinc-500">Eliminadas</div>
                    <div class="mt-3 text-4xl font-semibold text-zinc-900">{{ $this->metrics['deleted'] }}</div>
                </div>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-5">
                <flux:select wire:model.live="periodFilter">
                    <option value="7">Período 7 días</option>
                    <option value="30">Período 30 días</option>
                    <option value="90">Período 90 días</option>
                    <option value="all">Todo el historial</option>
                </flux:select>

                <flux:select wire:model.live="clientFilter">
                    <option value="">Cliente</option>
                    @foreach ($this->clientsCatalog as $client)
                        <option value="{{ $client->id }}">{{ $client->fullName() }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="statusFilter">
                    <option value="">Estado</option>
                    <option value="paid">Pagada</option>
                    <option value="partial">Abono</option>
                    <option value="draft">Borrador</option>
                    <option value="deleted">Eliminada</option>
                </flux:select>

                <flux:select wire:model.live="paymentMethodFilter">
                    <option value="">Método de pago</option>
                    @foreach ($this->paymentMethods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="branchFilter">
                    <option value="">Local</option>
                    @foreach ($this->branchesCatalog as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="rounded-[28px] border border-zinc-200/80 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-end gap-3 pb-4">
                <button type="button" wire:click="sortColumnsToggle" class="text-sm font-medium text-zinc-700 underline underline-offset-4">
                    Editar columnas
                </button>
                <a href="{{ route('sales.export', request()->query()) }}" class="inline-flex h-10 items-center rounded-xl border border-zinc-200 px-4 text-sm font-medium text-zinc-700 shadow-sm">
                    Exportar
                </a>
            </div>

            @if ($showColumnEditor)
                <div class="mb-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                    <div class="grid gap-3 md:grid-cols-3">
                        @foreach ($visibleColumns as $column => $visible)
                            <flux:checkbox wire:model.live="visibleColumns.{{ $column }}" :label="ucfirst($column)" />
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto rounded-[20px] border border-zinc-200">
                <table class="min-w-full divide-y divide-zinc-200">
                    <thead class="bg-white">
                        <tr class="text-left text-sm font-semibold text-zinc-700">
                            @if ($visibleColumns['id']) <th class="px-5 py-4">ID</th> @endif
                            @if ($visibleColumns['date']) <th class="px-5 py-4">Fecha</th> @endif
                            @if ($visibleColumns['amount']) <th class="px-5 py-4">Monto</th> @endif
                            @if ($visibleColumns['client']) <th class="px-5 py-4">Cliente</th> @endif
                            @if ($visibleColumns['branch']) <th class="px-5 py-4">Local</th> @endif
                            @if ($visibleColumns['status']) <th class="px-5 py-4">Estado</th> @endif
                            <th class="px-5 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white text-sm text-zinc-700">
                        @forelse ($sales as $sale)
                            <tr>
                                @if ($visibleColumns['id']) <td class="px-5 py-5">#{{ $sale->sale_number ?? $sale->id }}</td> @endif
                                @if ($visibleColumns['date']) <td class="px-5 py-5">{{ $sale->sold_at?->format('d/m/Y - h:i a') }}</td> @endif
                                @if ($visibleColumns['amount']) <td class="px-5 py-5">S/{{ number_format((float) $sale->total, 0) }}</td> @endif
                                @if ($visibleColumns['client']) <td class="px-5 py-5">{{ $sale->client?->fullName() ?? 'Consumidor final' }}</td> @endif
                                @if ($visibleColumns['branch']) <td class="px-5 py-5">{{ $sale->branch?->name ?? 'N/A' }}</td> @endif
                                @if ($visibleColumns['status']) <td class="px-5 py-5">{{ $sale->status }}</td> @endif
                                <td class="px-5 py-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="openSaleDetail({{ $sale->id }})" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm">
                                            <flux:icon.eye class="size-4" />
                                        </button>

                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                                            <flux:menu>
                                                <flux:menu.item icon="document-text" href="{{ route('sales.receipt.show', $sale->id) }}" target="_blank">
                                                    Ver comprobante
                                                </flux:menu.item>
                                                @if (! $sale->trashed())
                                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $sale->id }})">
                                                        Eliminar
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center text-zinc-500">No hay ventas para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-4">
                <flux:pagination :paginator="$sales" />
            </div>
        </div>
    </div>

    @if ($isDrawerOpen)
        <div class="fixed inset-0 z-[70] bg-zinc-950/30" wire:click="closeDrawer"></div>
        <aside class="fixed inset-y-0 right-0 z-[71] flex w-full max-w-[420px] flex-col bg-white shadow-[0_0_40px_rgba(15,23,42,0.2)]">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                <div class="flex items-center gap-3">
                    @if ($drawerStep !== 'cart' && $drawerStep !== 'success')
                        @if ($drawerStep === 'service-professional')
                            <button type="button" wire:click="backToItemPicker" class="text-zinc-500">
                                <flux:icon.chevron-left class="size-6" />
                            </button>
                        @else
                            <button type="button" wire:click="backToCart" class="text-zinc-500">
                                <flux:icon.chevron-left class="size-6" />
                            </button>
                        @endif
                    @elseif ($drawerStep === 'success')
                        <button type="button" wire:click="openCreateSale" class="text-zinc-500">
                            <flux:icon.chevron-left class="size-6" />
                        </button>
                    @endif
                    <flux:heading size="lg">
                        @switch($drawerStep)
                            @case('client-search') Asociar cliente a la venta @break
                            @case('client-create') Agrega un nuevo cliente @break
                            @case('item-picker') Agrega lo que desees @break
                            @case('service-professional') Selecciona un profesional @break
                            @case('payment') Método de pago @break
                            @case('success') {{ $saleSummaryMode === 'detail' ? 'Detalle de venta' : 'Venta completada' }} @break
                            @default Nueva venta
                        @endswitch
                    </flux:heading>
                </div>

                @if ($drawerStep !== 'success')
                    <button type="button" wire:click="closeDrawer" class="text-zinc-500">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                @endif
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                @if ($drawerStep === 'cart')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <button type="button" wire:click="openClientSearch" class="inline-flex size-11 items-center justify-center rounded-xl border border-zinc-200 shadow-sm">
                                <flux:icon.users class="size-5 text-zinc-500" />
                            </button>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost">Más opciones</flux:button>
                                <flux:menu>
                                    <flux:menu.item icon="trash" wire:click="openCreateSale">
                                        Vaciar carrito
                                    </flux:menu.item>
                                    @if ($saleForm['client_id'])
                                        <flux:menu.item icon="user-minus" wire:click="clearClient">
                                            Quitar cliente
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-xl border border-zinc-200">
                                <button type="button" wire:click="openItemPicker" class="flex w-full items-center justify-center gap-2 px-4 py-3 text-violet-600">
                                    <flux:icon.plus class="size-5" />
                                    Agregar al carro
                                </button>
                            </div>

                            @if ($saleForm['client_id'])
                                @php($client = $this->clientsCatalog->firstWhere('id', $saleForm['client_id']))
                                <button type="button" wire:click="openClientSearch" class="flex w-full items-center justify-between rounded-2xl border border-zinc-200 px-4 py-4 text-left">
                                    <span class="font-semibold text-zinc-900">{{ $client?->fullName() ?? 'Cliente' }}</span>
                                    <flux:icon.chevron-right class="size-5 text-zinc-400" />
                                </button>
                            @endif

                            @forelse ($saleForm['cart'] as $item)
                                <div class="flex items-start justify-between rounded-2xl border border-zinc-200 px-4 py-4">
                                    <div>
                                        <div class="font-medium text-zinc-900">
                                            {{ $item['item_name'] }}
                                            <span class="text-sm text-zinc-400">x{{ rtrim(rtrim((string) $item['quantity'], '0'), '.') }}</span>
                                        </div>

                                        @if (($item['meta']['professional_name'] ?? null) || $item['item_detail'])
                                            <div class="mt-1 text-sm text-zinc-500">{{ $item['item_detail'] }}</div>
                                        @endif

                                        @if ($item['item_type'] === 'service' && ($item['meta']['professional_name'] ?? null))
                                            <div class="mt-1 inline-flex items-center gap-2 rounded-full bg-violet-50 px-3 py-1 text-xs font-medium text-violet-700">
                                                <span>Profesional</span>
                                                <span>{{ $item['meta']['professional_name'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="font-semibold text-zinc-900">S/{{ number_format((float) $item['subtotal'], 0) }}</div>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                wire:click="decreaseCartItem('{{ $item['key'] }}')"
                                                @disabled((float) $item['quantity'] <= 1)
                                                class="inline-flex size-8 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 shadow-sm disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                <flux:icon.minus class="size-4" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="increaseCartItem('{{ $item['key'] }}')"
                                                class="inline-flex size-8 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 shadow-sm"
                                            >
                                                <flux:icon.plus class="size-4" />
                                            </button>
                                        </div>
                                        <button type="button" wire:click="removeCartItem('{{ $item['key'] }}')" class="rounded-xl border border-zinc-200 p-2 text-rose-500 shadow-sm">
                                            <flux:icon.trash class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="pt-8 text-center text-zinc-500">
                                    <p>El carro está vacío.</p>
                                    <p>Agrega ítems usando el botón "Agregar al carro"</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @elseif ($drawerStep === 'client-search')
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-zinc-700">Búsqueda de cliente</div>
                            <div class="mt-1 text-sm text-zinc-400">La búsqueda inicia a partir de 3 caracteres</div>
                        </div>

                        <flux:input wire:model.live.debounce.300ms="clientSearch" icon="magnifying-glass" placeholder="Busca por nombre, apellido, rut, email" />

                        <button type="button" wire:click="openClientCreate" class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 px-4 py-3 text-violet-600 shadow-sm">
                            <flux:icon.user-plus class="size-5" />
                            Crear nuevo cliente
                        </button>

                        <div class="rounded-2xl border border-zinc-200">
                            @if (mb_strlen(trim($clientSearch)) >= 3)
                                @forelse ($this->searchedClients as $client)
                                    <button type="button" wire:click="selectClient({{ $client->id }})" class="flex w-full flex-col border-b border-zinc-200 px-4 py-4 text-left last:border-b-0 hover:bg-zinc-50">
                                        <span class="font-semibold text-zinc-900">{{ $client->fullName() }}</span>
                                        <span class="mt-1 text-sm text-zinc-500">{{ $client->email ?: 'Sin email' }} | {{ $client->phone ?: 'Sin teléfono' }}</span>
                                    </button>
                                @empty
                                    <div class="px-4 py-6 text-sm text-zinc-500">No se encontraron clientes.</div>
                                @endforelse
                            @else
                                <div class="px-4 py-6 text-sm text-zinc-500">Ingresa al menos 3 caracteres para buscar.</div>
                            @endif
                        </div>
                    </div>
                @elseif ($drawerStep === 'client-create')
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-zinc-200 p-4">
                            <div class="text-base font-medium text-zinc-700">Información requerida</div>
                            <div class="mt-4 grid gap-4">
                                <flux:input wire:model="clientCreateForm.first_name" label="Nombre" />
                                <flux:input wire:model="clientCreateForm.last_name" label="Apellido" />
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 p-4">
                            <div class="text-base font-medium text-zinc-700">Información adicional</div>
                            <div class="mt-4 grid gap-4">
                                <flux:input wire:model="clientCreateForm.email" label="Email" />
                                <flux:input wire:model="clientCreateForm.phone" label="Teléfono" />
                            </div>
                        </div>
                    </div>
                @elseif ($drawerStep === 'item-picker')
                    <div class="space-y-4">
                        <flux:input wire:model.live.debounce.300ms="itemSearch" icon="magnifying-glass" placeholder="Busca y agrega entre tus servicios y productos" />

                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach (['recent' => 'Recientes', 'services' => 'Servicios', 'products' => 'Productos', 'giftcards' => 'Giftcard'] as $tab => $label)
                                <button
                                    type="button"
                                    wire:click="setItemPickerTab('{{ $tab }}')"
                                    class="{{ $itemPickerTab === $tab ? 'border-zinc-300 bg-white text-zinc-900' : 'border-zinc-200 bg-zinc-50 text-zinc-500' }} inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium"
                                >
                                    {{ $label }}
                                    @if ($tab === 'services')
                                        <span class="rounded-full bg-emerald-500 px-1.5 text-xs text-white">{{ $this->servicesCatalog->count() }}</span>
                                    @elseif ($tab === 'products')
                                        <span class="rounded-full bg-emerald-500 px-1.5 text-xs text-white">{{ $this->productsCatalog->count() }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <div class="rounded-2xl border border-zinc-200">
                            @if ($itemPickerTab === 'services')
                                @forelse ($this->filteredServicesCatalog as $service)
                                    <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 last:border-b-0">
                                        <div>
                                            <div class="font-medium text-zinc-900">{{ $service->name }}</div>
                                            <div class="mt-1 text-sm text-zinc-500">S/{{ number_format((float) $service->price, 0) }} | {{ $service->duration_minutes }} min</div>
                                        </div>
                                        @if ($service->professionalProfiles->isNotEmpty())
                                            <button type="button" wire:click="openServiceProfessionalPicker({{ $service->id }})" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm">
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
                            @elseif ($itemPickerTab === 'products')
                                @forelse ($this->filteredProductsCatalog as $product)
                                    <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 last:border-b-0">
                                        <div>
                                            <div class="font-medium text-zinc-900">{{ $product->name }}</div>
                                            <div class="mt-1 text-sm text-zinc-500">S/{{ number_format((float) $product->public_sale_price, 0) }} | {{ $product->brand?->name }} | {{ $product->presentation?->name }}</div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if ($this->cartQuantityForProduct($product->id) > 0)
                                                <button type="button" wire:click="decreaseProductToCart({{ $product->id }})" class="flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 shadow-sm">
                                                    <flux:icon.minus class="size-4" />
                                                </button>

                                                <div class="flex size-10 items-center justify-center rounded-xl bg-violet-600 text-sm font-semibold text-white shadow-sm">
                                                    {{ $this->cartQuantityForProduct($product->id) }}
                                                </div>
                                            @endif

                                            <button type="button" wire:click="addProductToCart({{ $product->id }})" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm">
                                                <flux:icon.plus class="size-5 text-violet-600" />
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-6 text-sm text-zinc-500">No se encontraron productos.</div>
                                @endforelse
                            @elseif ($itemPickerTab === 'recent')
                                @forelse ($this->filteredRecentItems as $item)
                                    <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 last:border-b-0">
                                        <div>
                                            <div class="font-medium text-zinc-900">{{ $item->item_name }}</div>
                                            <div class="mt-1 text-sm text-zinc-500">{{ $item->item_detail ?: 'Ítem reciente' }}</div>
                                        </div>
                                        @if ($item->item_type === 'service' && $item->service_id)
                                            @if ($item->service?->professionalProfiles?->isNotEmpty())
                                                <button type="button" wire:click="openServiceProfessionalPicker({{ $item->service_id }})" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm">
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
                                                    <button type="button" wire:click="decreaseProductToCart({{ $item->product_id }})" class="flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 shadow-sm">
                                                        <flux:icon.minus class="size-4" />
                                                    </button>

                                                    <div class="flex size-10 items-center justify-center rounded-xl bg-violet-600 text-sm font-semibold text-white shadow-sm">
                                                        {{ $this->cartQuantityForProduct($item->product_id) }}
                                                    </div>
                                                @endif

                                                <button type="button" wire:click="addProductToCart({{ $item->product_id }})" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 shadow-sm">
                                                    <flux:icon.plus class="size-5 text-violet-600" />
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="px-4 py-6 text-sm text-zinc-500">No hay ítems recientes.</div>
                                @endforelse
                            @else
                                <div class="px-4 py-8 text-sm text-zinc-500">Giftcards estará disponible pronto.</div>
                            @endif
                        </div>
                    </div>
                @elseif ($drawerStep === 'service-professional')
                    @php($selectedService = $this->serviceProfessionalPickerService)

                    <div class="space-y-4">
                        @if ($selectedService)
                            <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 px-4 py-4">
                                <div class="text-sm text-zinc-500">Servicio seleccionado</div>
                                <div class="mt-1 text-lg font-semibold text-zinc-900">{{ $selectedService->name }}</div>
                                <div class="mt-1 text-sm text-zinc-500">S/{{ number_format((float) $selectedService->price, 0) }} · {{ $selectedService->duration_minutes }} min</div>
                            </div>
                        @endif

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-zinc-700">Profesional</div>
                            <div class="text-sm text-zinc-500">Elige quién realizará este servicio para guardarlo en la venta.</div>
                        </div>

                        <div class="grid gap-3">
                            @forelse ($this->serviceProfessionalPickerProfessionals as $professional)
                                <button
                                    type="button"
                                    wire:click="selectServiceProfessional({{ $professional->id }})"
                                    class="flex items-center gap-3 rounded-[22px] border {{ $serviceProfessionalPickerProfessionalId === $professional->id ? 'border-violet-400 bg-violet-50 ring-2 ring-violet-100' : 'border-zinc-200 bg-white' }} px-4 py-4 text-left transition hover:border-violet-300"
                                >
                                    @if ($professional->photoUrl())
                                        <img src="{{ $professional->photoUrl() }}" alt="{{ $professional->displayName() }}" class="size-12 rounded-2xl object-cover">
                                    @else
                                        <div class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-sm font-semibold text-zinc-500">
                                            {{ $professional->initials() }}
                                        </div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-zinc-900">{{ $professional->displayName() }}</div>
                                        <div class="mt-0.5 text-sm text-zinc-500">{{ $selectedService?->duration_minutes ?? 0 }} min · Asignado a este servicio</div>
                                    </div>

                                    <flux:icon.chevron-right class="size-5 text-zinc-400" />
                                </button>
                            @empty
                                <div class="rounded-[22px] border border-dashed border-zinc-300 px-4 py-6 text-sm text-zinc-500">
                                    No hay profesionales vinculados a este servicio.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @elseif ($drawerStep === 'payment')
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($this->paymentMethods as $key => $label)
                                <button
                                    type="button"
                                    wire:click="completeSale('{{ $key }}')"
                                    class="{{ $saleForm['selected_payment_method'] === $key ? 'border-violet-400 ring-2 ring-violet-100' : 'border-zinc-200' }} rounded-2xl border px-4 py-6 text-center text-sm font-medium text-zinc-700"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4 text-sm text-zinc-500">
                            Al tocar un método de pago, la venta se registra automáticamente.
                        </div>
                    </div>
                @elseif ($drawerStep === 'success')
                    @if ($selectedSale)
                        <div class="space-y-6 pb-6">
                            <div class="rounded-[28px] border border-zinc-200 bg-white shadow-[0_20px_50px_rgba(15,23,42,0.06)]">
                                <div class="border-b border-zinc-200/80 px-5 py-4 text-center">
                                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-[0_10px_25px_rgba(16,185,129,0.3)]">
                                        <flux:icon.check class="size-8" />
                                    </div>

                                    <div class="mt-5 text-2xl font-semibold text-zinc-800">
                                        El pago se realizó con éxito
                                    </div>
                                    <div class="mt-1 text-sm text-zinc-500">
                                        Venta #{{ $selectedSale->ticket_number }}
                                    </div>
                                </div>

                                <div class="space-y-5 px-5 py-5">
                                    <div>
                                        <div class="text-sm text-zinc-400">Cliente</div>
                                        <div class="mt-2 text-base font-semibold text-zinc-900">
                                            {{ $selectedSale->client?->fullName() ?? 'Consumidor final' }}
                                        </div>

                                        @if ($selectedSale->client?->email)
                                            <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500">
                                                <flux:icon.envelope class="size-4" />
                                                <span>{{ $selectedSale->client?->email }}</span>
                                            </div>
                                        @endif

                                        @if ($selectedSale->client?->phone)
                                            <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500">
                                                <flux:icon.phone class="size-4" />
                                                <span>{{ $selectedSale->client?->phone }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="border-t border-zinc-200 pt-5">
                                        <div class="text-sm text-zinc-400">Medio de pago</div>
                                        @forelse ($selectedSale->payments as $payment)
                                            <div class="mt-3 flex items-start justify-between gap-3">
                                                <div>
                                                    <div class="font-medium text-zinc-900">{{ $this->paymentMethods[$payment->method] ?? $payment->method }}</div>
                                                    <div class="text-sm text-zinc-500">#{{ $selectedSale->ticket_number }} · {{ $selectedSale->sold_at?->format('d-m-Y') }}</div>
                                                </div>
                                                <div class="font-medium text-zinc-900">S/{{ number_format((float) $payment->amount, 0) }}</div>
                                            </div>
                                        @empty
                                            <div class="mt-3 text-sm text-zinc-500">No hay pagos registrados para esta venta.</div>
                                        @endforelse
                                    </div>

                                    <div class="border-t border-zinc-200 pt-5">
                                        <div class="text-sm text-zinc-400">Detalle del pago</div>
                                        <div class="mt-3 space-y-2 text-zinc-700">
                                            <div class="flex items-center justify-between"><span>Subtotal</span><span>S/{{ number_format((float) $selectedSale->subtotal, 0) }}</span></div>
                                            <div class="flex items-center justify-between"><span>Vuelto</span><span>S/{{ number_format((float) $selectedSale->change_total, 0) }}</span></div>
                                            <div class="flex items-center justify-between"><span>Descuentos</span><span>S/{{ number_format((float) $selectedSale->discount_total, 0) }}</span></div>
                                            <div class="flex items-center justify-between font-semibold text-zinc-900"><span>Total:</span><span>S/{{ number_format((float) $selectedSale->total, 0) }}</span></div>
                                        </div>
                                    </div>

                                    <div class="border-t border-zinc-200 pt-5">
                                        <div class="space-y-3">
                                            @if ($emailReceiptUrl)
                                                <a href="{{ $emailReceiptUrl }}" class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-violet-600 shadow-sm transition hover:bg-violet-50">
                                                    Enviar comprobante
                                                </a>
                                            @else
                                                <button type="button" disabled class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-zinc-400 shadow-sm">
                                                    Enviar comprobante
                                                </button>
                                            @endif

                                            <a href="{{ $receiptUrl }}" target="_blank" class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-violet-600 shadow-sm transition hover:bg-violet-50">
                                                Ver comprobante
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <div class="border-t border-zinc-200 bg-white px-5 py-4">
                <div class="mb-3 flex items-center justify-between">
                    <div class="text-2xl font-semibold text-zinc-800">Total:</div>
                    <div class="text-2xl font-semibold text-zinc-800">S/{{ number_format($drawerStep === 'success' && $selectedSale ? (float) $selectedSale->total : collect($saleForm['cart'])->sum(fn ($item) => (float) $item['subtotal']), 0) }}</div>
                </div>

                @if ($drawerStep === 'cart')
                    <button type="button" wire:click="proceedToPayment" class="flex h-12 w-full items-center justify-center rounded-xl bg-violet-500 font-semibold text-white disabled:opacity-50">
                        Continuar
                    </button>
                @elseif ($drawerStep === 'item-picker')
                    <button type="button" wire:click="backToCart" class="flex h-12 w-full items-center justify-center rounded-xl bg-violet-500 font-semibold text-white">
                        Ir al carro ({{ count($saleForm['cart']) }})
                    </button>
                @elseif ($drawerStep === 'service-professional')
                    <button type="button" wire:click="backToItemPicker" class="flex h-12 w-full items-center justify-center rounded-xl bg-violet-500 font-semibold text-white">
                        Volver a servicios
                    </button>
                @elseif ($drawerStep === 'client-create')
                    <button type="button" wire:click="saveInlineClient" class="flex h-12 w-full items-center justify-center rounded-xl bg-violet-500 font-semibold text-white">
                        Guardar cliente
                    </button>
                @elseif ($drawerStep === 'payment')
                    <div class="flex h-12 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-600">
                        Selecciona un método de pago para registrar la venta
                    </div>
                @elseif ($drawerStep === 'success')
                    <button type="button" wire:click="closeDrawer" class="flex h-12 w-full items-center justify-center rounded-xl bg-violet-500 font-semibold text-white">
                        Cerrar
                    </button>
                @endif
            </div>
        </aside>
    @endif

    <flux:modal name="delete-sale" wire:close="closeDeleteModal" wire:cancel="closeDeleteModal" class="w-full max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Eliminar venta</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">
                    Esta acción ocultará la venta del listado activo y, si incluye productos, devolverá el stock.
                </flux:text>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeDeleteModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="button" wire:click="deleteSale">
                    Eliminar venta
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
