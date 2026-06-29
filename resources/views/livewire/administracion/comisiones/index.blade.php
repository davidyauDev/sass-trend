<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="rounded-[28px] border border-zinc-200/80 bg-white p-3 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-2">
                    <button
                        type="button"
                        wire:click="showServices"
                        @class([
                            'flex-1 sm:flex-none rounded-2xl border px-4 py-3 text-left transition',
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
                            'flex-1 sm:flex-none rounded-2xl border px-4 py-3 text-left transition',
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

                <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($this->professionals as $professional)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-100 px-4 py-4 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-lg font-semibold text-cyan-800">{{ $professional->public_name }}</div>
                                    <div class="text-sm text-zinc-600">{{ (int) $professional->services_count }} servicios</div>
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
                                        Comisión
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center text-sm text-zinc-500 sm:col-span-2 xl:col-span-3">
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

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($this->products as $product)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-100 px-4 py-4 shadow-sm">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-lg font-semibold text-cyan-800">{{ $product->name }}</div>
                                    <div class="text-sm text-zinc-600">
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
                        <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center text-sm text-zinc-500 sm:col-span-2 lg:col-span-3">
                            No se encontraron productos.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    <flux:modal name="professional-services" wire:close="closeProfessionalServicesModal" wire:cancel="closeProfessionalServicesModal" class="w-full max-w-5xl mx-4 sm:mx-6">
        <form wire:submit.prevent="saveProfessionalServices" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $this->selectedProfessional?->public_name ?? 'Profesional' }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Editar comisiones por servicio</flux:text>
            </div>

            @if ($this->selectedProfessional && $this->selectedProfessional->services->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($professionalServiceForm->rows as $index => $row)
                        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <div class="text-sm font-semibold text-zinc-800">{{ $row['service_name'] }}</div>

                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_5rem] gap-2">
                                <flux:input
                                    wire:model="professionalServiceForm.rows.{{ $index }}.sale_commission"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    label="Comisión"
                                />
                                <flux:select
                                    wire:model="professionalServiceForm.rows.{{ $index }}.commission_type"
                                    label="Tipo"
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

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeProfessionalServicesModal" class="w-full sm:w-auto">Cancelar</flux:button>
                <flux:button variant="primary" type="submit" class="w-full sm:w-auto">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="professional-default-commission" wire:close="closeProfessionalDefaultModal" wire:cancel="closeProfessionalDefaultModal" class="w-full max-w-lg mx-4 sm:mx-6">
        <form wire:submit.prevent="saveProfessionalDefaultCommission" class="space-y-6">
            <div>
                <flux:heading size="lg">Comisión por defecto</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Configura la comisión general del profesional</flux:text>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_5rem] gap-2">
                    <flux:input
                        wire:model="professionalDefaultForm.sale_commission"
                        type="number"
                        min="0"
                        step="0.01"
                        label="Comisión por defecto"
                    />
                    <flux:select wire:model="professionalDefaultForm.commission_type" label="Tipo">
                        <option value="percent">%</option>
                        <option value="amount">$</option>
                    </flux:select>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeProfessionalDefaultModal" class="w-full sm:w-auto">Cancelar</flux:button>
                <flux:button variant="primary" type="submit" class="w-full sm:w-auto">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="product-commission" wire:close="closeProductModal" wire:cancel="closeProductModal" class="w-full max-w-lg mx-4 sm:mx-6">
        <form wire:submit.prevent="saveProductCommission" class="space-y-6">
            <div>
                <flux:heading size="lg">Comisión del producto</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Configura la comisión por defecto de este producto</flux:text>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_5rem] gap-2">
                    <flux:input
                        wire:model="productForm.sale_commission"
                        type="number"
                        min="0"
                        step="0.01"
                        label="Comisión por defecto"
                    />
                    <flux:select wire:model="productForm.commission_type" label="Tipo">
                        <option value="percent">%</option>
                        <option value="amount">$</option>
                    </flux:select>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeProductModal" class="w-full sm:w-auto">Cancelar</flux:button>
                <flux:button variant="primary" type="submit" class="w-full sm:w-auto">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
