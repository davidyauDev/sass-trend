<section class="">
    @php
        $summary = $this->summary;
        $rows = $this->reportRowsPaginator;
        $totalSales = max(0, (float) $summary['total_sales']);
    @endphp

    <div class="mx-auto max-w-[1520px] space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl" class="text-[2.1rem] font-semibold tracking-tight text-slate-900">
                    Reporte de comisiones
                </flux:heading>
                <p class="mt-2 max-w-3xl text-sm text-slate-600 sm:text-[1rem]">
                    Calcula cuánto debes pagarle de comisión a cada profesional según las ventas del período.
                </p>
            </div>

            <flux:button
                variant="outline"
                href="{{ route('administracion.comisiones.index') }}"
                icon="arrow-left"
                class="h-12 rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Volver a configuración
            </flux:button>
        </div>

        <div class="rounded-[20px] border border-white/70 bg-white px-4 py-4 shadow-[0_10px_30px_rgba(15,23,42,0.06)] sm:px-5 sm:py-5">
            <div class="grid gap-4 xl:grid-cols-[1.05fr_1fr_1fr_1fr_auto] xl:items-end">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-800">Período</label>
                    <flux:select
                        wire:model="period"
                        class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                    >
                        @foreach ($this->periodOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-800">Locales</label>
                    <flux:select
                        wire:model="branchId"
                        class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                    >
                        <option value="">Todos los locales</option>
                        @foreach ($this->branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-800">Tipo de usuario</label>
                    <flux:select
                        wire:model="userType"
                        class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                    >
                        <option value="active_professionals">Prestadores activos</option>
                        <option value="all_professionals">Todos los profesionales</option>
                    </flux:select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-800">Usuarios</label>
                    <flux:select
                        wire:model="professionalId"
                        class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                    >
                        <option value="all">Todos seleccionados</option>
                        @foreach ($this->availableProfessionals as $professional)
                            <option value="{{ $professional->id }}">{{ $professional->public_name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:button
                    type="button"
                    wire:click="applyFilters"
                    variant="primary"
                    icon="magnifying-glass"
                    class="h-12 w-full rounded-xl bg-violet-600 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                >
                    Buscar
                </flux:button>
            </div>
        </div>

        <div class="overflow-hidden rounded-[20px] border border-white/70 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
            <div class="grid xl:grid-cols-3 xl:divide-x xl:divide-slate-200">
                <div class="flex items-center gap-5 px-6 py-6">
                    <div class="flex size-20 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <flux:icon name="shopping-cart" class="size-9" />
                    </div>

                    <div class="min-w-0">
                        <div class="text-base text-slate-700">Ventas de servicios</div>
                        <div class="mt-1 text-[2rem] font-semibold leading-none text-emerald-600">
                            {{ $this->money((float) $summary['service_sales']) }}
                        </div>
                        <div class="mt-2 text-sm text-slate-500">
                            {{ $this->percentOf((float) $summary['service_sales'], $totalSales) }} del total de ventas
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-5 border-t border-slate-200 px-6 py-6 xl:border-t-0">
                    <div class="flex size-20 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-600">
                        <flux:icon name="shopping-bag" class="size-9" />
                    </div>

                    <div class="min-w-0">
                        <div class="text-base text-slate-700">Venta de productos</div>
                        <div class="mt-1 text-[2rem] font-semibold leading-none text-sky-600">
                            {{ $this->money((float) $summary['product_sales']) }}
                        </div>
                        <div class="mt-2 text-sm text-slate-500">
                            {{ $this->percentOf((float) $summary['product_sales'], $totalSales) }} del total de ventas
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-5 border-t border-slate-200 px-6 py-6 xl:border-t-0">
                    <div class="flex size-20 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                        <flux:icon name="circle-stack" class="size-9" />
                    </div>

                    <div class="min-w-0">
                        <div class="text-base text-slate-700">Comisiones generadas</div>
                        <div class="mt-1 text-[2rem] font-semibold leading-none text-violet-600">
                            {{ $this->money((float) $summary['total_commissions']) }}
                        </div>
                        <div class="mt-2 text-sm text-slate-500">
                            {{ $this->percentOf((float) $summary['total_commissions'], $totalSales) }} del total de ventas
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-[20px] border border-white/70 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-6 text-base font-semibold text-slate-900">
                    <div>
                        Ventas:
                        <span class="text-emerald-600">{{ $this->money((float) $summary['total_sales']) }}</span>
                    </div>

                    <div>
                        Comisiones:
                        <span class="text-violet-600">{{ $this->money((float) $summary['total_commissions']) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 self-start lg:self-auto">
                    <flux:button
                        type="button"
                        wire:click="exportReport"
                        variant="outline"
                        icon="arrow-down-tray"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                    >
                        Exportar
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="outline"
                        icon="cog-6-tooth"
                        class="size-11 rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50"
                        aria-label="Configuración del reporte"
                    />
                </div>
            </div>

            <div class="px-5 pt-4 text-right text-sm text-slate-400">
                Mostrando {{ $rows->count() }} de {{ $rows->total() }} registros
            </div>

            <div class="overflow-x-auto px-5 pt-4">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr class="text-left text-sm font-semibold text-slate-700">
                            <th class="border-y border-slate-200 bg-slate-50 px-4 py-4">
                                <button type="button" wire:click="sortBy('professional_name')" class="inline-flex items-center gap-1.5">
                                    Profesional
                                    <span class="text-xs text-slate-500">{{ $this->sortIndicator('professional_name') }}</span>
                                </button>
                            </th>
                            <th class="border-y border-slate-200 bg-slate-50 px-4 py-4 text-right">
                                <button type="button" wire:click="sortBy('sales_total')" class="inline-flex items-center gap-1.5">
                                    Ventas
                                    <span class="text-xs text-slate-500">{{ $this->sortIndicator('sales_total') }}</span>
                                </button>
                            </th>
                            <th class="border-y border-slate-200 bg-slate-50 px-4 py-4 text-right">
                                <button type="button" wire:click="sortBy('commission_amount')" class="inline-flex items-center gap-1.5">
                                    Comisión
                                    <span class="text-xs text-slate-500">{{ $this->sortIndicator('commission_amount') }}</span>
                                </button>
                            </th>
                            <th class="border-y border-slate-200 bg-slate-50 px-4 py-4"></th>
                        </tr>
                    </thead>

                    <tbody class="bg-white">
                        @forelse ($rows as $row)
                            @php
                                $avatarClasses = [
                                    'bg-violet-100 text-violet-700',
                                    'bg-emerald-100 text-emerald-700',
                                    'bg-orange-100 text-orange-700',
                                    'bg-sky-100 text-sky-700',
                                ][($row['professional_id'] - 1) % 4];

                                $salesShare = $this->percentOf((float) $row['sales_total'], $totalSales);
                                $commissionShare = $this->percentOf((float) $row['commission_amount'], $totalSales);
                                $initials = collect(explode(' ', (string) $row['professional_name']))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->implode('');
                            @endphp

                            <tr class="group">
                                <td class="border-b border-slate-200 px-4 py-4 align-middle">
                                    <div class="flex items-center gap-4">
                                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $avatarClasses }} text-sm font-semibold">
                                            {{ $initials }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="truncate text-base font-semibold uppercase tracking-tight text-slate-900">
                                                {{ $row['professional_name'] }}
                                            </div>

                                            <div class="mt-2 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Prestador activo
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="border-b border-slate-200 px-4 py-4 text-right align-middle">
                                    <div class="text-base font-semibold text-slate-900">
                                        {{ $this->money((float) $row['sales_total']) }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ $salesShare }} del total
                                    </div>
                                </td>

                                <td class="border-b border-slate-200 px-4 py-4 text-right align-middle">
                                    <div class="text-base font-semibold text-slate-900">
                                        {{ $this->money((float) $row['commission_amount']) }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ $commissionShare }} del total
                                    </div>
                                </td>

                                <td class="border-b border-slate-200 px-4 py-4 text-right align-middle">
                                    <span class="inline-flex size-10 items-center justify-center text-slate-400 transition group-hover:text-slate-600">
                                        <flux:icon name="chevron-right" class="size-5" />
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    No hay ventas con profesionales asignados en el período seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid gap-4 border-t border-slate-200 px-5 py-4 md:grid-cols-[1fr_auto_1fr] md:items-center">
                <div class="text-sm text-slate-600">
                    Mostrando {{ $rows->firstItem() ?? 0 }} a {{ $rows->lastItem() ?? 0 }} de {{ $rows->total() }} registros
                </div>

                <div class="justify-self-center">
                    <flux:pagination :paginator="$rows" />
                </div>

                <div class="justify-self-end">
                    <flux:select
                        wire:model.live="perPage"
                        class="h-11 min-w-[10rem] rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                    >
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                    </flux:select>
                </div>
            </div>
        </div>
    </div>
</section>
