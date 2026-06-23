<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="grid gap-6 xl:grid-cols-[16rem_minmax(0,1fr)_24rem]">
        <aside class="space-y-6">
            <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-4 p-4">
                    <div>
                        <flux:badge color="sky" size="sm" inset="left">Commissions</flux:badge>
                        <flux:heading size="lg" class="mt-3">Commission Hub</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Motor enterprise para reglas, liquidaciones, pagos y auditoría.
                        </flux:text>
                    </div>

                    <div class="space-y-2">
                        @foreach ([
                            ['dashboard', 'Dashboard', 'home'],
                            ['commissions', 'Commissions', 'banknotes'],
                            ['rules', 'Rules', 'adjustments-horizontal'],
                            ['settlements', 'Settlements', 'queue-list'],
                            ['reports', 'Reports', 'chart-bar'],
                            ['audit', 'Audit Logs', 'clipboard-document-list'],
                        ] as [$section, $label, $icon])
                            <button
                                type="button"
                                wire:click="$set('section', '{{ $section }}')"
                                @class([
                                    'flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left transition',
                                    'border-sky-400 bg-sky-500/10 text-sky-300' => $this->section === $section,
                                    'border-zinc-200/80 bg-white text-zinc-600 hover:border-sky-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300' => $this->section !== $section,
                                ])
                            >
                                <flux:icon name="{{ $icon }}" class="size-5" />
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </flux:card>

            <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-3 p-4">
                    <flux:heading size="sm">Top Performers</flux:heading>
                    <div class="space-y-2">
                        @forelse ($this->topPerformers as $performer)
                            <div class="rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $performer['name'] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $performer['completed'] }} completed</div>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-sky-400">S/ {{ number_format($performer['commissions'], 2) }}</div>
                                </div>
                            </div>
                        @empty
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No performance data yet.</flux:text>
                        @endforelse
                    </div>
                </div>
            </flux:card>
        </aside>

        <main class="space-y-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <flux:badge color="sky" size="sm" inset="left">Enterprise Commission Engine</flux:badge>
                    <flux:heading size="xl" class="mt-3">Commission Management</flux:heading>
                    <flux:text class="mt-2 max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
                        Track revenue, approvals, settlements, audit history and professional performance across branches with rule-driven calculations.
                    </flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button variant="ghost" icon="arrow-path" wire:click="$refresh">Refresh</flux:button>
                    <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportReport('csv')">CSV</flux:button>
                    <flux:button variant="ghost" icon="table-cells" wire:click="exportReport('excel')">Excel</flux:button>
                    <flux:button variant="ghost" icon="document-text" wire:click="exportReport('pdf')">PDF</flux:button>
                    <flux:button variant="ghost" icon="plus" wire:click="openRuleModal">Create Rule</flux:button>
                    <flux:button variant="primary" icon="queue-list" wire:click="openSettlementModal">Generate Settlement</flux:button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Total Commissions</flux:text>
                    <flux:heading size="xl" class="mt-2">S/ {{ number_format($this->dashboardMetrics['total_commissions'], 2) }}</flux:heading>
                </flux:card>
                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Pending</flux:text>
                    <flux:heading size="xl" class="mt-2">S/ {{ number_format($this->dashboardMetrics['pending_commissions'], 2) }}</flux:heading>
                </flux:card>
                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Approved</flux:text>
                    <flux:heading size="xl" class="mt-2">S/ {{ number_format($this->dashboardMetrics['approved_commissions'], 2) }}</flux:heading>
                </flux:card>
                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Paid</flux:text>
                    <flux:heading size="xl" class="mt-2">S/ {{ number_format($this->dashboardMetrics['paid_commissions'], 2) }}</flux:heading>
                </flux:card>
                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Revenue</flux:text>
                    <flux:heading size="xl" class="mt-2">S/ {{ number_format($this->dashboardMetrics['revenue_generated'], 2) }}</flux:heading>
                </flux:card>
            </div>

            <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-4 border-b border-zinc-200/80 px-5 py-4 dark:border-zinc-700 xl:grid xl:grid-cols-[minmax(0,1fr)_12rem_12rem_12rem_12rem] xl:items-center">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" clearable placeholder="Search commissions, professionals or branches" />
                    <flux:select wire:model.live="branchFilter">
                        <option value="">All branches</option>
                        @foreach ($this->branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="professionalFilter">
                        <option value="">All professionals</option>
                        @foreach ($this->professionals as $professional)
                            <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All statuses</option>
                        @foreach ([\App\Services\Commissions\CommissionStatusCatalog::GENERATED, \App\Services\Commissions\CommissionStatusCatalog::PENDING_REVIEW, \App\Services\Commissions\CommissionStatusCatalog::APPROVED, \App\Services\Commissions\CommissionStatusCatalog::PAID, \App\Services\Commissions\CommissionStatusCatalog::REJECTED, \App\Services\Commissions\CommissionStatusCatalog::CANCELLED] as $status)
                            <option value="{{ $status }}">{{ \Illuminate\Support\Str::headline($status) }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="perPage">
                        <option value="10">10 / page</option>
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                    </flux:select>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <div class="flex flex-wrap items-center gap-2">
                        <span>From</span>
                        <flux:input wire:model.live="dateFrom" type="date" class="w-auto" />
                        <span>to</span>
                        <flux:input wire:model.live="dateTo" type="date" class="w-auto" />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="clearFilters">Reset</flux:button>
                    </div>
                </div>

                @if ($this->section === 'dashboard' || $this->section === 'commissions')
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Professional</flux:table.column>
                                <flux:table.column>Source</flux:table.column>
                                <flux:table.column>Revenue</flux:table.column>
                                <flux:table.column>Commission</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                                <flux:table.column class="text-right">Options</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse ($this->commissions as $commission)
                                    <flux:table.row :key="$commission->id">
                                        <flux:table.cell>
                                            <button type="button" class="text-left" wire:click="$set('selectedCommissionId', {{ $commission->id }})">
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $commission->professional?->fullName() ?? 'Unknown' }}</div>
                                                <div class="text-xs text-zinc-500">{{ $commission->branch?->name ?? 'No branch' }}</div>
                                            </button>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="text-sm">{{ \Illuminate\Support\Str::headline($commission->source_type) }}</div>
                                            <div class="text-xs text-zinc-500">{{ $commission->source_reference }}</div>
                                        </flux:table.cell>
                                        <flux:table.cell>S/ {{ number_format((float) $commission->revenue_amount, 2) }}</flux:table.cell>
                                        <flux:table.cell>S/ {{ number_format((float) $commission->commission_amount, 2) }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge :color="$commission->status === 'approved' ? 'emerald' : ($commission->status === 'paid' ? 'sky' : 'amber')">
                                                {{ \Illuminate\Support\Str::headline($commission->status) }}
                                            </flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" icon="eye" wire:click="$set('selectedCommissionId', {{ $commission->id }})">Detail</flux:button>
                                                <flux:button size="sm" variant="ghost" icon="check-circle" wire:click="approveSelectedCommission">Approve</flux:button>
                                                <flux:button size="sm" variant="ghost" icon="x-circle" wire:click="rejectSelectedCommission">Reject</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row>
                                        <flux:table.cell colspan="6">
                                            <div class="py-12 text-center">
                                                <flux:heading size="lg">No commissions found</flux:heading>
                                                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Adjust filters or generate commissions from agenda and sales events.</flux:text>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    </div>

                    <div class="border-t border-zinc-200/80 px-5 py-4 dark:border-zinc-700">
                        <flux:pagination :paginator="$this->commissions" />
                    </div>
                @elseif ($this->section === 'rules')
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Name</flux:table.column>
                                <flux:table.column>Scope</flux:table.column>
                                <flux:table.column>Type</flux:table.column>
                                <flux:table.column>Priority</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                                <flux:table.column class="text-right">Options</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse ($this->rules as $rule)
                                    <flux:table.row :key="$rule->id">
                                        <flux:table.cell>
                                            <button type="button" class="text-left" wire:click="$set('selectedRuleId', {{ $rule->id }})">
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $rule->name }}</div>
                                                <div class="text-xs text-zinc-500">{{ $rule->slug }}</div>
                                            </button>
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $rule->source_type ?? 'All' }}</flux:table.cell>
                                        <flux:table.cell>{{ $rule->type?->name ?? 'N/A' }}</flux:table.cell>
                                        <flux:table.cell>{{ $rule->priority }}</flux:table.cell>
                                        <flux:table.cell><flux:badge :color="$rule->is_active ? 'emerald' : 'zinc'">{{ $rule->is_active ? 'Active' : 'Inactive' }}</flux:badge></flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditRuleModal({{ $rule->id }})">Edit</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row>
                                        <flux:table.cell colspan="6">
                                            <div class="py-12 text-center">
                                                <flux:heading size="lg">No rules defined</flux:heading>
                                                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Create percentage, fixed or tiered rules with branch and source priority.</flux:text>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @elseif ($this->section === 'settlements')
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Settlement</flux:table.column>
                                <flux:table.column>Period</flux:table.column>
                                <flux:table.column>Total</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                                <flux:table.column class="text-right">Options</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse ($this->settlements as $settlement)
                                    <flux:table.row :key="$settlement->id">
                                        <flux:table.cell>
                                            <button type="button" class="text-left" wire:click="$set('selectedSettlementId', {{ $settlement->id }})">
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $settlement->settlement_number }}</div>
                                                <div class="text-xs text-zinc-500">{{ $settlement->branch?->name ?? 'All branches' }}</div>
                                            </button>
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $settlement->starts_at?->format('d M Y') }} - {{ $settlement->ends_at?->format('d M Y') }}</flux:table.cell>
                                        <flux:table.cell>S/ {{ number_format((float) $settlement->total_commissions, 2) }}</flux:table.cell>
                                        <flux:table.cell><flux:badge :color="$settlement->status === 'approved' ? 'emerald' : ($settlement->status === 'paid' ? 'sky' : 'amber')">{{ \Illuminate\Support\Str::headline($settlement->status) }}</flux:badge></flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" icon="check-circle" wire:click="$set('selectedSettlementId', {{ $settlement->id }})">Open</flux:button>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row>
                                        <flux:table.cell colspan="5">
                                            <div class="py-12 text-center">
                                                <flux:heading size="lg">No settlements yet</flux:heading>
                                                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Generate daily, weekly or monthly batches from approved commissions.</flux:text>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @elseif ($this->section === 'reports')
                    <div class="grid gap-4 xl:grid-cols-2">
                        <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="space-y-4 p-4">
                                <flux:heading size="sm">Monthly Commission Trends</flux:heading>
                                <div class="space-y-3">
                                    @foreach ($this->topPerformers as $performer)
                                        <div>
                                            <div class="mb-1 flex items-center justify-between text-xs text-zinc-500">
                                                <span>{{ $performer['name'] }}</span>
                                                <span>S/ {{ number_format($performer['commissions'], 2) }}</span>
                                            </div>
                                            <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="h-full rounded-full bg-sky-400" style="width: {{ min(100, max(10, $performer['commissions'] > 0 ? ($performer['commissions'] / max(1, $this->dashboardMetrics['revenue_generated'])) * 100 : 10)) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </flux:card>
                        <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="space-y-4 p-4">
                                <flux:heading size="sm">Best Selling Services</flux:heading>
                                <div class="space-y-3">
                                    @forelse ($this->bestSellingServices as $service)
                                        <div class="flex items-center justify-between rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                                            <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $service['label'] }}</span>
                                            <span class="text-sm font-semibold text-emerald-400">S/ {{ number_format($service['total'], 2) }}</span>
                                        </div>
                                    @empty
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No service revenue yet.</flux:text>
                                    @endforelse
                                </div>
                            </div>
                        </flux:card>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Action</flux:table.column>
                                <flux:table.column>User</flux:table.column>
                                <flux:table.column>Timestamp</flux:table.column>
                                <flux:table.column>IP</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse ($this->auditLogs as $log)
                                    <flux:table.row :key="$log->id">
                                        <flux:table.cell>{{ $log->action }}</flux:table.cell>
                                        <flux:table.cell>{{ $log->user?->fullName() ?? 'System' }}</flux:table.cell>
                                        <flux:table.cell>{{ $log->created_at?->diffForHumans() }}</flux:table.cell>
                                        <flux:table.cell>{{ $log->ip_address ?? '—' }}</flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row>
                                        <flux:table.cell colspan="4">
                                            <div class="py-12 text-center">
                                                <flux:heading size="lg">No audit entries yet</flux:heading>
                                                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Every approval, reversal, settlement and payment is tracked here.</flux:text>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endif
            </flux:card>
        </main>

        <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
            @if ($this->selectedCommission)
                <flux:card class="border border-zinc-200/80 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <flux:badge :color="$this->selectedCommission->status === 'approved' ? 'emerald' : ($this->selectedCommission->status === 'paid' ? 'sky' : 'amber')">
                                    {{ \Illuminate\Support\Str::headline($this->selectedCommission->status) }}
                                </flux:badge>
                                <flux:heading size="lg" class="mt-2">{{ $this->selectedCommission->professional?->fullName() ?? 'Unknown professional' }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500">{{ $this->selectedCommission->source_type }} · {{ $this->selectedCommission->source_reference }}</flux:text>
                            </div>
                            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('selectedCommissionId', null)" />
                        </div>

                        <div class="grid gap-3 rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-zinc-400">Revenue</div>
                                    <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $this->selectedCommission->revenue_amount, 2) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-zinc-400">Commission</div>
                                    <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $this->selectedCommission->commission_amount, 2) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-zinc-400">Branch</div>
                                    <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $this->selectedCommission->branch?->name ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-zinc-400">Generated</div>
                                    <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $this->selectedCommission->generated_at?->format('d M Y H:i') ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <flux:button variant="primary" icon="check-circle" wire:click="approveSelectedCommission">Approve</flux:button>
                            <flux:button variant="ghost" icon="x-circle" wire:click="rejectSelectedCommission">Reject</flux:button>
                            <flux:button variant="ghost" icon="arrow-path" wire:click="reverseSelectedCommission">Reverse</flux:button>
                        </div>
                    </div>
                </flux:card>
            @elseif ($this->selectedSettlement)
                <flux:card class="border border-zinc-200/80 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <flux:badge color="sky">{{ \Illuminate\Support\Str::headline($this->selectedSettlement->status) }}</flux:badge>
                                <flux:heading size="lg" class="mt-2">{{ $this->selectedSettlement->settlement_number }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500">{{ $this->selectedSettlement->starts_at?->format('d M Y') }} - {{ $this->selectedSettlement->ends_at?->format('d M Y') }}</flux:text>
                            </div>
                            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('selectedSettlementId', null)" />
                        </div>

                        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-zinc-400">Total</div>
                                <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $this->selectedSettlement->total_commissions, 2) }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wide text-zinc-400">Paid</div>
                                <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $this->selectedSettlement->total_paid, 2) }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <flux:button variant="primary" icon="check-circle" wire:click="approveSettlement">Approve</flux:button>
                            <flux:button variant="ghost" icon="banknotes" wire:click="markSettlementPaid">Mark Paid</flux:button>
                        </div>
                    </div>
                </flux:card>
            @elseif ($this->selectedRule)
                <flux:card class="border border-zinc-200/80 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4 p-4">
                        <flux:heading size="lg">{{ $this->selectedRule->name }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $this->selectedRule->notes ?? 'No notes available.' }}</flux:text>
                        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-zinc-400">Source</div>
                                <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $this->selectedRule->source_type ?? 'All' }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wide text-zinc-400">Priority</div>
                                <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $this->selectedRule->priority }}</div>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @else
                <flux:card class="border border-zinc-200/80 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-3 p-4">
                        <flux:heading size="sm">Detail Drawer</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            Select a commission, rule or settlement to inspect professional information, revenue details, commission history, notes and audit timeline.
                        </flux:text>
                    </div>
                </flux:card>
            @endif
        </aside>
    </div>

    <flux:modal name="commission-rule" wire:close="closeRuleModal" wire:cancel="closeRuleModal" class="w-full max-w-5xl">
        <form wire:submit="saveRule" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $ruleForm->commissionRuleId ? 'Edit Rule' : 'New Rule' }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Define rule priority, source scope and formula behavior.</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <flux:input wire:model="ruleForm.name" label="Name *" type="text" />
                <flux:input wire:model="ruleForm.slug" label="Slug" type="text" />
                <flux:select wire:model="ruleForm.commission_type_id" label="Commission Type *">
                    <option value="">Select</option>
                    @foreach (\App\Models\CommissionType::query()->orderBy('name')->get() as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="ruleForm.source_type" label="Source Type *">
                    @foreach ([
                        \App\Services\Commissions\CommissionSourceCatalog::APPOINTMENT,
                        \App\Services\Commissions\CommissionSourceCatalog::SERVICE_SALE,
                        \App\Services\Commissions\CommissionSourceCatalog::PRODUCT_SALE,
                        \App\Services\Commissions\CommissionSourceCatalog::MEMBERSHIP_SALE,
                        \App\Services\Commissions\CommissionSourceCatalog::PACKAGE_SALE,
                        \App\Services\Commissions\CommissionSourceCatalog::SUBSCRIPTION_SALE,
                        \App\Services\Commissions\CommissionSourceCatalog::WALK_IN,
                        \App\Services\Commissions\CommissionSourceCatalog::MANUAL_SALE,
                        \App\Services\Commissions\CommissionSourceCatalog::CROSS_SELL,
                        \App\Services\Commissions\CommissionSourceCatalog::UPSELL,
                    ] as $sourceType)
                        <option value="{{ $sourceType }}">{{ \Illuminate\Support\Str::headline($sourceType) }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="ruleForm.priority" label="Priority" type="number" min="1" max="100" />
                <flux:select wire:model="ruleForm.calculation_mode" label="Calculation Mode *">
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed</option>
                    <option value="profit">Profit Based</option>
                    <option value="quantity">Quantity Based</option>
                </flux:select>
                <flux:input wire:model="ruleForm.percentage" label="Percentage" type="number" min="0" max="100" step="0.01" />
                <flux:input wire:model="ruleForm.fixed_amount" label="Fixed Amount" type="number" min="0" step="0.01" />
                <flux:input wire:model="ruleForm.min_revenue" label="Min Revenue" type="number" min="0" step="0.01" />
                <flux:input wire:model="ruleForm.min_quantity" label="Min Quantity" type="number" min="1" />
                <flux:switch wire:model.live="ruleForm.is_active" label="Active" align="left" />
                <flux:input wire:model="ruleForm.branch_id" label="Branch ID" type="number" min="1" />
                <flux:input wire:model="ruleForm.service_id" label="Service ID" type="number" min="1" />
                <flux:input wire:model="ruleForm.service_category_id" label="Category ID" type="number" min="1" />
                <div class="md:col-span-2 xl:col-span-3">
                    <flux:textarea wire:model="ruleForm.notes" label="Notes" rows="4" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeRuleModal">Cancel</flux:button>
                <flux:button variant="primary" type="submit">Save Rule</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="commission-settlement" wire:close="closeSettlementModal" wire:cancel="closeSettlementModal" class="w-full max-w-4xl">
        <form wire:submit="saveSettlement" class="space-y-6">
            <div>
                <flux:heading size="lg">Generate Settlement</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Create daily, weekly, biweekly, monthly or custom batches.</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="settlementForm.period_type" label="Period Type">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="biweekly">Biweekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="custom">Custom Date Range</option>
                </flux:select>
                <flux:input wire:model="settlementForm.branch_id" label="Branch ID" type="number" min="1" />
                <flux:input wire:model="settlementForm.starts_at" label="Starts At" type="date" />
                <flux:input wire:model="settlementForm.ends_at" label="Ends At" type="date" />
                <div class="md:col-span-2">
                    <flux:textarea wire:model="settlementForm.notes" label="Notes" rows="4" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" type="button" wire:click="closeSettlementModal">Cancel</flux:button>
                <flux:button variant="primary" type="submit">Generate</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
