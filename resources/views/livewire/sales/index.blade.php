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

<section >
    <div class="space-y-5 sm:space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-[2rem] font-semibold tracking-tight text-slate-900 dark:text-white">Ventas</h1>
                <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">Gestiona y consulta todas las ventas realizadas en tu negocio.</p>
            </div>

            <flux:button variant="primary" icon="plus" wire:click="openCreateSale" class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none w-full sm:w-auto">
                Nueva venta
            </flux:button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="flex items-start gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">
                        <flux:icon.shopping-bag class="size-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-zinc-400">Todas las ventas</div>
                        <div class="mt-2 text-3xl font-semibold text-slate-900 dark:text-white">{{ $this->metrics['all'] }}</div>
                    </div>
                </div>
                <div class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->metrics['period_label'] }}</div>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->metrics['all_change'] !== null)
                        <span class="inline-flex items-center rounded-full px-2 py-1 font-semibold {{ $this->metrics['all_change'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }}">
                            {{ $this->metrics['all_change'] >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $this->metrics['all_change'], 1), '0'), '.') }}%
                        </span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $this->metrics['comparison_label'] }}</span>
                    @endif
                </div>
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="flex items-start gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <flux:icon.banknotes class="size-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-zinc-400">Pagos parciales</div>
                        <div class="mt-2 text-3xl font-semibold text-slate-900 dark:text-white">{{ $this->metrics['partial'] }}</div>
                    </div>
                </div>
                <div class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->metrics['period_label'] }}</div>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->metrics['partial_change'] !== null)
                        <span class="inline-flex items-center rounded-full px-2 py-1 font-semibold {{ $this->metrics['partial_change'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }}">
                            {{ $this->metrics['partial_change'] >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $this->metrics['partial_change'], 1), '0'), '.') }}%
                        </span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $this->metrics['comparison_label'] }}</span>
                    @endif
                </div>
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="flex items-start gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                        <flux:icon.trash class="size-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-zinc-400">Eliminadas</div>
                        <div class="mt-2 text-3xl font-semibold text-slate-900 dark:text-white">{{ $this->metrics['deleted'] }}</div>
                    </div>
                </div>
                <div class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->metrics['period_label'] }}</div>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->metrics['deleted_change'] !== null)
                        <span class="inline-flex items-center rounded-full px-2 py-1 font-semibold {{ $this->metrics['deleted_change'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }}">
                            {{ $this->metrics['deleted_change'] >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $this->metrics['deleted_change'], 1), '0'), '.') }}%
                        </span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $this->metrics['comparison_label'] }}</span>
                    @endif
                </div>
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="flex items-start gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300">
                        <flux:icon.currency-dollar class="size-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-zinc-400">Ventas totales</div>
                        <div class="mt-2 text-3xl font-semibold text-slate-900 dark:text-white">S/{{ number_format((float) $this->metrics['total_amount'], 0) }}</div>
                    </div>
                </div>
                <div class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->metrics['period_label'] }}</div>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if ($this->metrics['total_amount_change'] !== null)
                        <span class="inline-flex items-center rounded-full px-2 py-1 font-semibold {{ $this->metrics['total_amount_change'] >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }}">
                            {{ $this->metrics['total_amount_change'] >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $this->metrics['total_amount_change'], 1), '0'), '.') }}%
                        </span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $this->metrics['comparison_label'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-[24px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
            <div class="grid gap-3 xl:grid-cols-[minmax(11rem,0.9fr)_minmax(12rem,1fr)_minmax(10rem,0.8fr)_minmax(11rem,0.9fr)_minmax(10rem,0.85fr)_auto]">
                <flux:select wire:model.live="periodFilter" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                    <option value="7">Ultimos 7 dias</option>
                    <option value="30">Ultimos 30 dias</option>
                    <option value="90">Ultimos 90 dias</option>
                    <option value="all">Todo el historial</option>
                </flux:select>

                <flux:select wire:model.live="clientFilter" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                    <option value="">Cliente</option>
                    @foreach ($this->clientsCatalog as $client)
                        <option value="{{ $client->id }}">{{ $client->fullName() }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="statusFilter" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                    <option value="">Todos</option>
                    <option value="paid">Pagada</option>
                    <option value="partial">Parcial</option>
                    <option value="draft">Borrador</option>
                    <option value="deleted">Eliminada</option>
                </flux:select>

                <flux:select wire:model.live="paymentMethodFilter" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                    <option value="">Todos</option>
                    @foreach ($this->paymentMethods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="branchFilter" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                    <option value="">Todos</option>
                    @foreach ($this->branchesCatalog as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </flux:select>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="sortColumnsToggle" class="inline-flex h-12 items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-zinc-50 dark:border-white/10 dark:bg-[#0d131a] dark:text-white dark:shadow-none">
                        Filtros
                    </button>
                    <button type="button" wire:click="clearFilters" class="inline-flex h-12 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 px-4 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-300">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
            <div class="flex flex-col gap-3 border-b border-zinc-200 px-4 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="$set('statusFilter', '')" class="inline-flex items-center border-b-2 px-3 py-2 text-sm font-semibold transition {{ $statusFilter === '' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' : 'border-transparent text-zinc-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white' }}">
                        Todas las ventas
                    </button>
                    <button type="button" wire:click="$set('statusFilter', 'partial')" class="inline-flex items-center border-b-2 px-3 py-2 text-sm font-semibold transition {{ $statusFilter === 'partial' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' : 'border-transparent text-zinc-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white' }}">
                        Pagos parciales
                    </button>
                    <button type="button" wire:click="$set('statusFilter', 'deleted')" class="inline-flex items-center border-b-2 px-3 py-2 text-sm font-semibold transition {{ $statusFilter === 'deleted' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' : 'border-transparent text-zinc-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white' }}">
                        Eliminadas
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="sortColumnsToggle" class="inline-flex h-10 items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-white dark:shadow-none">
                        Editar columnas
                    </button>
                    <a href="{{ route('sales.export', request()->query()) }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-white dark:shadow-none">
                        Exportar
                    </a>
                </div>
            </div>

            @if ($showColumnEditor)
                <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                        @foreach ($visibleColumns as $column => $visible)
                            <flux:checkbox wire:model.live="visibleColumns.{{ $column }}" :label="ucfirst($column)" />
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-3 p-3 sm:hidden">
                @forelse ($sales as $sale)
                    @php
                        $saleStatus = (string) $sale->status;
                        $statusLabel = match ($saleStatus) {
                            'paid' => 'Pagada',
                            'partial' => 'Abono',
                            'draft' => 'Borrador',
                            default => ucfirst($saleStatus),
                        };
                        $statusBadgeClass = match ($saleStatus) {
                            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'partial' => 'bg-amber-50 text-amber-700 ring-amber-100',
                            'draft' => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
                            default => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
                        };
                    @endphp

                    <article x-data="{ expanded: false }" class="overflow-hidden rounded-[22px] border border-zinc-200 bg-white shadow-sm">
                        <button type="button" class="flex w-full items-start gap-3 px-4 py-4 text-left" @click="expanded = !expanded">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-base font-semibold leading-tight text-zinc-900">
                                    #{{ $sale->sale_number ?? $sale->id }}
                                </div>
                                <div class="mt-0.5 truncate text-xs text-zinc-500">
                                    {{ $sale->client?->fullName() ?? 'Consumidor final' }}
                                </div>
                                <div class="mt-1 truncate text-xs text-zinc-400">
                                    {{ $sale->sold_at?->format('d/m/Y - h:i a') }} · {{ $sale->branch?->name ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold leading-tight text-emerald-600">
                                    S/ {{ number_format((float) $sale->total, 2) }}
                                </div>
                                <div class="mt-0.5 text-xs text-zinc-500">
                                    {{ $sale->user?->name ?? 'Sin usuario' }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="{{ $statusBadgeClass }} inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset">
                                    {{ $statusLabel }}
                                </span>

                                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 transition"
                                    :class="expanded ? 'border-violet-300 text-violet-700' : ''">
                                    <flux:icon.chevron-down class="size-4 transition-transform" :class="expanded ? 'rotate-180' : ''" />
                                </span>
                            </div>
                        </button>

                        <div x-show="expanded" x-cloak x-transition class="border-t border-zinc-100 px-4 py-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                    <div class="text-[11px] uppercase tracking-wide text-zinc-500">Cliente</div>
                                    <div class="mt-1 font-medium text-zinc-900">{{ $sale->client?->fullName() ?? 'Consumidor final' }}</div>
                                </div>

                                <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                    <div class="text-[11px] uppercase tracking-wide text-zinc-500">Registrado por</div>
                                    <div class="mt-1 font-medium text-zinc-900">{{ $sale->user?->name ?? 'Sin usuario' }}</div>
                                </div>

                                <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                    <div class="text-[11px] uppercase tracking-wide text-zinc-500">Local</div>
                                    <div class="mt-1 font-medium text-zinc-900">{{ $sale->branch?->name ?? 'N/A' }}</div>
                                </div>

                                <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                    <div class="text-[11px] uppercase tracking-wide text-zinc-500">Estado</div>
                                    <div class="mt-1 font-medium {{ $saleStatus === 'paid' ? 'text-emerald-600' : ($saleStatus === 'partial' ? 'text-amber-600' : 'text-zinc-600') }}">
                                        {{ $statusLabel }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <button type="button" wire:click="openSaleDetail({{ $sale->id }})" class="inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-zinc-700 shadow-sm transition active:scale-[0.99]">
                                    <flux:icon.eye class="size-4" />
                                    Ver detalle
                                </button>

                                <a href="{{ route('sales.receipt.show', $sale->id) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-violet-600 shadow-sm transition active:scale-[0.99]">
                                    <flux:icon.document-text class="size-4" />
                                    Comprobante
                                </a>

                                @if (! $sale->trashed())
                                    <button type="button" wire:click="confirmDelete({{ $sale->id }})" class="col-span-2 inline-flex items-center justify-center gap-2 rounded-xl bg-rose-500 px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition active:scale-[0.99]">
                                        <flux:icon.trash class="size-4" />
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[22px] border border-zinc-200 bg-white px-5 py-12 text-center text-zinc-500 shadow-sm">
                        No hay ventas para mostrar.
                    </div>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr>
                            @if ($visibleColumns['id']) <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">ID</th> @endif
                            @if ($visibleColumns['date']) <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Fecha</th> @endif
                            @if ($visibleColumns['client']) <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Cliente</th> @endif
                            @if ($visibleColumns['amount']) <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Monto</th> @endif
                            <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Metodo de pago</th>
                            <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Registrado por</th>
                            @if ($visibleColumns['branch']) <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Local</th> @endif
                            @if ($visibleColumns['status']) <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Estado</th> @endif
                            <th class="border-b border-zinc-200 px-5 py-4 text-right text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            @php
                                $saleStatus = (string) $sale->status;
                                $statusLabel = match ($saleStatus) {
                                    'paid' => 'Completada',
                                    'partial' => 'Parcial',
                                    'draft' => 'Borrador',
                                    'deleted' => 'Eliminada',
                                    default => ucfirst($saleStatus),
                                };
                                $statusBadgeClass = match ($saleStatus) {
                                    'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                                    'partial' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                                    'draft' => 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.05] dark:text-zinc-400',
                                    'deleted' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                                    default => 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.05] dark:text-zinc-400',
                                };
                                $payment = $sale->payments->first();
                            @endphp
                            <tr>
                                @if ($visibleColumns['id']) <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">#{{ $sale->sale_number ?? $sale->id }}</td> @endif
                                @if ($visibleColumns['date']) <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                    <div class="text-sm text-slate-700 dark:text-zinc-200">{{ $sale->sold_at?->format('d/m/Y - h:i a') }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $sale->sold_at?->diffForHumans() }}</div>
                                </td> @endif
                                @if ($visibleColumns['client']) <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $sale->client?->fullName() ?? 'Consumidor final' }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $sale->client?->phone ?? 'Sin telefono' }}</div>
                                </td> @endif
                                @if ($visibleColumns['amount']) <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm font-medium text-slate-900 dark:border-white/5 dark:text-white">S/{{ number_format((float) $sale->total, 0) }}</td> @endif
                                <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                    <div class="text-sm text-slate-700 dark:text-zinc-200">{{ $payment?->method ? ($this->paymentMethods[$payment->method] ?? $payment->method) : 'Sin pago' }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $payment?->reference ?: '' }}</div>
                                </td>
                                <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                    <div class="text-sm text-slate-700 dark:text-zinc-200">{{ $sale->user?->name ?? 'Sin usuario' }}</div>
                                </td>
                                @if ($visibleColumns['branch']) <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">{{ $sale->branch?->name ?? 'N/A' }}</td> @endif
                                @if ($visibleColumns['status']) <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5"><span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadgeClass }}">{{ $statusLabel }}</span></td> @endif
                                <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="openSaleDetail({{ $sale->id }})" class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]">
                                            <flux:icon.eye class="size-4" />
                                        </button>

                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" class="rounded-xl border border-zinc-200 bg-white text-zinc-600 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200" />
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
                                <td colspan="9" class="px-5 py-16 text-center text-zinc-500 dark:text-zinc-400">No hay ventas para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 border-t border-zinc-200 px-4 py-4 md:flex-row md:items-center md:justify-between dark:border-white/10">
                <div class="text-sm text-slate-600 dark:text-zinc-400">
                    Mostrando {{ $sales->firstItem() }} a {{ $sales->lastItem() }} de {{ $sales->total() }} resultados
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="min-w-[10rem]">
                        <flux:select wire:model.live="perPage" class="h-11 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                            <option value="10">10 por página</option>
                            <option value="25">25 por página</option>
                            <option value="50">50 por página</option>
                        </flux:select>
                    </div>

                    <div>
                        {{ $sales->links('vendor.pagination.livewire-table-clean') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isDrawerOpen)
        <div class="fixed inset-0 z-[70] bg-zinc-950/30" wire:click="closeDrawer"></div>
        <aside class="fixed inset-0 z-[71] flex h-[100dvh] w-full max-w-none flex-col bg-white text-zinc-900 shadow-[0_0_40px_rgba(15,23,42,0.2)] dark:bg-[#0b1118] dark:text-white sm:inset-y-0 sm:right-0 sm:left-auto sm:max-w-[420px] sm:rounded-none">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 dark:border-white/10 sm:px-5">
                <div class="flex items-center gap-3">
                    @if ($drawerStep !== 'cart' && $drawerStep !== 'success')
                        @if (in_array($drawerStep, ['service-professional', 'product-config'], true))
                            <button type="button" wire:click="backToItemPicker" class="text-zinc-500 dark:text-zinc-300">
                                <flux:icon.chevron-left class="size-6" />
                            </button>
                        @else
                            <button type="button" wire:click="backToCart" class="text-zinc-500 dark:text-zinc-300">
                                <flux:icon.chevron-left class="size-6" />
                            </button>
                        @endif
                    @elseif ($drawerStep === 'success')
                        <button type="button" wire:click="openCreateSale" class="text-zinc-500 dark:text-zinc-300">
                            <flux:icon.chevron-left class="size-6" />
                        </button>
                    @endif
                    <flux:heading size="lg">
                        @switch($drawerStep)
                            @case('client-search') Asociar cliente a la venta @break
                            @case('client-create') Agrega un nuevo cliente @break
                            @case('item-picker') Agrega lo que desees @break
                            @case('service-professional') Selecciona un profesional @break
                            @case('product-config') {{ $this->productConfigurationProduct?->name ?? 'Configura el producto' }} @break
                            @case('payment') Método de pago @break
                            @case('success') {{ $saleSummaryMode === 'detail' ? 'Detalle de venta' : 'Venta completada' }} @break
                            @default Nueva venta
                        @endswitch
                    </flux:heading>
                </div>

                @if ($drawerStep !== 'success')
                    <button type="button" wire:click="closeDrawer" class="text-zinc-500 dark:text-zinc-300">
                        <flux:icon.x-mark class="size-5 sm:size-6" />
                    </button>
                @endif
            </div>

            <div
                class="relative min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-4 transition-[opacity,transform,filter] duration-200 ease-out motion-reduce:transition-none"
                wire:loading.class="opacity-60 blur-[1px] scale-[0.995]"
                wire:target="openItemPicker,backToItemPicker,openServiceProfessionalPicker,openProductConfiguration,selectServiceProfessional,selectClient,openClientSearch,openClientCreate,backToCart"
            >
                <div
                    wire:loading.delay.shorter
                    wire:target="openItemPicker,backToItemPicker,openServiceProfessionalPicker,openProductConfiguration,selectServiceProfessional,selectClient,openClientSearch,openClientCreate,backToCart"
                    class="absolute inset-0 z-20 flex items-start justify-center bg-white/65 px-4 pt-20 backdrop-blur-[2px] dark:bg-[#0b1118]/80"
                >
                    <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white/95 px-4 py-3 shadow-[0_20px_40px_rgba(15,23,42,0.12)] dark:border-white/10 dark:bg-[#0f1720] dark:shadow-none">
                        <div class="size-5 animate-spin rounded-full border-2 border-zinc-200 border-t-violet-600 dark:border-white/10 dark:border-t-emerald-500"></div>
                        <div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">Preparando vista</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Un momento, estamos cargando tus opciones.</div>
                        </div>
                    </div>
                </div>

                @if ($drawerStep === 'cart')
                    @php
                        $cartSubtotal = collect($saleForm['cart'])->sum(fn ($item): float => ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)));
                        $cartDiscounts = collect($saleForm['cart'])->sum(fn ($item): float => (float) data_get($item, 'meta.discount_amount', 0));
                        $cartTotal = collect($saleForm['cart'])->sum(fn ($item): float => (float) ($item['subtotal'] ?? 0));
                    @endphp

                    <div class="space-y-5">
                        <div class="rounded-[22px] border border-dashed border-zinc-200 bg-white p-2 dark:border-white/10 dark:bg-white">
                            <button
                                type="button"
                                wire:click="openItemPicker"
                                wire:target="openItemPicker"
                                wire:loading.attr="disabled"
                                class="relative flex w-full flex-col items-center justify-center gap-1 rounded-[18px] px-4 py-5 text-emerald-600 transition-transform duration-200 ease-out active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <span wire:loading.remove wire:target="openItemPicker" class="inline-flex items-center gap-2 text-sm font-semibold">
                                    <flux:icon.plus class="size-5" />
                                    Agregar productos o servicios
                                </span>

                                <span class="text-sm text-zinc-500 dark:text-zinc-500">Buscar en catalogo</span>

                                <span wire:loading.inline-flex wire:target="openItemPicker" class="inline-flex items-center gap-2 text-sm font-semibold">
                                    <span class="size-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-600"></span>
                                    Abriendo...
                                </span>
                            </button>
                        </div>

                        @if ($saleForm['client_id'])
                            @php
                                $client = $this->clientsCatalog->firstWhere('id', $saleForm['client_id']);
                            @endphp
                            <button type="button" wire:click="openClientSearch" class="flex w-full items-center justify-between rounded-[22px] border border-zinc-200 bg-white px-4 py-4 text-left shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        <flux:icon.users class="size-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-zinc-900 dark:text-white">{{ $client?->fullName() ?? 'Cliente' }}</div>
                                        <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $client?->phone ?? 'Sin telefono' }}</div>
                                        <div class="mt-2 inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            Cliente frecuente
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    <span>Cambiar cliente</span>
                                    <flux:icon.chevron-right class="size-4" />
                                </div>
                            </button>
                        @endif

                        @if (count($saleForm['cart']) === 0)
                            <div class="flex flex-col items-center justify-center px-4 py-6 text-center">
                                <div class="relative flex size-28 items-center justify-center rounded-full bg-gradient-to-br from-emerald-50 to-zinc-50 dark:from-emerald-50 dark:to-zinc-100">
                                    <span class="absolute left-4 top-6 text-emerald-200 dark:text-emerald-300">+</span>
                                    <span class="absolute right-5 top-5 text-emerald-200 dark:text-emerald-300">+</span>
                                    <span class="absolute left-7 bottom-7 text-emerald-200 dark:text-emerald-300">+</span>
                                    <flux:icon.shopping-cart class="size-10 text-emerald-600" />
                                </div>

                                <div class="mt-5 text-xl font-semibold text-zinc-900 dark:text-white">Tu carrito esta vacio</div>
                                <div class="mt-2 max-w-[15rem] text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                                    Agrega productos o servicios para comenzar la venta.
                                </div>
                            </div>
                        @else
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                        Carrito ({{ count($saleForm['cart']) }} {{ count($saleForm['cart']) === 1 ? 'articulo' : 'articulos' }})
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="openCreateSale"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-rose-500 transition hover:text-rose-600"
                                    >
                                        <flux:icon.trash class="size-4" />
                                        Vaciar carrito
                                    </button>
                                </div>

                                @foreach ($saleForm['cart'] as $item)
                                    @php
                                        $itemQuantity = max(1, (float) ($item['quantity'] ?? 1));
                                        $itemUnitPrice = (float) ($item['unit_price'] ?? 0);
                                        $itemGross = $itemQuantity * $itemUnitPrice;
                                        $itemDiscount = (float) data_get($item, 'meta.discount_amount', 0);
                                        $itemIsService = ($item['item_type'] ?? null) === 'service';
                                    @endphp

                                    <div class="rounded-[22px] border border-zinc-200 bg-white px-4 py-4 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-1 flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $itemIsService ? 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300' }}">
                                                @if ($itemIsService)
                                                    <flux:icon.scissors class="size-5" />
                                                @else
                                                    <flux:icon.archive-box class="size-5" />
                                                @endif
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $item['item_name'] }}</div>
                                                        @if ($item['item_detail'])
                                                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $item['item_detail'] }}</div>
                                                        @endif

                                                        @if (($item['meta']['professional_name'] ?? null))
                                                            <div class="mt-1 text-sm text-violet-600 dark:text-violet-300">
                                                                {{ $itemIsService ? 'Profesional' : 'Vendedor' }}: {{ $item['meta']['professional_name'] }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="shrink-0 text-right">
                                                        <div class="text-2xl font-semibold leading-none text-zinc-900 dark:text-white">S/{{ number_format((float) $item['subtotal'], 2) }}</div>
                                                        @if ($itemDiscount > 0)
                                                            <div class="mt-2 text-sm text-zinc-400 dark:text-zinc-500">Antes S/{{ number_format($itemGross, 2) }}</div>
                                                            <div class="mt-1 text-sm font-medium text-emerald-600 dark:text-emerald-400">Descuento -S/{{ number_format($itemDiscount, 2) }}</div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="mt-4 flex items-center justify-between gap-3">
                                                    <div class="inline-flex items-center overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                                                        <button
                                                            type="button"
                                                            wire:click="decreaseCartItem('{{ $item['key'] }}')"
                                                            @disabled((float) $item['quantity'] <= 1)
                                                            class="inline-flex size-10 items-center justify-center text-zinc-500 transition hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-white/[0.05] disabled:cursor-not-allowed disabled:opacity-40"
                                                        >
                                                            <flux:icon.minus class="size-4" />
                                                        </button>

                                                        <div class="flex h-10 min-w-10 items-center justify-center border-x border-zinc-200 px-4 text-sm font-semibold text-zinc-900 dark:border-white/10 dark:text-white">
                                                            {{ rtrim(rtrim((string) $item['quantity'], '0'), '.') }}
                                                        </div>

                                                        <button
                                                            type="button"
                                                            wire:click="increaseCartItem('{{ $item['key'] }}')"
                                                            class="inline-flex size-10 items-center justify-center text-emerald-600 transition hover:bg-zinc-50 dark:text-emerald-400 dark:hover:bg-white/[0.05]"
                                                        >
                                                            <flux:icon.plus class="size-4" />
                                                        </button>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        wire:click="removeCartItem('{{ $item['key'] }}')"
                                                        class="inline-flex items-center gap-2 text-sm font-medium text-rose-500 transition hover:text-rose-600"
                                                    >
                                                        <flux:icon.trash class="size-4" />
                                                        Eliminar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif ($drawerStep === 'client-search')
                    <div class="space-y-4">
                        <div>
                            <div class="text-base font-semibold text-zinc-900 dark:text-white">Busqueda de cliente</div>
                            <div class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">La busqueda inicia a partir de 3 caracteres</div>
                        </div>

                        <flux:input
                            wire:model.live.debounce.300ms="clientSearch"
                            icon="magnifying-glass"
                            placeholder="Busca por nombre, apellido, DNI, email o telefono"
                            class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                        />

                        <button type="button" wire:click="openClientCreate" class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-emerald-600 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:text-emerald-400 dark:shadow-none">
                            <flux:icon.user-plus class="size-5" />
                            Crear nuevo cliente
                        </button>

                        <div class="overflow-hidden rounded-[22px] border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                            @if (mb_strlen(trim($clientSearch)) >= 3)
                                @forelse ($this->searchedClients as $client)
                                    <button type="button" wire:click="selectClient({{ $client->id }})" class="flex w-full items-start gap-3 border-b border-zinc-200 px-4 py-4 text-left transition last:border-b-0 hover:bg-zinc-50 dark:border-white/10 dark:hover:bg-white/[0.05]">
                                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            {{ strtoupper(substr($client->first_name ?? 'C', 0, 1).substr($client->last_name ?? '', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <span class="block font-semibold text-zinc-900 dark:text-white">{{ $client->fullName() }}</span>
                                            <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ $client->email ?: 'Sin email' }}</span>
                                            <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ $client->phone ?: 'Sin telefono' }}</span>
                                        </div>
                                        <flux:icon.chevron-right class="mt-1 size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
                                    </button>
                                @empty
                                    <div class="px-4 py-8 text-sm text-zinc-500 dark:text-zinc-400">No se encontraron clientes.</div>
                                @endforelse
                            @else
                                <div class="px-4 py-8 text-sm text-zinc-500 dark:text-zinc-400">Ingresa al menos 3 caracteres para buscar.</div>
                            @endif
                        </div>
                    </div>
                @elseif ($drawerStep === 'client-create')
                    <div class="space-y-4">
                        <div class="rounded-[22px] border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="text-base font-semibold text-zinc-900 dark:text-white">Informacion requerida</div>
                            <div class="mt-4 grid gap-4">
                                <flux:input wire:model="clientCreateForm.first_name" label="Nombre" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                                <flux:input wire:model="clientCreateForm.last_name" label="Apellido" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                            </div>
                        </div>

                        <div class="rounded-[22px] border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="text-base font-semibold text-zinc-900 dark:text-white">Informacion adicional</div>
                            <div class="mt-4 grid gap-4">
                                <flux:input wire:model="clientCreateForm.email" label="Email" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                                <flux:input wire:model="clientCreateForm.phone" label="Telefono" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                            </div>
                        </div>
                    </div>
                @elseif ($drawerStep === 'item-picker')
                    <livewire:sales.item-picker :cart-quantities="$this->itemPickerCartQuantities()" :key="'sales-item-picker'" />
                @elseif ($drawerStep === 'service-professional')
                    @php
                        $selectedService = $this->serviceProfessionalPickerService;
                        $servicePrice = (float) $serviceConfigurationPrice;
                        $serviceQuantity = max(1, (int) $serviceConfigurationQuantity);
                        $serviceDiscountValue = (float) $serviceConfigurationDiscountValue;
                        $serviceGrossSubtotal = round($servicePrice * $serviceQuantity, 2);
                        $serviceDiscountAmount = $serviceConfigurationDiscountType === 'amount'
                            ? min($serviceGrossSubtotal, $serviceDiscountValue)
                            : round($serviceGrossSubtotal * min(100, $serviceDiscountValue) / 100, 2);
                        $serviceNetSubtotal = round(max(0, $serviceGrossSubtotal - $serviceDiscountAmount), 2);
                    @endphp

                    <div class="space-y-4">
                        @if ($selectedService)
                            <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Servicio seleccionado</div>
                                <div class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedService->name }}</div>
                                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">S/{{ number_format((float) $selectedService->price, 0) }} · {{ $selectedService->duration_minutes }} min</div>
                            </div>
                        @endif

                        <div class="rounded-[24px] border border-zinc-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                        Profesional <span class="text-rose-500">*</span>
                                    </div>

                                    <flux:select
                                        wire:model.live="serviceProfessionalPickerProfessionalId"
                                        class="{{ $errors->has('serviceProfessionalPickerProfessionalId') ? 'border-rose-500 ring-2 ring-rose-100' : '' }} dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                    >
                                        <option value="">Selecciona un profesional</option>

                                        @forelse ($this->serviceProfessionalPickerProfessionals as $professional)
                                            <option value="{{ $professional->id }}">{{ $professional->public_name }}</option>
                                        @empty
                                            <option value="">No hay profesionales vinculados</option>
                                        @endforelse
                                    </flux:select>

                                    @error('serviceProfessionalPickerProfessionalId')
                                        <p class="text-sm font-medium text-rose-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <div class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Precio</div>
                                    <flux:input wire:model.live="serviceConfigurationPrice" type="number" min="0" step="0.01" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                                </div>

                                <div>
                                    <div class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Descuento</div>
                                    <div class="grid grid-cols-[minmax(0,1fr)_5rem] gap-2">
                                        <flux:input wire:model.live="serviceConfigurationDiscountValue" type="number" min="0" step="0.01" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                                        <flux:select wire:model.live="serviceConfigurationDiscountType" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                                            <option value="percent">%</option>
                                            <option value="amount">S/</option>
                                        </flux:select>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-300">
                                    <div class="flex items-center justify-between">
                                        <span>Subtotal</span>
                                        <span>S/{{ number_format($serviceGrossSubtotal, 2) }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between">
                                        <span>Descuento</span>
                                        <span>S/{{ number_format($serviceDiscountAmount, 2) }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-white/10 dark:text-white">
                                        <span>Total</span>
                                        <span>S/{{ number_format($serviceNetSubtotal, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($drawerStep === 'product-config')
                    @php
                        $selectedProduct = $this->productConfigurationProduct;
                    @endphp

                    <div class="space-y-4">
                        @if ($selectedProduct)
                            <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Producto seleccionado</div>
                                <div class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedProduct->name }}</div>
                                <div class="mt-3 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ (float) $selectedProduct->current_stock > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }}">
                                    Stock disponible:
                                    <span class="ml-1">{{ number_format((float) $selectedProduct->current_stock, 0, '.', ',') }}</span>
                                </div>
                                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    S/{{ number_format((float) $selectedProduct->public_sale_price, 2) }} · {{ $selectedProduct->category?->name }} · {{ $selectedProduct->brand?->name }} · {{ $selectedProduct->presentation?->name }}
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center justify-center gap-3">
                            <button
                                type="button"
                                wire:click="decreaseProductConfigurationQuantity"
                                class="inline-flex size-11 items-center justify-center rounded-xl border border-zinc-200 bg-white text-rose-500 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none"
                            >
                                <flux:icon.trash class="size-5" />
                            </button>

                            <div class="min-w-8 text-center text-lg font-medium text-zinc-800 dark:text-white">
                                {{ $productConfigurationQuantity }}
                            </div>

                            <button
                                type="button"
                                wire:click="increaseProductConfigurationQuantity"
                                @disabled($selectedProduct === null || (float) $selectedProduct->current_stock <= 0 || $productConfigurationQuantity >= (int) floor((float) $selectedProduct->current_stock))
                                class="inline-flex size-11 items-center justify-center rounded-xl border border-zinc-200 bg-white text-violet-600 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none"
                            >
                                <flux:icon.plus class="size-5" />
                            </button>
                        </div>

                        <div class="rounded-[24px] border border-zinc-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="space-y-4">
                                <div>
                                    <div class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Vendedor</div>
                                    <flux:select wire:model.live="productConfigurationProfessionalId" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                                        <option value="">Selecciona un vendedor</option>
                                        @forelse ($this->professionalsCatalog as $professional)
                                            <option value="{{ $professional->id }}">{{ $professional->public_name }}</option>
                                        @empty
                                            <option value="">No hay vendedores disponibles</option>
                                        @endforelse
                                    </flux:select>
                                    @error('productConfigurationProfessionalId')
                                        <p class="mt-2 text-sm font-medium text-rose-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <div class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Precio</div>
                                    <flux:input wire:model.live="productConfigurationPrice" type="number" min="0" step="0.01" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                                </div>

                                <div>
                                    <div class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Descuento</div>
                                    <div class="grid grid-cols-[minmax(0,1fr)_5rem] gap-2">
                                        <flux:input wire:model.live="productConfigurationDiscountValue" type="number" min="0" step="0.01" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white" />
                                        <flux:select wire:model.live="productConfigurationDiscountType" class="dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                                            <option value="percent">%</option>
                                            <option value="amount">S/</option>
                                        </flux:select>
                                    </div>
                                </div>

                                @php
                                    $productPrice = (float) $productConfigurationPrice;
                                    $productQuantity = max(1, (int) $productConfigurationQuantity);
                                    $availableStock = (float) ($selectedProduct?->current_stock ?? 0);
                                    $discountValue = (float) $productConfigurationDiscountValue;
                                    $grossSubtotal = round($productPrice * $productQuantity, 2);
                                    $discountAmount = $productConfigurationDiscountType === 'amount'
                                        ? min($grossSubtotal, $discountValue)
                                        : round($grossSubtotal * min(100, $discountValue) / 100, 2);
                                    $netSubtotal = round(max(0, $grossSubtotal - $discountAmount), 2);
                                @endphp

                                

                                <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-300">
                                    <div class="flex items-center justify-between">
                                        <span>Subtotal</span>
                                        <span>S/{{ number_format($grossSubtotal, 2) }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between">
                                        <span>Descuento</span>
                                        <span>S/{{ number_format($discountAmount, 2) }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-white/10 dark:text-white">
                                        <span>Total</span>
                                        <span>S/{{ number_format($netSubtotal, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($drawerStep === 'payment')
                    <div class="space-y-6">
                        <div>
                            <div class="text-[1.05rem] font-medium leading-8 text-slate-700 dark:text-zinc-300">
                                Como deseas recibir el pago de esta venta?
                            </div>
                        </div>

                        @php
                            $paymentCardMeta = [
                                'bank_transfer' => [
                                    'title' => 'Transferencia Bancaria',
                                    'description' => 'Transferencia desde cuentas bancarias.',
                                    'icon' => 'arrows-right-left',
                                    'icon_class' => 'text-violet-600 dark:text-violet-300',
                                ],
                                'debit_card' => [
                                    'title' => 'Tarjeta de Debito',
                                    'description' => 'Pago con tarjeta de debito.',
                                    'icon' => 'credit-card',
                                    'icon_class' => 'text-violet-600 dark:text-violet-300',
                                ],
                                'credit_card' => [
                                    'title' => 'Tarjeta de Credito',
                                    'description' => 'Pago con tarjeta de credito.',
                                    'icon' => 'credit-card',
                                    'icon_class' => 'text-violet-600 dark:text-violet-300',
                                ],
                                'cash' => [
                                    'title' => 'Efectivo',
                                    'description' => 'Pago en efectivo.',
                                    'icon' => 'banknotes',
                                    'icon_class' => 'text-violet-600 dark:text-violet-300',
                                ],
                                'yape' => [
                                    'title' => 'Yape',
                                    'description' => 'Pago a traves de Yape.',
                                    'icon' => null,
                                    'icon_class' => '',
                                ],
                                'plin' => [
                                    'title' => 'Plin',
                                    'description' => 'Pago a traves de Plin.',
                                    'icon' => null,
                                    'icon_class' => '',
                                ],
                            ];
                        @endphp

                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($this->paymentMethods as $key => $label)
                                @php
                                    $meta = $paymentCardMeta[$key] ?? [
                                        'title' => $label,
                                        'description' => 'Selecciona este metodo de pago.',
                                        'icon' => 'credit-card',
                                        'icon_class' => 'text-violet-600 dark:text-violet-300',
                                    ];
                                    $isSelected = $saleForm['selected_payment_method'] === $key;
                                @endphp

                                <button
                                    type="button"
                                    wire:click="completeSale('{{ $key }}')"
                                    class="{{ $isSelected
                                        ? 'border-violet-400 bg-violet-50/60 ring-2 ring-violet-100 dark:border-violet-400/50 dark:bg-violet-500/10 dark:ring-violet-500/10'
                                        : 'border-zinc-200 bg-white hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/[0.05]' }} relative flex min-h-[13rem] flex-col items-center justify-center rounded-[28px] border px-4 py-6 text-center transition"
                                >
                                    @if ($isSelected)
                                        <span class="absolute right-4 top-4 inline-flex size-9 items-center justify-center rounded-full bg-violet-600 text-white shadow-sm dark:bg-violet-500">
                                            <flux:icon.check class="size-5" />
                                        </span>
                                    @endif

                                    @if ($key === 'yape')
                                        <div class="mb-5 flex h-14 items-center justify-center">
                                            <div class="text-[2.1rem] font-black italic leading-none text-violet-600 dark:text-violet-300">
                                                yape
                                            </div>
                                        </div>
                                    @elseif ($key === 'plin')
                                        <div class="mb-5 flex h-14 items-center justify-center">
                                            <div class="flex size-14 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400 to-emerald-400 text-2xl font-bold text-white shadow-sm">
                                                plin
                                            </div>
                                        </div>
                                    @else
                                        <div class="mb-5 flex h-14 items-center justify-center">
                                            <flux:icon :name="$meta['icon']" class="size-12 {{ $meta['icon_class'] }}" />
                                        </div>
                                    @endif

                                    <div class="text-[1.05rem] font-semibold leading-snug text-slate-900 dark:text-white">
                                        {{ $meta['title'] }}
                                    </div>
                                    <div class="mt-3 text-sm leading-6 text-slate-500 dark:text-zinc-400">
                                        {{ $meta['description'] }}
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        <div class="rounded-[24px] border border-violet-200 bg-violet-50 px-4 py-4 text-sm text-violet-700 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-200">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <flux:icon.information-circle class="size-6" />
                                </div>
                                <div class="leading-6">
                                    Al seleccionar un metodo de pago, la venta se registrara automaticamente.
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($drawerStep === 'success')
                    @if ($selectedSale)
                        <div class="space-y-4 sm:space-y-6 pb-6">
                            <div class="rounded-[28px] border border-zinc-200 bg-white shadow-[0_20px_50px_rgba(15,23,42,0.06)] dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
                                <div class="border-b border-zinc-200/80 px-4 py-4 text-center dark:border-white/10 sm:px-5">
                                    <div class="mx-auto flex size-12 sm:size-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-[0_10px_25px_rgba(16,185,129,0.3)] dark:shadow-none">
                                        <flux:icon.check class="size-6 sm:size-8" />
                                    </div>

                                    <div class="mt-4 text-xl font-semibold text-zinc-800 dark:text-white sm:mt-5 sm:text-2xl">
                                        El pago se realizó con éxito
                                    </div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 sm:text-sm">
                                        Venta #{{ $selectedSale->ticket_number }}
                                    </div>
                                </div>

                                <div class="space-y-4 px-4 py-4 sm:space-y-5 sm:px-5 sm:py-5">
                                    <div>
                                        <div class="text-sm text-zinc-400 dark:text-zinc-500">Cliente</div>
                                        <div class="mt-2 text-base font-semibold text-zinc-900 dark:text-white">
                                            {{ $selectedSale->client?->fullName() ?? 'Consumidor final' }}
                                        </div>

                                        @if ($selectedSale->client?->email)
                                            <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                <flux:icon.envelope class="size-4" />
                                                <span>{{ $selectedSale->client?->email }}</span>
                                            </div>
                                        @endif

                                        @if ($selectedSale->client?->phone)
                                            <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                <flux:icon.phone class="size-4" />
                                                <span>{{ $selectedSale->client?->phone }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="border-t border-zinc-200 pt-5 dark:border-white/10">
                                        <div class="text-sm text-zinc-400 dark:text-zinc-500">Medio de pago</div>
                                        @forelse ($selectedSale->payments as $payment)
                                            <div class="mt-3 flex items-start justify-between gap-3">
                                                <div>
                                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $this->paymentMethods[$payment->method] ?? $payment->method }}</div>
                                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">#{{ $selectedSale->ticket_number }} · {{ $selectedSale->sold_at?->format('d-m-Y') }}</div>
                                                </div>
                                                <div class="font-medium text-zinc-900 dark:text-white">S/{{ number_format((float) $payment->amount, 0) }}</div>
                                            </div>
                                        @empty
                                            <div class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No hay pagos registrados para esta venta.</div>
                                        @endforelse
                                    </div>

                                    <div class="border-t border-zinc-200 pt-5 dark:border-white/10">
                                        <div class="text-sm text-zinc-400 dark:text-zinc-500">Detalle del pago</div>
                                        <div class="mt-3 space-y-2 text-zinc-700 dark:text-zinc-300">
                                            <div class="flex items-center justify-between"><span>Subtotal</span><span>S/{{ number_format((float) $selectedSale->subtotal, 0) }}</span></div>
                                            <div class="flex items-center justify-between"><span>Vuelto</span><span>S/{{ number_format((float) $selectedSale->change_total, 0) }}</span></div>
                                            <div class="flex items-center justify-between"><span>Descuentos</span><span>S/{{ number_format((float) $selectedSale->discount_total, 0) }}</span></div>
                                            <div class="flex items-center justify-between font-semibold text-zinc-900 dark:text-white"><span>Total:</span><span>S/{{ number_format((float) $selectedSale->total, 0) }}</span></div>
                                        </div>
                                    </div>

                                    <div class="border-t border-zinc-200 pt-5 dark:border-white/10">
                                        <div class="space-y-3">
                                            @if ($emailReceiptUrl)
                                                <a href="{{ $emailReceiptUrl }}" class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-violet-600 shadow-sm transition hover:bg-violet-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-violet-300 dark:hover:bg-white/[0.05] dark:shadow-none">
                                                    Enviar comprobante
                                                </a>
                                            @else
                                                <button type="button" disabled class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-zinc-400 shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-500 dark:shadow-none">
                                                    Enviar comprobante
                                                </button>
                                            @endif

                                            <a href="{{ $receiptUrl }}" target="_blank" class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-violet-600 shadow-sm transition hover:bg-violet-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-violet-300 dark:hover:bg-white/[0.05] dark:shadow-none">
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

            <div class="border-t border-zinc-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-[#0b1118] sm:px-5">
                @if ($drawerStep === 'cart')
                    @php
                        $drawerSubtotal = collect($saleForm['cart'])->sum(fn ($item): float => ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)));
                        $drawerDiscounts = collect($saleForm['cart'])->sum(fn ($item): float => (float) data_get($item, 'meta.discount_amount', 0));
                        $drawerTotal = collect($saleForm['cart'])->sum(fn ($item): float => (float) ($item['subtotal'] ?? 0));
                    @endphp

                    <div class="space-y-4">
                        <div class="border-t border-zinc-100 pt-4">
                            <div class="flex items-center justify-between">
                                <div class="text-base font-semibold text-zinc-900 dark:text-white">Resumen de venta</div>
                                @if (count($saleForm['cart']) > 0)
                                    <div class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        {{ count($saleForm['cart']) }} {{ count($saleForm['cart']) === 1 ? 'articulo' : 'articulos' }}
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                                <div class="flex items-center justify-between">
                                    <span>Subtotal</span>
                                    <span>S/{{ number_format($drawerSubtotal, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span>Descuentos</span>
                                    <div class="flex items-center gap-3">
                                        <button type="button" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">+ Agregar</button>
                                        <span class="dark:text-zinc-400">- S/{{ number_format($drawerDiscounts, 2) }}</span>
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-emerald-50/60 px-4 py-3 dark:bg-emerald-500/10">
                                    <div class="flex items-center justify-between text-lg font-semibold text-emerald-700 dark:text-emerald-300">
                                        <span>Total</span>
                                        <span>S/{{ number_format($drawerTotal, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" wire:click="proceedToPayment" class="flex h-11 w-full items-center justify-between rounded-xl bg-emerald-600 px-4 font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 sm:h-12">
                            <span class="mx-auto">Continuar</span>
                            @if (count($saleForm['cart']) > 0)
                                <span class="text-sm font-semibold">S/{{ number_format($drawerTotal, 2) }}</span>
                            @endif
                        </button>
                    </div>
                @elseif ($drawerStep === 'item-picker')
                    <button type="button" wire:click="backToCart" class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white shadow-sm transition hover:bg-emerald-700 sm:h-12">
                        Ir al carro ({{ count($saleForm['cart']) }})
                    </button>
                @elseif ($drawerStep === 'service-professional')
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" wire:click="backToItemPicker" class="flex h-11 w-full items-center justify-center rounded-xl border border-zinc-200 bg-white font-semibold text-zinc-700 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 sm:h-12">
                            Volver
                        </button>

                        <button type="button" wire:click="saveServiceConfiguration" class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white disabled:opacity-50 sm:h-12">
                            Agregar al carro
                        </button>
                    </div>
                @elseif ($drawerStep === 'product-config')
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" wire:click="backToItemPicker" class="flex h-11 w-full items-center justify-center rounded-xl border border-zinc-200 bg-white font-semibold text-zinc-700 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 sm:h-12">
                            Volver
                        </button>

                        <button type="button" wire:click="saveProductConfiguration" @disabled($this->productConfigurationAvailableStock <= 0) class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50 sm:h-12">
                            Agregar al carro
                        </button>
                    </div>
                @elseif ($drawerStep === 'client-create')
                    <button type="button" wire:click="saveInlineClient" class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white sm:h-12">
                        Guardar cliente
                    </button>
                @elseif ($drawerStep === 'payment')
                    <div class="flex h-11 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-600 dark:bg-white/[0.05] dark:text-zinc-300 sm:h-12">
                        Selecciona un método de pago para registrar la venta
                    </div>
                @elseif ($drawerStep === 'success')
                    <button type="button" wire:click="closeDrawer" class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white sm:h-12">
                        Cerrar
                    </button>
                @else
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-xl font-semibold text-zinc-800 dark:text-white sm:text-2xl">Total:</div>
                        <div class="text-xl font-semibold text-zinc-800 dark:text-white sm:text-2xl">S/{{ number_format($drawerStep === 'success' && $selectedSale ? (float) $selectedSale->total : collect($saleForm['cart'])->sum(fn ($item) => (float) $item['subtotal']), 0) }}</div>
                    </div>
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
