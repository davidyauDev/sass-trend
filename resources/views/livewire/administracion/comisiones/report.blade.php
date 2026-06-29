<section class="w-full bg-[#eef3f7] px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl space-y-5">
        <div class="space-y-1">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="xl">Reporte de comisiones</flux:heading>
                    <p class="text-sm text-slate-600">
                        Calcula cuánto debes pagarle de comisión a cada profesional según las ventas del período.
                    </p>
                </div>

                <a
                    href="{{ route('administracion.comisiones.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Volver a configuración
                </a>
            </div>
        </div>

        <div class="rounded-[18px] border border-white/80 bg-white px-4 py-4 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Período</label>
                    <flux:select wire:model="period">
                        @foreach ($this->periodOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Locales</label>
                    <flux:select wire:model="branchId">
                        <option value="">Todos los locales</option>
                        @foreach ($this->branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Tipo de usuario</label>
                    <flux:select wire:model="userType">
                        <option value="active_professionals">Prestadores activos</option>
                        <option value="all_professionals">Todos los profesionales</option>
                    </flux:select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Usuarios</label>
                    <flux:select wire:model="professionalId">
                        <option value="all">Todos seleccionados</option>
                        @foreach ($this->availableProfessionals as $professional)
                            <option value="{{ $professional->id }}">{{ $professional->public_name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex items-end sm:col-span-2 lg:col-span-1">
                    <button
                        type="button"
                        wire:click="applyFilters"
                        class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-[10px] bg-violet-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                    >
                        <flux:icon.magnifying-glass class="size-4" />
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 rounded-[18px] border border-white/80 bg-white px-6 py-5 shadow-sm sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <div class="text-sm text-slate-700">Ventas de servicios</div>
                <div class="mt-1 text-[2rem] font-semibold leading-none text-slate-900">{{ $this->money($this->summary['service_sales']) }}</div>
            </div>

            <div>
                <div class="text-sm text-slate-700">Venta de productos</div>
                <div class="mt-1 text-[2rem] font-semibold leading-none text-slate-900">{{ $this->money($this->summary['product_sales']) }}</div>
            </div>

            <div>
                <div class="text-sm text-slate-700">Comisiones generadas</div>
                <div class="mt-1 text-[2rem] font-semibold leading-none text-slate-900">{{ $this->money($this->summary['total_commissions']) }}</div>
            </div>
        </div>

        <div class="rounded-[18px] border border-white/80 bg-white px-4 py-4 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-2 pb-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm sm:text-[1.05rem] font-semibold text-slate-900">
                    <div>
                        Ventas:
                        <span class="text-emerald-600">{{ $this->money($this->summary['total_sales']) }}</span>
                    </div>
                    <div>
                        Comisiones:
                        <span class="text-amber-500">{{ $this->money($this->summary['total_commissions']) }}</span>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="exportReport"
                    class="inline-flex items-center justify-center gap-2 rounded-[10px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <flux:icon.arrow-down-tray class="size-4" />
                    Exportar
                </button>
            </div>

            <div class="px-2 pt-4 text-right text-sm text-slate-400">
                Mostrando {{ $this->reportRows->count() }} de {{ $this->reportRows->count() }} registros
            </div>

            <div class="mt-3 overflow-x-auto -mx-4 sm:-mx-0">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm font-semibold text-slate-700">
                            <th class="border-y border-slate-200 bg-slate-50 px-3 sm:px-4 py-4">
                                <button type="button" wire:click="sortBy('professional_name')" class="inline-flex items-center gap-1">
                                    Profesional
                                    <span class="text-xs text-slate-500">{{ $this->sortIndicator('professional_name') }}</span>
                                </button>
                            </th>
                            <th class="border-y border-slate-200 bg-slate-50 px-3 sm:px-4 py-4 text-right">
                                <button type="button" wire:click="sortBy('sales_total')" class="inline-flex items-center gap-1">
                                    Ventas
                                    <span class="text-xs text-slate-500">{{ $this->sortIndicator('sales_total') }}</span>
                                </button>
                            </th>
                            <th class="border-y border-slate-200 bg-slate-50 px-3 sm:px-4 py-4 text-right">
                                <button type="button" wire:click="sortBy('commission_amount')" class="inline-flex items-center gap-1">
                                    Comisión
                                    <span class="text-xs text-slate-500">{{ $this->sortIndicator('commission_amount') }}</span>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white text-xs sm:text-sm text-slate-700">
                        @forelse ($this->reportRows as $row)
                            <tr>
                                <td class="border-b border-slate-200 px-3 sm:px-4 py-4">{{ $row['professional_name'] }}</td>
                                <td class="border-b border-slate-200 px-3 sm:px-4 py-4 text-right">{{ $this->money($row['sales_total']) }}</td>
                                <td class="border-b border-slate-200 px-3 sm:px-4 py-4 text-right">{{ $this->money($row['commission_amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    No hay ventas con profesionales asignados en el período seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
