<x-layouts::app :title="__('Venta de productos')">
    <section
        x-data="productSales(@js($salesConfig))"
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
                        <flux:heading size="xl" class="mt-3">Venta de productos</flux:heading>
                        <flux:text class="mt-2 max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
                            Registra ventas rápidas, descuenta stock del local seleccionado y revisa el resumen de recaudación por rango de fechas.
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" action="{{ route('products.sales.index') }}" class="flex flex-wrap items-center gap-2">
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
                                <flux:button variant="ghost" href="{{ route('products.sales.index') }}" icon="x-mark">
                                    Limpiar
                                </flux:button>
                            @endif
                        </form>

                        <flux:button variant="primary" icon="plus" type="button" @click="openCreate()">
                            Registrar venta
                        </flux:button>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[28px] bg-zinc-900 px-6 py-8 text-white shadow-[0_10px_30px_rgba(15,23,42,0.18)]">
                        <div class="text-sm text-zinc-300">Recaudación total</div>
                        <div class="mt-5 text-4xl font-semibold">S/ {{ number_format($salesSummary['revenue'], 2) }}</div>
                    </div>

                    <div class="rounded-[28px] bg-zinc-900 px-6 py-8 text-white shadow-[0_10px_30px_rgba(15,23,42,0.18)]">
                        <div class="text-sm text-zinc-300">Unidades vendidas</div>
                        <div class="mt-5 text-4xl font-semibold">{{ number_format($salesSummary['units_sold'], 2) }}</div>
                    </div>

                    <div class="rounded-[28px] border border-zinc-200 bg-white px-6 py-8 shadow-sm">
                        <div class="text-sm text-zinc-500">Ventas registradas</div>
                        <div class="mt-5 text-4xl font-semibold text-zinc-900">{{ number_format($salesSummary['sales_count']) }}</div>
                    </div>

                    <div class="rounded-[28px] border border-zinc-200 bg-white px-6 py-8 shadow-sm">
                        <div class="text-sm text-zinc-500">Ticket promedio</div>
                        <div class="mt-5 text-4xl font-semibold text-zinc-900">S/ {{ number_format($salesSummary['average_ticket'], 2) }}</div>
                    </div>
                </div>

                <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-200/80 px-5 py-4">
                        <div>
                            <flux:heading size="lg">Detalle de la venta</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500">
                                Revisa las ventas recientes por producto y local.
                            </flux:text>
                        </div>
                    </div>

                    @if ($sales->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                            <div class="flex size-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <flux:icon name="shopping-cart" class="size-8" />
                            </div>

                            <div class="space-y-1">
                                <flux:heading size="lg">No hay ventas registradas</flux:heading>
                                <flux:text class="text-sm text-zinc-500">
                                    Registra la primera venta para empezar a descontar stock y construir el historial.
                                </flux:text>
                            </div>

                            <flux:button variant="primary" icon="plus" type="button" @click="openCreate()">
                                Registrar venta
                            </flux:button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Fecha</flux:table.column>
                                    <flux:table.column>Local</flux:table.column>
                                    <flux:table.column>Producto</flux:table.column>
                                    <flux:table.column>Formato/Presentación</flux:table.column>
                                    <flux:table.column>Unidades vendidas</flux:table.column>
                                    <flux:table.column>Recaudación</flux:table.column>
                                    <flux:table.column>Staff</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach ($sales as $sale)
                                        @php($item = $sale->items->first())
                                        <flux:table.row :key="$sale->id">
                                            <flux:table.cell>{{ $sale->sold_at?->format('d-m-Y H:i') }}</flux:table.cell>
                                            <flux:table.cell>{{ $sale->branch?->name ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>{{ $item?->product?->name ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>{{ $item?->product?->presentation?->name ?? 'N/A' }}</flux:table.cell>
                                            <flux:table.cell>{{ number_format((float) ($item?->quantity ?? 0), 2) }}</flux:table.cell>
                                            <flux:table.cell>S/ {{ number_format((float) $sale->total, 2) }}</flux:table.cell>
                                            <flux:table.cell>{{ $sale->user?->name ?? 'N/A' }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>

                        <div class="border-t border-zinc-200/80 px-5 py-4">
                            {{ $sales->links() }}
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
            <div class="relative flex w-full max-w-4xl max-h-[92vh] flex-col overflow-hidden rounded-[30px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-6 py-5">
                    <div>
                        <flux:heading size="lg">Registrar venta</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Selecciona el local y el producto para descontar stock automáticamente.
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

                <form @submit.prevent="saveSale" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <div class="rounded-[24px] border border-zinc-200/80 p-5">
                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="space-y-1.5">
                                    <flux:select x-model="form.branch_id" label="Local *" class="rounded-2xl">
                                        <option value="">Selecciona un local</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                        @endforeach
                                    </flux:select>
                                    <p class="text-sm text-rose-600" x-show="errors.branch_id" x-text="errors.branch_id" x-cloak></p>
                                </div>

                                <div class="space-y-1.5">
                                    <flux:select
                                        x-model="form.product_id"
                                        label="Producto *"
                                        class="rounded-2xl"
                                        @change="syncProductPrice()"
                                    >
                                        <option value="">Selecciona un producto</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                                        @endforeach
                                    </flux:select>
                                    <div class="text-sm text-zinc-500" x-show="selectedProduct()" x-cloak>
                                        Stock actual:
                                        <span class="font-semibold text-zinc-700" x-text="selectedProduct()?.current_stock ?? '0.00'"></span>
                                    </div>
                                    <p class="text-sm text-rose-600" x-show="errors.product_id" x-text="errors.product_id" x-cloak></p>
                                </div>

                                <div class="space-y-1.5">
                                    <flux:input x-model="form.quantity" label="Cantidad *" type="number" min="0.01" step="0.01" class="rounded-2xl" />
                                    <p class="text-sm text-rose-600" x-show="errors.quantity" x-text="errors.quantity" x-cloak></p>
                                </div>

                                <div class="space-y-1.5">
                                    <flux:input x-model="form.unit_price" label="Precio unitario" type="number" min="0" step="0.01" class="rounded-2xl" />
                                    <p class="text-sm text-rose-600" x-show="errors.unit_price" x-text="errors.unit_price" x-cloak></p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-1.5">
                                <flux:textarea x-model="form.notes" label="Notas" rows="4" class="rounded-2xl" />
                                <p class="text-sm text-rose-600" x-show="errors.notes" x-text="errors.notes" x-cloak></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-zinc-500">
                            La venta descuenta stock del local seleccionado en tiempo real.
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
                                type="submit"
                                class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60"
                                x-bind:disabled="saving"
                            >
                                <span x-text="submitLabel()"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts::app>
