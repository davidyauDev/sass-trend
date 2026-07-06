<x-layouts::app :title="__('Venta de productos')">
    @php
        $formatAmount = static fn (float|int $value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
        $detailBaseQuery = [
            'from' => $from,
            'to' => $to,
            'product_status' => $productStatus,
            'product_id' => $productId,
            'seller_key' => $sellerKey,
        ];
        $selectedProductLabel = collect($products)->firstWhere('id', $productId)['name'] ?? null;
        $selectedSellerLabel = $sellerKey === null
            ? null
            : ($sellerBreakdown->firstWhere('seller_key', $sellerKey)['seller_name'] ?? null);
        $highestProduct = $highlights['highest_product'] ?? null;
        $lowestProduct = $highlights['lowest_product'] ?? null;
        $highestSeller = $highlights['highest_seller'] ?? null;
        $lowestSeller = $highlights['lowest_seller'] ?? null;
    @endphp

    <section
        x-data="productSales(@js($salesConfig))"
        x-cloak
        class="w-full px-0 py-6"
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

        <div class="space-y-6">
            <div class="rounded-[26px] bg-white px-4 py-5 shadow-sm ring-1 ring-zinc-200/70 sm:px-6">
                <form method="GET" action="{{ route('products.sales.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <input type="hidden" name="detail" value="{{ $detailTab }}">

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-900">Periodo de tiempo</label>
                        <div class="grid grid-cols-2 gap-2 sm:gap-3 rounded-2xl border border-zinc-300 bg-white p-3 shadow-sm">
                            <input
                                type="date"
                                name="from"
                                value="{{ $from }}"
                                class="h-10 sm:h-11 rounded-xl border border-zinc-200 px-2 sm:px-3 text-xs sm:text-sm text-slate-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                            />
                            <input
                                type="date"
                                name="to"
                                value="{{ $to }}"
                                class="h-10 sm:h-11 rounded-xl border border-zinc-200 px-2 sm:px-3 text-xs sm:text-sm text-slate-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-900">Estado del producto</label>
                        <select
                            name="product_status"
                            class="h-12 sm:h-[60px] w-full rounded-2xl border border-zinc-300 bg-white px-4 text-xs sm:text-sm text-slate-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                        >
                            <option value="active" @selected($productStatus === 'active')>Activo</option>
                            <option value="inactive" @selected($productStatus === 'inactive')>Inactivo</option>
                            <option value="all" @selected($productStatus === 'all')>Todos</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-900">Productos</label>
                        <select
                            name="product_id"
                            class="h-12 sm:h-[60px] w-full rounded-2xl border border-zinc-300 bg-white px-4 text-xs sm:text-sm text-slate-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                        >
                            <option value="">Todos</option>
                            @foreach ($products as $product)
                                <option value="{{ $product['id'] }}" @selected((string) $productId === (string) $product['id'])>
                                    {{ $product['name'] }} · Stock: {{ number_format((float) $product['current_stock'], 2, '.', ',') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap items-center justify-end gap-2">
                        <flux:button type="submit" variant="primary" icon="magnifying-glass">
                            Aplicar filtros
                        </flux:button>

                        @if ($hasFilters)
                            <flux:button variant="ghost" href="{{ route('products.sales.index', ['detail' => $detailTab]) }}" icon="x-mark">
                                Limpiar
                            </flux:button>
                        @endif

                        <flux:button variant="ghost" icon="plus" type="button" @click="openCreate()">
                            Registrar venta
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex min-h-[160px] sm:min-h-[214px] items-center justify-center rounded-[18px] bg-[#263039] px-4 sm:px-6 py-6 sm:py-8 text-center text-white shadow-[0_18px_40px_rgba(21,30,39,0.15)]">
                    <div>
                        <div class="text-3xl sm:text-5xl font-semibold leading-none">S/{{ $formatAmount($salesSummary['revenue']) }}</div>
                        <div class="mt-3 sm:mt-4 text-sm sm:text-lg uppercase tracking-wide text-slate-300">Recaudacion</div>
                    </div>
                </div>

                <div class="flex min-h-[160px] sm:min-h-[214px] items-center justify-center rounded-[18px] bg-[#263039] px-4 sm:px-6 py-6 sm:py-8 text-center text-white shadow-[0_18px_40px_rgba(21,30,39,0.15)]">
                    <div>
                        <div class="text-3xl sm:text-5xl font-semibold leading-none">{{ $formatAmount($salesSummary['units_sold']) }}</div>
                        <div class="mt-3 sm:mt-4 text-sm sm:text-lg uppercase tracking-wide text-slate-300">Unidades</div>
                    </div>
                </div>

                <div class="rounded-[18px] bg-white px-4 sm:px-5 py-4 shadow-sm ring-1 ring-zinc-200/70 sm:col-span-2 xl:col-span-1">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-base sm:text-[20px] font-semibold text-slate-900">Mayor ingreso</h2>
                        <span class="inline-flex size-6 sm:size-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <flux:icon name="arrow-up" class="size-3 sm:size-4" />
                        </span>
                    </div>

                    <div class="mt-4 sm:mt-5 space-y-4 sm:space-y-5">
                        <div class="border-t border-zinc-200 pt-4 sm:pt-5">
                            <div class="text-sm sm:text-[15px] text-slate-900">{{ $highestProduct['product_name'] ?? 'Sin registros' }}</div>
                            <div class="mt-1 text-xs sm:text-[14px] text-slate-500">{{ $highestProduct['presentation_name'] ?? 'Sin formato' }}</div>
                            <div class="mt-2 flex items-end justify-between gap-4">
                                <div class="text-xl sm:text-[24px] font-semibold leading-none text-slate-900">
                                    S/{{ $formatAmount($highestProduct['revenue'] ?? 0) }}
                                </div>
                                <div class="text-xs sm:text-[15px] text-slate-700 underline decoration-zinc-300 underline-offset-4">
                                    {{ $formatAmount($highestProduct['units_sold'] ?? 0) }} {{ ($highestProduct['units_sold'] ?? 0) == 1.0 ? 'Unidad' : 'Unidades' }}
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-zinc-200 pt-4 sm:pt-5">
                            <div class="text-sm sm:text-[15px] font-medium text-slate-900">
                                Vendedor: {{ $highestSeller['seller_name'] ?? 'Sin registros' }}
                            </div>
                            <div class="mt-2 flex items-end justify-between gap-4">
                                <div class="text-xl sm:text-[24px] font-semibold leading-none text-slate-900">
                                    S/{{ $formatAmount($highestSeller['revenue'] ?? 0) }}
                                </div>
                                <div class="text-xs sm:text-[15px] text-slate-700 underline decoration-zinc-300 underline-offset-4">
                                    {{ $formatAmount($highestSeller['units_sold'] ?? 0) }} {{ ($highestSeller['units_sold'] ?? 0) == 1.0 ? 'Unidad' : 'Unidades' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[18px] bg-white px-4 sm:px-5 py-4 shadow-sm ring-1 ring-zinc-200/70">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-base sm:text-[20px] font-semibold text-slate-900">Menor ingreso</h2>
                        <span class="inline-flex size-6 sm:size-7 items-center justify-center rounded-full bg-rose-100 text-rose-500">
                            <flux:icon name="arrow-down" class="size-3 sm:size-4" />
                        </span>
                    </div>

                    <div class="mt-4 sm:mt-5 space-y-4 sm:space-y-5">
                        <div class="border-t border-zinc-200 pt-4 sm:pt-5">
                            <div class="text-sm sm:text-[15px] text-slate-900">{{ $lowestProduct['product_name'] ?? 'Sin registros' }}</div>
                            <div class="mt-1 text-xs sm:text-[14px] text-slate-500">{{ $lowestProduct['presentation_name'] ?? 'Sin formato' }}</div>
                            <div class="mt-2 flex items-end justify-between gap-4">
                                <div class="text-xl sm:text-[24px] font-semibold leading-none text-slate-900">
                                    S/{{ $formatAmount($lowestProduct['revenue'] ?? 0) }}
                                </div>
                                <div class="text-xs sm:text-[15px] text-slate-700 underline decoration-zinc-300 underline-offset-4">
                                    {{ $formatAmount($lowestProduct['units_sold'] ?? 0) }} {{ ($lowestProduct['units_sold'] ?? 0) == 1.0 ? 'Unidad' : 'Unidades' }}
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-zinc-200 pt-4 sm:pt-5">
                            <div class="text-sm sm:text-[15px] font-medium text-slate-900">
                                Vendedor: {{ $lowestSeller['seller_name'] ?? 'Sin registros' }}
                            </div>
                            <div class="mt-2 flex items-end justify-between gap-4">
                                <div class="text-xl sm:text-[24px] font-semibold leading-none text-slate-900">
                                    S/{{ $formatAmount($lowestSeller['revenue'] ?? 0) }}
                                </div>
                                <div class="text-xs sm:text-[15px] text-slate-700 underline decoration-zinc-300 underline-offset-4">
                                    {{ $formatAmount($lowestSeller['units_sold'] ?? 0) }} {{ ($lowestSeller['units_sold'] ?? 0) == 1.0 ? 'Unidad' : 'Unidades' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sales-detail" class="rounded-[18px] bg-white px-4 sm:px-5 py-4 sm:py-5 shadow-sm ring-1 ring-zinc-200/70">
                <div class="text-base sm:text-[18px] font-semibold text-slate-900">Detalle de la venta</div>

                <div class="mt-6 flex flex-col gap-4 border-b border-zinc-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-center gap-6 sm:gap-8 text-sm sm:text-[15px]">
                        <a
                            href="{{ route('products.sales.index', array_merge($detailBaseQuery, ['detail' => 'products'])) }}"
                            class="relative pb-3 font-medium {{ $detailTab === 'products' ? 'text-violet-600' : 'text-slate-900' }}"
                        >
                            Por productos
                            @if ($detailTab === 'products')
                                <span class="absolute inset-x-0 bottom-0 h-[3px] rounded-full bg-violet-500"></span>
                            @endif
                        </a>
                        <a
                            href="{{ route('products.sales.index', array_merge($detailBaseQuery, ['detail' => 'vendors'])) }}"
                            class="relative pb-3 font-medium {{ $detailTab === 'vendors' ? 'text-violet-600' : 'text-slate-900' }}"
                        >
                            Por vendedor
                            @if ($detailTab === 'vendors')
                                <span class="absolute inset-x-0 bottom-0 h-[3px] rounded-full bg-violet-500"></span>
                            @endif
                        </a>
                    </div>

                    <flux:button
                        variant="ghost"
                        icon="arrow-down-tray"
                        href="{{ route('products.sales.export', array_merge($detailBaseQuery, ['detail' => $detailTab])) }}"
                    >
                        Descargar reporte
                    </flux:button>
                </div>

                @if ($selectedProductLabel !== null || $selectedSellerLabel !== null)
                    <div class="mt-4 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-900">
                        @if ($selectedProductLabel !== null)
                            <span>Producto filtrado: <strong>{{ $selectedProductLabel }}</strong>.</span>
                        @endif
                        @if ($selectedSellerLabel !== null)
                            <span> Vendedor filtrado: <strong>{{ $selectedSellerLabel }}</strong>.</span>
                        @endif
                        <a href="{{ route('products.sales.index', ['detail' => $detailTab]) }}" class="ml-1 font-semibold underline underline-offset-4">Quitar filtro</a>
                    </div>
                @endif

                @if (($detailTab === 'products' && $productBreakdown->isEmpty()) || ($detailTab === 'vendors' && $sellerBreakdown->isEmpty()))
                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                        <div class="flex size-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <flux:icon name="shopping-cart" class="size-8" />
                        </div>
                        <div class="space-y-1">
                            <flux:heading size="lg">No hay ventas registradas</flux:heading>
                            <flux:text class="text-sm text-zinc-500">
                                Registra la primera venta para empezar a ver el detalle por producto y por vendedor.
                            </flux:text>
                        </div>
                    </div>
                @elseif ($detailTab === 'products')
                    <div class="mt-4 overflow-x-auto rounded-[16px] border border-zinc-200/80">
                        <table class="min-w-full divide-y divide-zinc-200 text-left">
                            <thead class="bg-white">
                                <tr class="text-xs sm:text-[14px] font-semibold text-slate-700">
                                    <th class="px-3 sm:px-4 py-4">Producto</th>
                                    <th class="hidden sm:table-cell px-3 sm:px-4 py-4">Formato</th>
                                    <th class="px-3 sm:px-4 py-4">Unidades</th>
                                    <th class="px-3 sm:px-4 py-4">Recaudacion</th>
                                    <th class="px-3 sm:px-4 py-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 bg-white text-xs sm:text-[15px] text-slate-800">
                                @foreach ($productBreakdown as $row)
                                    <tr>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5">
                                            <div>{{ $row['product_name'] }}</div>
                                            <div class="sm:hidden mt-0.5 text-xs text-slate-500">{{ $row['presentation_name'] }}</div>
                                        </td>
                                        <td class="hidden sm:table-cell px-3 sm:px-4 py-4 sm:py-5">{{ $row['presentation_name'] }}</td>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5">{{ $formatAmount($row['units_sold']) }}</td>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5 font-medium">S/{{ $formatAmount($row['revenue']) }}</td>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5 text-right">
                                            <a
                                                href="{{ route('products.sales.index', array_merge($detailBaseQuery, ['detail' => 'products', 'product_id' => $row['product_id']])) }}#sales-detail"
                                                class="inline-flex items-center gap-1 sm:gap-2 rounded-xl border border-zinc-200 px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-violet-600 shadow-sm transition hover:border-violet-200 hover:bg-violet-50"
                                            >
                                                <flux:icon name="eye" class="size-3 sm:size-4" />
                                                <span class="hidden sm:inline">Ver detalles</span>
                                                <span class="sm:hidden">Ver</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mt-4 overflow-x-auto rounded-[16px] border border-zinc-200/80">
                        <table class="min-w-full divide-y divide-zinc-200 text-left">
                            <thead class="bg-white">
                                <tr class="text-xs sm:text-[14px] font-semibold text-slate-700">
                                    <th class="px-3 sm:px-4 py-4">Vendedor</th>
                                    <th class="hidden md:table-cell px-3 sm:px-4 py-4">Tipo</th>
                                    <th class="px-3 sm:px-4 py-4">Unidades</th>
                                    <th class="px-3 sm:px-4 py-4">Recaudacion</th>
                                    <th class="px-3 sm:px-4 py-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 bg-white text-xs sm:text-[15px] text-slate-800">
                                @foreach ($sellerBreakdown as $row)
                                    <tr>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5">{{ $row['seller_name'] }}</td>
                                        <td class="hidden md:table-cell px-3 sm:px-4 py-4 sm:py-5">{{ $row['user_type'] }}</td>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5">{{ $formatAmount($row['units_sold']) }}</td>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5 font-medium">S/{{ $formatAmount($row['revenue']) }}</td>
                                        <td class="px-3 sm:px-4 py-4 sm:py-5 text-right">
                                            <a
                                                href="{{ route('products.sales.index', array_merge($detailBaseQuery, ['detail' => 'vendors', 'seller_key' => $row['seller_key']])) }}#sales-detail"
                                                class="inline-flex items-center gap-1 sm:gap-2 rounded-xl border border-zinc-200 px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-violet-600 shadow-sm transition hover:border-violet-200 hover:bg-violet-50"
                                            >
                                                <flux:icon name="eye" class="size-3 sm:size-4" />
                                                <span class="hidden sm:inline">Ver detalles</span>
                                                <span class="sm:hidden">Ver</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div
            x-show="isOpen"
            x-transition.opacity
            class="fixed inset-0 z-[60] flex items-center justify-center bg-zinc-950/50 px-3 py-6 backdrop-blur-[2px]"
            @keydown.escape.window="closeModal()"
            @click.self="closeModal()"
        >
            <div class="relative flex w-full max-w-2xl sm:max-w-4xl max-h-[92vh] flex-col overflow-hidden rounded-[20px] sm:rounded-[30px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 sm:px-6 py-4 sm:py-5">
                    <div>
                        <flux:heading size="lg">Registrar venta</flux:heading>
                        <flux:text class="mt-1 text-xs sm:text-sm text-zinc-500">
                            Selecciona el local y el producto para descontar stock automaticamente.
                        </flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-1.5 sm:p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        @click="closeModal()"
                        aria-label="Cerrar modal"
                    >
                        <flux:icon name="x-mark" class="size-5 sm:size-6" />
                    </button>
                </div>

                <form @submit.prevent="saveSale" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto px-4 sm:px-6 py-4 sm:py-5">
                        <div class="rounded-[20px] sm:rounded-[24px] border border-zinc-200/80 p-4 sm:p-5">
                            <div class="grid gap-3 sm:gap-4 sm:grid-cols-2">
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
                            <option value="{{ $product['id'] }}">
                                {{ $product['name'] }} · Stock: {{ number_format((float) $product['current_stock'], 2, '.', ',') }}
                            </option>
                        @endforeach
                    </flux:select>
                    <div class="rounded-2xl border px-3 py-2 text-sm" x-show="selectedProduct()" x-cloak
                        x-bind:class="isStockAvailable()
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                            : 'border-rose-200 bg-rose-50 text-rose-900'">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Stock actual</span>
                            <span class="font-semibold" x-text="selectedProduct()?.current_stock ?? '0.00'"></span>
                        </div>
                        <p class="mt-1 text-xs" x-show="isStockAvailable()" x-cloak>
                            Cantidad disponible para agregar:
                            <span class="font-semibold" x-text="selectedProduct()?.current_stock ?? '0.00'"></span>
                            <span x-text="(Number.parseFloat(selectedProduct()?.current_stock ?? '0') || 0) === 1 ? 'unidad' : 'unidades'"></span>.
                        </p>
                        <p class="mt-1 text-xs font-medium" x-show="!isStockAvailable()" x-cloak>
                            No puedes registrar esta venta porque el producto está sin stock.
                        </p>
                    </div>
                    <p class="text-sm text-rose-600" x-show="errors.product_id" x-text="errors.product_id" x-cloak></p>
                </div>

                <div class="space-y-1.5">
                    <flux:input
                        x-model="form.quantity"
                        label="Cantidad *"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="rounded-2xl"
                        x-bind:max="selectedProductStock() > 0 ? selectedProductStock() : null"
                        x-bind:disabled="!isStockAvailable()"
                    />
                    <p class="text-xs text-zinc-500" x-show="selectedProduct()" x-cloak>
                        <span x-show="isStockAvailable()" x-cloak>
                            Puedes agregar hasta
                            <span class="font-semibold" x-text="selectedProduct()?.current_stock ?? '0.00'"></span>
                            <span x-text="(Number.parseFloat(selectedProduct()?.current_stock ?? '0') || 0) === 1 ? 'unidad' : 'unidades'"></span>.
                        </span>
                        <span class="font-medium text-rose-600" x-show="!isStockAvailable()" x-cloak>
                            No hay unidades disponibles para este producto.
                        </span>
                    </p>
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

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-4 sm:px-6 py-3 sm:py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs sm:text-sm text-zinc-500">
                            La venta descuenta stock del local seleccionado en tiempo real.
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex h-9 sm:h-10 items-center justify-center rounded-xl bg-zinc-100 px-3 sm:px-4 text-xs sm:text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                                @click="closeModal()"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="inline-flex h-9 sm:h-10 items-center justify-center rounded-xl bg-violet-600 px-3 sm:px-4 text-xs sm:text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60"
                                x-bind:disabled="!canSubmitSale()"
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
