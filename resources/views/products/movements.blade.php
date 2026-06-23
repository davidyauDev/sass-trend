<x-layouts::app :title="__('Movimiento de stock')">
    <section
        x-data="productMovements(@js($movementConfig))"
        x-cloak
        class="w-full px-4 py-6 sm:px-6 lg:px-8"
    >
        <div
            x-show="toast.visible"
            x-transition
            class="fixed right-4 top-4 z-[70] max-w-sm rounded-2xl border px-4 py-3 shadow-2xl"
            :class="toast.type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                : 'border-rose-200 bg-rose-50 text-rose-900'"
        >
            <div class="text-sm font-semibold" x-text="toast.title"></div>
            <div class="mt-1 text-sm opacity-90" x-text="toast.message"></div>
        </div>

        <div class="relative overflow-hidden rounded-[32px] border border-zinc-200/80 bg-white shadow-[0_20px_70px_rgba(122,80,210,0.08)]">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-violet-500 via-fuchsia-500 to-violet-400"></div>

            <div class="space-y-6 px-5 py-6 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0">
                        <flux:badge color="violet" size="sm" inset="left">Productos</flux:badge>
                        <flux:heading size="xl" class="mt-3">Movimiento de stock</flux:heading>
                        <flux:text class="mt-2 max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
                            Revisa los movimientos históricos y abre el ajuste por producto desde la columna <strong>Ajuste</strong>.
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" action="{{ route('products.movements.index') }}" class="flex flex-wrap items-center gap-2">
                            <flux:input
                                name="from"
                                value="{{ $from }}"
                                type="date"
                                label="Desde"
                                class="w-full min-w-[11rem] rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm xl:w-[12rem]"
                            />
                            <flux:input
                                name="to"
                                value="{{ $to }}"
                                type="date"
                                label="Hasta"
                                class="w-full min-w-[11rem] rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm xl:w-[12rem]"
                            />
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-zinc-700">Local</label>
                                <select
                                    name="branch_id"
                                    class="h-12 w-full min-w-[11rem] rounded-2xl border border-zinc-300 bg-white px-4 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100 xl:w-[14rem]"
                                >
                                    <option value="">Todos</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch['id'] }}" @selected((string) $branchId === (string) $branch['id'])>{{ $branch['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-zinc-700">Producto</label>
                                <select
                                    name="product_id"
                                    class="h-12 w-full min-w-[11rem] rounded-2xl border border-zinc-300 bg-white px-4 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100 xl:w-[16rem]"
                                >
                                    <option value="">Todos</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product['id'] }}" @selected((string) $productId === (string) $product['id'])>{{ $product['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <flux:button type="submit" variant="ghost" icon="magnifying-glass">
                                Buscar
                            </flux:button>
                            @if ($hasFilters)
                                <flux:button variant="ghost" href="{{ route('products.movements.index') }}" icon="x-mark">
                                    Limpiar
                                </flux:button>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[28px] bg-zinc-900 px-6 py-8 text-white shadow-[0_10px_30px_rgba(15,23,42,0.18)]">
                        <div class="text-sm text-zinc-300">Movimientos positivos</div>
                        <div class="mt-5 text-4xl font-semibold">{{ number_format($movementSummary['positive']) }}</div>
                    </div>

                    <div class="rounded-[28px] bg-zinc-900 px-6 py-8 text-white shadow-[0_10px_30px_rgba(15,23,42,0.18)]">
                        <div class="text-sm text-zinc-300">Movimientos negativos</div>
                        <div class="mt-5 text-4xl font-semibold">{{ number_format($movementSummary['negative']) }}</div>
                    </div>

                    <div class="rounded-[28px] border border-zinc-200 bg-white px-6 py-8 shadow-sm">
                        <div class="text-sm text-zinc-500">Movimientos totales</div>
                        <div class="mt-5 text-4xl font-semibold text-zinc-900">{{ number_format($movementSummary['total']) }}</div>
                    </div>

                    <div class="rounded-[28px] border border-zinc-200 bg-white px-6 py-8 shadow-sm">
                        <div class="text-sm text-zinc-500">Productos con historial</div>
                        <div class="mt-5 text-4xl font-semibold text-zinc-900">{{ number_format($movementSummary['products']) }}</div>
                    </div>
                </div>

                <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-200/80 px-5 py-4">
                        <div>
                            <flux:heading size="lg">Movimientos de stock</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500">
                                Haz clic en el ajuste para abrir el modal y editar el stock por local.
                            </flux:text>
                        </div>
                    </div>

                    @if ($movements->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                            <div class="flex size-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <flux:icon name="arrows-right-left" class="size-8" />
                            </div>

                            <div class="space-y-1">
                                <flux:heading size="lg">No hay movimientos aún</flux:heading>
                                <flux:text class="text-sm text-zinc-500">
                                    Los ajustes y ventas aparecerán aquí cuando empieces a operar con el inventario.
                                </flux:text>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Fecha</flux:table.column>
                                    <flux:table.column>Local</flux:table.column>
                                    <flux:table.column>Producto</flux:table.column>
                                    <flux:table.column>Formato/Presentación</flux:table.column>
                                    <flux:table.column>Ajuste</flux:table.column>
                                    <flux:table.column>Causa</flux:table.column>
                                    <flux:table.column>Staff</flux:table.column>
                                    <flux:table.column>Comentario</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach ($movements as $movement)
                                        <flux:table.row :key="$movement->id">
                                            <flux:table.cell>{{ $movement->occurred_at?->format('d-m-Y H:i') }}</flux:table.cell>
                                            <flux:table.cell>{{ $movement->branch?->name ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>{{ $movement->product?->name ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>{{ $movement->product?->presentation?->name ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-2 font-medium text-violet-700 underline decoration-violet-300 underline-offset-4 hover:text-violet-900"
                                                    @click="openMovement({{ $movement->product_id }})"
                                                >
                                                    <span class="inline-flex size-5 items-center justify-center rounded-full {{ (float) $movement->quantity_delta >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                                                        <flux:icon name="{{ (float) $movement->quantity_delta >= 0 ? 'arrow-up' : 'arrow-down' }}" class="size-3" />
                                                    </span>
                                                    De {{ number_format((float) $movement->previous_stock, 2) }} a {{ number_format((float) $movement->new_stock, 2) }}
                                                </button>
                                            </flux:table.cell>
                                            <flux:table.cell>{{ $movement->reason ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>{{ $movement->user?->name ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>{{ $movement->comment ?? 'N/A' }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>

                        <div class="border-t border-zinc-200/80 px-5 py-4">
                            {{ $movements->links() }}
                        </div>
                    @endif
                </flux:card>
            </div>
        </div>

        <div
            x-show="isOpen"
            x-transition.opacity
            class="fixed inset-0 z-[60] flex items-center justify-center bg-zinc-950/50 px-3 py-6 backdrop-blur-[2px]"
            @keydown.escape.window="closeModal()"
            @click.self="closeModal()"
        >
            <div class="relative flex w-full max-w-[1500px] max-h-[92vh] flex-col overflow-hidden rounded-[30px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-6 py-5">
                    <div>
                        <flux:heading size="lg" x-text="title()"></flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Ajusta el stock por local y revisa el historial del producto seleccionado.
                        </flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        @click="closeModal()"
                        aria-label="Cerrar modal"
                    >
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <template x-if="loading">
                        <div class="flex min-h-[18rem] items-center justify-center rounded-[24px] border border-zinc-200/80 bg-zinc-50 text-zinc-500">
                            Cargando detalle del producto...
                        </div>
                    </template>

                    <template x-if="!loading && currentProduct">
                        <div class="space-y-4">
                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-5">
                                    <flux:heading size="base">Historial de movimientos</flux:heading>
                                </div>

                                <div class="overflow-x-auto rounded-2xl border border-zinc-200/80">
                                    <table class="min-w-full divide-y divide-zinc-200">
                                        <thead class="bg-zinc-50">
                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                                <th class="px-4 py-3">Fecha</th>
                                                <th class="px-4 py-3">Local</th>
                                                <th class="px-4 py-3">Ajuste</th>
                                                <th class="px-4 py-3">Causa</th>
                                                <th class="px-4 py-3">Responsable</th>
                                                <th class="px-4 py-3">Comentario</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 bg-white">
                                            <template x-if="history.length === 0">
                                                <tr>
                                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500">
                                                        Todavía no hay movimientos para este producto.
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-for="movement in history" :key="movement.id">
                                                <tr class="text-sm text-zinc-700">
                                                    <td class="px-4 py-3" x-text="movement.occurred_at"></td>
                                                    <td class="px-4 py-3" x-text="movement.branch"></td>
                                                    <td class="px-4 py-3">
                                                        <div class="inline-flex items-center gap-2 font-medium text-zinc-900">
                                                            <span class="inline-flex size-5 items-center justify-center rounded-full"
                                                                :class="movement.direction === 'up' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                                                                <span class="text-[10px] leading-none" x-text="movement.direction === 'up' ? '↑' : '↓'"></span>
                                                            </span>
                                                            <span x-text="`De ${movement.previous_stock} a ${movement.new_stock}`"></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3" x-text="movement.reason"></td>
                                                    <td class="px-4 py-3" x-text="movement.user"></td>
                                                    <td class="px-4 py-3" x-text="movement.comment || 'N/A'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-2">
                                    <flux:heading size="base">Stock en locales</flux:heading>
                                    <flux:text class="mt-1 text-sm text-zinc-500">
                                        Indica la cantidad de stock actual del producto en cada uno de tus locales.
                                    </flux:text>
                                </div>

                                <div class="overflow-x-auto rounded-2xl border border-zinc-200/80">
                                    <table class="min-w-full divide-y divide-zinc-200">
                                        <thead class="bg-zinc-50">
                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                                <th class="px-4 py-3">Local</th>
                                                <th class="px-4 py-3">Cantidad en stock</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 bg-white">
                                            <template x-for="branch in branches" :key="branch.id">
                                                <tr class="text-sm text-zinc-700">
                                                    <td class="px-4 py-3" x-text="branch.name"></td>
                                                    <td class="px-4 py-3">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            class="h-12 w-full rounded-xl border border-zinc-300 px-3 text-sm outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                                                            x-model="stockByBranch[branch.id]"
                                                        />
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-zinc-500">
                        Los cambios actualizan el stock por local y generan el movimiento correspondiente.
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                            @click="closeModal()"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60"
                            x-bind:disabled="saving || loading || !currentProduct"
                            @click="saveMovement()"
                        >
                            <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::app>
