<section >
    <div class="relative w-full overflow-hidden rounded-[24px]">
        <div class="space-y-5 px-1 py-2 sm:px-3 lg:px-0">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-[2rem] font-semibold tracking-tight text-slate-900 dark:text-white">Comisiones</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">Gestiona y revisa las comisiones por profesional, servicios y productos.</p>
                </div>

                <a
                    href="{{ route('administracion.comisiones.reporte') }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-violet-600 shadow-sm transition hover:bg-violet-50 dark:border-white/10 dark:bg-white/[0.02] dark:text-violet-300 dark:shadow-none dark:hover:bg-white/[0.05]"
                >
                    Ver reporte de comisiones
                </a>
            </div>

            <div class="overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="border-b border-zinc-200 px-4 pt-2 dark:border-white/10">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="showServices"
                            @class([
                                'inline-flex items-center gap-2 rounded-t-[18px] border-b-2 px-4 py-3 text-sm font-semibold transition',
                                'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' => $section === 'services',
                                'border-transparent text-zinc-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white' => $section !== 'services',
                            ])
                        >
                            <flux:icon name="wrench-screwdriver" class="size-4" />
                            <span>Servicios</span>
                        </button>

                        <button
                            type="button"
                            wire:click="showProducts"
                            @class([
                                'inline-flex items-center gap-2 rounded-t-[18px] border-b-2 px-4 py-3 text-sm font-semibold transition',
                                'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' => $section === 'products',
                                'border-transparent text-zinc-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white' => $section !== 'products',
                            ])
                        >
                            <flux:icon name="cube" class="size-4" />
                            <span>Productos</span>
                        </button>
                    </div>
                </div>

                @if ($section === 'services')
                    <div class="p-4 sm:p-6">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Servicios asignados a profesionales</h2>
                            <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                Revisa los servicios asignados por profesional y ajusta su comisión por defecto o por servicio.
                            </p>
                        </div>

                        <div class="mt-6 max-w-md">
                            <flux:input
                                wire:model.live.debounce.300ms="professionalSearch"
                                icon="magnifying-glass"
                                clearable
                                placeholder="Buscar profesional o servicio..."
                                class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                            />
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @forelse ($this->professionals as $professional)
                                @php
                                    $initials = collect(explode(' ', (string) $professional->public_name))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                        ->implode('');
                                @endphp

                                <div class="rounded-[22px] border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-zinc-300 dark:border-white/10 dark:bg-[#0f1720] dark:shadow-none">
                                    <div class="flex items-start gap-3">
                                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            {{ $initials }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-semibold uppercase tracking-[0.02em] text-slate-900 dark:text-white">
                                                {{ $professional->public_name }}
                                            </div>
                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ (int) $professional->services_count }} servicios
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="openProfessionalServicesModal({{ $professional->id }})"
                                            class="inline-flex items-center rounded-lg bg-amber-400 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-500"
                                        >
                                            <flux:icon name="pencil-square" class="mr-1.5 size-3.5" />
                                            Editar
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="openProfessionalDefaultModal({{ $professional->id }})"
                                            class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                        >
                                            <flux:icon name="banknotes" class="mr-1.5 size-3.5" />
                                            Comisión
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-zinc-200 p-10 text-center text-sm text-zinc-500 dark:border-white/10 dark:text-zinc-400 sm:col-span-2 xl:col-span-4">
                                    No hay profesionales activos para mostrar.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <h2 class="text-[1.55rem] font-semibold tracking-tight text-slate-900 dark:text-white">Comisiones de productos</h2>
                                <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                    Configura la comisión por defecto de cada producto activo.
                                </p>
                            </div>

                            <div class="w-full max-w-md">
                                <flux:input
                                    wire:model.live.debounce.300ms="productSearch"
                                    icon="magnifying-glass"
                                    clearable
                                    placeholder="Buscar producto..."
                                    class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                />
                            </div>
                        </div>

                        <form wire:submit.prevent="applyCommissionToAllProducts" class="mt-6 rounded-[18px] border border-emerald-100 bg-emerald-50/35 px-4 py-4 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/5 sm:px-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                <div class="flex min-w-0 flex-1 items-start gap-4">
                                    <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 ring-8 ring-emerald-100/40 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-transparent">
                                        <flux:icon name="tag" class="size-7" />
                                    </div>

                                    <div class="min-w-0">
                                        <div class="text-base font-semibold text-slate-900 dark:text-white">
                                            Aplicar porcentaje a todos los productos
                                        </div>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
                                            Establece un porcentaje de comisión para todos los productos activos.
                                        </p>
                                    </div>
                                </div>

                                <div class="grid w-full gap-4 lg:w-auto lg:grid-cols-[11rem_15rem] lg:items-end">
                                    <div class="min-w-0">
                                        <label class="mb-2 block text-sm font-medium text-slate-500 dark:text-zinc-400">
                                            Porcentaje de comisión
                                        </label>

                                        <div class="flex h-10 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a]">
                                            <flux:input
                                                wire:model="bulkProductCommissionPercentage"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                class="h-full flex-1 rounded-none border-0 bg-transparent shadow-none ring-0 focus:ring-0 dark:text-white"
                                            />
                                            <div class="inline-flex h-full shrink-0 items-center border-l border-zinc-200 bg-white px-3 text-sm font-semibold text-zinc-500 dark:border-white/10 dark:bg-[#0d131a] dark:text-zinc-400">
                                                %
                                            </div>
                                        </div>
                                    </div>

                                    <flux:button
                                        type="submit"
                                        variant="primary"
                                        icon="check-circle"
                                        class="h-10 w-full self-end rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none"
                                        wire:target="applyCommissionToAllProducts"
                                        wire:loading.attr="disabled"
                                    >
                                        Aplicar a todos
                                    </flux:button>
                                </div>
                            </div>
                        </form>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @forelse ($this->products as $product)
                                <div class="rounded-[22px] border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-zinc-300 dark:border-white/10 dark:bg-[#0f1720] dark:shadow-none">
                                    <div class="min-w-0">
                                        <div class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ $product->name }}</div>
                                        <div class="mt-2 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                            {{ $this->commissionBadge((float) $product->sale_commission, $product->commission_type) }}
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button
                                            type="button"
                                            wire:click="openProductModal({{ $product->id }})"
                                            class="inline-flex h-10 items-center rounded-xl border border-zinc-200 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.02] dark:text-white dark:hover:bg-white/[0.05]"
                                        >
                                            <flux:icon name="pencil-square" class="mr-1.5 size-3.5" />
                                            Editar
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-zinc-200 p-10 text-center text-sm text-zinc-500 dark:border-white/10 dark:text-zinc-400 sm:col-span-2 xl:col-span-4">
                                    No se encontraron productos.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
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
