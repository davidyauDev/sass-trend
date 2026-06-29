<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="rounded-[28px] border border-zinc-200/80 bg-white p-3 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="grid gap-2">
                    <button
                        type="button"
                        wire:click="showServices"
                        @class([
                            'rounded-2xl border px-4 py-4 text-left transition',
                            'border-zinc-200 bg-white text-zinc-900 shadow-sm' => $section === 'services',
                            'border-transparent bg-zinc-100 text-zinc-400' => $section !== 'services',
                        ])
                    >
                        <div class="text-sm font-semibold">Servicios</div>
                    </button>

                    <button
                        type="button"
                        wire:click="showProducts"
                        @class([
                            'rounded-2xl border px-4 py-4 text-left transition',
                            'border-zinc-200 bg-white text-zinc-900 shadow-sm' => $section === 'products',
                            'border-transparent bg-zinc-100 text-zinc-400' => $section !== 'products',
                        ])
                    >
                        <div class="text-sm font-semibold">Productos</div>
                    </button>
                </div>

                <a
                    href="{{ route('administracion.comisiones.reporte') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-violet-600 shadow-sm transition hover:bg-violet-50"
                >
                    Ver reporte de comisiones
                </a>
            </div>
        </div>

        @if ($section === 'services')
            <div class="rounded-[28px] border border-zinc-200/80 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <flux:heading size="xl">Servicios</flux:heading>
                        <flux:text class="mt-2 max-w-3xl text-sm text-zinc-500">
                            Revisa los servicios asignados por profesional y ajusta su comisión por defecto o por servicio.
                        </flux:text>
                    </div>
                </div>

                <div class="mt-8 grid gap-4">
                    @forelse ($this->professionals as $professional)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-100 px-4 py-4 shadow-sm">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <div class="text-lg font-semibold text-cyan-800">{{ $professional->public_name }}</div>
                                    <div class="text-sm text-zinc-600">Número de servicios {{ (int) $professional->services_count }}</div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        wire:click="openProfessionalServicesModal({{ $professional->id }})"
                                        class="inline-flex items-center rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="openProfessionalDefaultModal({{ $professional->id }})"
                                        class="inline-flex items-center rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600"
                                    >
                                        Editar Por Defecto
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center text-sm text-zinc-500">
                            No hay profesionales activos para mostrar.
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="rounded-[28px] border border-zinc-200/80 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <flux:heading size="xl">Productos</flux:heading>
                    </div>

                    <div class="w-full max-w-md">
                        <flux:input
                            wire:model.live.debounce.300ms="productSearch"
                            icon="magnifying-glass"
                            clearable
                            placeholder="Búsqueda rápida"
                        />
                    </div>
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    @forelse ($this->products as $product)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-100 px-4 py-4 shadow-sm">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-lg font-semibold text-cyan-800">{{ $product->name }}</div>
                                    <div class="text-sm text-zinc-600">
                                        Comisión Por Defecto:
                                        {{ $this->commissionBadge((float) $product->sale_commission, $product->commission_type) }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="openProductModal({{ $product->id }})"
                                    class="inline-flex items-center rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500"
                                >
                                    Editar
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center text-sm text-zinc-500 md:col-span-2">
                            No se encontraron productos.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    <flux:modal name="professional-services" wire:close="closeProfessionalServicesModal" wire:cancel="closeProfessionalServicesModal" class="w-full max-w-5xl">
        <form wire:submit.prevent="saveProfessionalServices" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Editando comisiones para {{ $this->selectedProfessional?->public_name ?? 'Profesional' }}
                </flux:heading>
            </div>

            @if ($this->selectedProfessional && $this->selectedProfessional->services->isNotEmpty())
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($professionalServiceForm->rows as $index => $row)
                        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <div class="text-sm font-semibold text-zinc-800">{{ $row['service_name'] }}</div>

                            <div class="mt-3 grid grid-cols-[minmax(0,1fr)_6rem] gap-2">
                                <flux:input
                                    wire:model="professionalServiceForm.rows.{{ $index }}.sale_commission"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    label="Comisión"
                                />
                                <flux:select
                                    wire:model="professionalServiceForm.rows.{{ $index }}.commission_type"
                                    label="&nbsp;"
                                >
                                    <option value="percent">%</option>
                                    <option value="amount">$</option>
                                </flux:select>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-zinc-200 p-6 text-sm text-zinc-500">
                    Este profesional no tiene servicios asignados todavía.
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeProfessionalServicesModal">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="professional-default-commission" wire:close="closeProfessionalDefaultModal" wire:cancel="closeProfessionalDefaultModal" class="w-full max-w-2xl">
        <form wire:submit.prevent="saveProfessionalDefaultCommission" class="space-y-6">
            <div>
                <flux:heading size="lg">Editando comisión por defecto</flux:heading>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-[minmax(0,1fr)_6rem] gap-2">
                    <flux:input
                        wire:model="professionalDefaultForm.sale_commission"
                        type="number"
                        min="0"
                        step="0.01"
                        label="Comisión por defecto"
                    />
                    <flux:select wire:model="professionalDefaultForm.commission_type" label="Unidad">
                        <option value="percent">%</option>
                        <option value="amount">$</option>
                    </flux:select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeProfessionalDefaultModal">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="product-commission" wire:close="closeProductModal" wire:cancel="closeProductModal" class="w-full max-w-2xl">
        <form wire:submit.prevent="saveProductCommission" class="space-y-6">
            <div>
                <flux:heading size="lg">Editando comisión por defecto</flux:heading>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-[minmax(0,1fr)_6rem] gap-2">
                    <flux:input
                        wire:model="productForm.sale_commission"
                        type="number"
                        min="0"
                        step="0.01"
                        label="Comisión por defecto"
                    />
                    <flux:select wire:model="productForm.commission_type" label="Unidad">
                        <option value="percent">%</option>
                        <option value="amount">$</option>
                    </flux:select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeProductModal">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
