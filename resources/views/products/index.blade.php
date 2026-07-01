<x-layouts::app :title="__('Inventario de productos')">
    <section
        x-data="productInventory(@js($inventoryConfig))"
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

        <div class="relative w-full overflow-hidden rounded-[24px] border border-zinc-200/80 bg-white shadow-[0_20px_70px_rgba(122,80,210,0.08)]">
            <div class="space-y-6 px-4 py-6 sm:px-5 lg:px-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0">
                        <flux:badge color="violet" size="sm" inset="left">Productos</flux:badge>
                        <flux:heading size="xl" class="mt-3">Inventario</flux:heading>

                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap items-center gap-2">
                            <flux:input
                                name="q"
                                value="{{ $search }}"
                                type="search"
                                icon="magnifying-glass"
                                placeholder="Busca por nombre, marca o código"
                                class="w-full min-w-[16rem] rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm xl:w-[22rem]"
                            />

                            @if ($search !== '')
                                <flux:button variant="ghost" href="{{ route('products.index') }}" icon="x-mark">
                                    Limpiar
                                </flux:button>
                            @endif
                        </form>

                        <form
                            method="POST"
                            action="{{ route('products.import') }}"
                            enctype="multipart/form-data"
                            class="flex flex-wrap items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-3 py-2 shadow-sm"
                            onsubmit="return confirm('Se eliminara el inventario actual y se reemplazara con el contenido del Excel. Deseas continuar?')"
                        >
                            @csrf

                            <label for="inventory_file" class="sr-only">Archivo Excel de inventario</label>
                            <input
                                id="inventory_file"
                                name="inventory_file"
                                type="file"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                required
                                class="block max-w-[16rem] text-sm text-zinc-600 file:mr-3 file:rounded-xl file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200"
                            />

                            <button
                                type="submit"
                                class="inline-flex h-10 items-center justify-center rounded-xl bg-amber-500 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600"
                            >
                                Importar Excel
                            </button>
                        </form>

                        <flux:button variant="primary" icon="plus" type="button" @click="openCreate()">
                            Nuevo producto
                        </flux:button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->has('inventory_file'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                        {{ $errors->first('inventory_file') }}
                    </div>
                @endif

                <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm">
                    @if ($products->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                            <div class="flex size-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <flux:icon name="cube" class="size-8" />
                            </div>

                            <div class="space-y-1">
                                <flux:heading size="lg">No hay productos aún</flux:heading>
                                <flux:text class="text-sm text-zinc-500">
                                    Crea tu primer producto para comenzar a registrar precios, stock y alarmas.
                                </flux:text>
                            </div>

                            <flux:button variant="primary" icon="plus" type="button" @click="openCreate()">
                                Nuevo producto
                            </flux:button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Producto</flux:table.column>
                                    <flux:table.column>Marca</flux:table.column>
                                    <flux:table.column>Categoría</flux:table.column>
                                    <flux:table.column>Formato</flux:table.column>
                                    <flux:table.column>Precio</flux:table.column>
                                    <flux:table.column>Stock</flux:table.column>
                                    <flux:table.column class="whitespace-nowrap text-center">+Stock</flux:table.column>
                                    <flux:table.column class="whitespace-nowrap text-center">-Stock</flux:table.column>
                                    <flux:table.column class="text-right">Opciones</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach ($products as $product)
                                        <flux:table.row :key="$product->id">
                                            <flux:table.cell>
                                                <div class="min-w-0">
                                                    <div class="font-medium text-zinc-900">{{ $product->name }}</div>
                                                    <div class="text-xs text-zinc-500">
                                                        {{ $product->barcode ?: 'Sin código de barras' }}
                                                    </div>
                                                </div>
                                            </flux:table.cell>
                                            <flux:table.cell>{{ $product->brand?->name ?? 'Sin marca' }}</flux:table.cell>
                                            <flux:table.cell>{{ $product->category?->name ?? 'Sin categoría' }}</flux:table.cell>
                                            <flux:table.cell>{{ $product->presentation?->name ?? 'Sin formato' }}</flux:table.cell>
                                            <flux:table.cell>S/ {{ number_format((float) $product->public_sale_price, 2) }}</flux:table.cell>
                                            <flux:table.cell>{{ number_format((float) $product->current_stock, 2) }}</flux:table.cell>
                                            <flux:table.cell>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm font-medium text-sky-700 transition hover:text-sky-900"
                                                    @click="openStockAdjustment('increase', @js([
                                                        'id' => $product->id,
                                                        'name' => $product->name,
                                                    ]))"
                                                >
                                                    <flux:icon name="plus" class="size-4" />
                                                    <span>Stock</span>
                                                </button>
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm font-medium text-sky-700 transition hover:text-sky-900"
                                                    @click="openStockAdjustment('decrease', @js([
                                                        'id' => $product->id,
                                                        'name' => $product->name,
                                                    ]))"
                                                >
                                                    <flux:icon name="minus" class="size-4" />
                                                    <span>Stock</span>
                                                </button>
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <div class="flex items-center justify-end gap-2">
                                                    <flux:button
                                                        size="sm"
                                                        variant="ghost"
                                                        icon="pencil-square"
                                                        type="button"
                                                        aria-label="Editar producto"
                                                        title="Editar producto"
                                                        @click="openEdit(@js([
                                                            'id' => $product->id,
                                                            'name' => $product->name,
                                                            'barcode' => $product->barcode ?? '',
                                                            'brand_id' => $product->brand_id,
                                                            'category_id' => $product->category_id,
                                                            'presentation_id' => $product->presentation_id,
                                                            'public_sale_price' => (string) $product->public_sale_price,
                                                            'current_stock' => (string) $product->current_stock,
                                                            'purchase_cost' => (string) $product->purchase_cost,
                                                            'internal_sale_price' => (string) $product->internal_sale_price,
                                                            'sale_commission' => (string) $product->sale_commission,
                                                            'commission_type' => $product->commission_type,
                                                            'includes_tax' => (bool) $product->includes_tax,
                                                            'description' => $product->description ?? '',
                                                            'stock_alarm_enabled' => (bool) $product->stock_alarm_enabled,
                                                            'stock_alarm_limit' => $product->stock_alarm_limit === null ? '' : (string) $product->stock_alarm_limit,
                                                            'stock_alarm_emails' => $product->stock_alarm_emails ?? '',
                                                            'is_active' => (bool) $product->is_active,
                                                        ]))"
                                                    />

                                                    <flux:button
                                                        size="sm"
                                                        variant="danger"
                                                        icon="trash"
                                                        type="button"
                                                        aria-label="Eliminar producto"
                                                        title="Eliminar producto"
                                                        @click="deleteProduct({{ $product->id }}, @js($product->name))"
                                                    />
                                                </div>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>

                        <div class="border-t border-zinc-200/80 px-5 py-4">
                            {{ $products->links() }}
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
                        <flux:heading size="lg" x-text="modalTitle()"></flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Completa los datos básicos, opciones avanzadas y alarmas de stock.
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

                <div class="border-b border-violet-200 px-6 pt-4">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-t-2xl border px-4 py-3 text-sm font-medium transition"
                            :class="activeTab === 'basic'
                                ? 'border-violet-300 bg-violet-50 text-violet-700 shadow-sm'
                                : 'border-transparent bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                            @click="activeTab = 'basic'"
                        >
                            Datos básicos
                        </button>

                        <button
                            type="button"
                            class="rounded-t-2xl border px-4 py-3 text-sm font-medium transition"
                            :class="activeTab === 'advanced'
                                ? 'border-violet-300 bg-violet-50 text-violet-700 shadow-sm'
                                : 'border-transparent bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                            @click="activeTab = 'advanced'"
                        >
                            Opciones avanzadas
                        </button>

                        <button
                            type="button"
                            class="rounded-t-2xl border px-4 py-3 text-sm font-medium transition"
                            :class="activeTab === 'alarms'
                                ? 'border-violet-300 bg-violet-50 text-violet-700 shadow-sm'
                                : 'border-transparent bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                            @click="activeTab = 'alarms'"
                        >
                            Alarmas de stock
                        </button>

                        <button
                            x-show="isEditing"
                            x-cloak
                            type="button"
                            class="rounded-t-2xl border px-4 py-3 text-sm font-medium transition"
                            :class="activeTab === 'movements'
                                ? 'border-violet-300 bg-violet-50 text-violet-700 shadow-sm'
                                : 'border-transparent bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                            @click="activeTab = 'movements'"
                        >
                            Movimientos de stock
                        </button>
                    </div>
                </div>

                <form @submit.prevent="saveProduct" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <div x-show="activeTab === 'basic'" x-cloak class="space-y-4">
                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-5">
                                    <flux:heading size="base">Datos básicos</flux:heading>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <flux:input x-model="form.name" label="Nombre *" type="text" placeholder="Indica el nombre del producto" class="rounded-2xl" />
                                        <p class="text-sm text-rose-600" x-show="errors.name" x-text="errors.name" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input x-model="form.barcode" label="Código de barras" type="text" placeholder="Indica el código del producto" class="rounded-2xl" />
                                        <p class="text-sm text-rose-600" x-show="errors.barcode" x-text="errors.barcode" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:select x-model="form.brand_id" label="Marca *" class="rounded-2xl">
                                            <option value="">Selecciona una marca</option>
                                            <template x-for="brand in brands" :key="brand.id">
                                                <option :value="brand.id" x-text="brand.name"></option>
                                            </template>
                                        </flux:select>
                                        <button type="button" class="text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-4 hover:text-violet-700" @click="openQuickCreate('brands')">
                                            + Nueva marca
                                        </button>
                                        <p class="text-sm text-rose-600" x-show="errors.brand_id" x-text="errors.brand_id" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:select x-model="form.category_id" label="Categoría *" class="rounded-2xl">
                                            <option value="">Selecciona una categoría</option>
                                            <template x-for="category in categories" :key="category.id">
                                                <option :value="category.id" x-text="category.name"></option>
                                            </template>
                                        </flux:select>
                                        <button type="button" class="text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-4 hover:text-violet-700" @click="openQuickCreate('categories')">
                                            + Nueva categoría
                                        </button>
                                        <p class="text-sm text-rose-600" x-show="errors.category_id" x-text="errors.category_id" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:select x-model="form.presentation_id" label="Formato/Presentación *" class="rounded-2xl">
                                            <option value="">Selecciona un formato</option>
                                            <template x-for="presentation in presentations" :key="presentation.id">
                                                <option :value="presentation.id" x-text="presentation.name"></option>
                                            </template>
                                        </flux:select>
                                        <button type="button" class="text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-4 hover:text-violet-700" @click="openQuickCreate('presentations')">
                                            + Nuevo formato
                                        </button>
                                        <p class="text-sm text-rose-600" x-show="errors.presentation_id" x-text="errors.presentation_id" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input x-model="form.public_sale_price" label="Precio de venta al público" type="number" min="0" step="0.01" class="rounded-2xl" />
                                        <p class="text-sm text-rose-600" x-show="errors.public_sale_price" x-text="errors.public_sale_price" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input x-model="form.current_stock" label="Cantidad en stock" type="number" min="0" step="0.01" class="rounded-2xl" />
                                        <p class="text-sm text-rose-600" x-show="errors.current_stock" x-text="errors.current_stock" x-cloak></p>
                                    </div>
                                </div>

                                <div class="mt-5 rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-4">
                                    <flux:switch x-model="form.stock_alarm_enabled" label="Activar alarma de stock bajo del producto" align="left" />
                                </div>
                            </div>
                        </div>

                        <div x-show="activeTab === 'advanced'" x-cloak class="space-y-4">
                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-5">
                                    <flux:heading size="base">Opciones avanzadas</flux:heading>
                                </div>

                                <div class="space-y-5">
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div class="space-y-1.5">
                                            <flux:input x-model="form.purchase_cost" label="Costo de compra" type="number" min="0" step="0.01" class="rounded-2xl" />
                                            <p class="text-sm text-rose-600" x-show="errors.purchase_cost" x-text="errors.purchase_cost" x-cloak></p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <flux:input x-model="form.internal_sale_price" label="Precio de venta interna" type="number" min="0" step="0.01" class="rounded-2xl" />
                                            <p class="text-sm text-rose-600" x-show="errors.internal_sale_price" x-text="errors.internal_sale_price" x-cloak></p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <flux:label>Comisión de venta</flux:label>
                                            <div class="grid grid-cols-[minmax(0,1fr)_8rem] overflow-hidden rounded-2xl border border-zinc-300 bg-white">
                                                <input
                                                    x-model="form.sale_commission"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="h-12 border-0 px-4 text-sm text-zinc-900 outline-none focus:ring-0"
                                                />
                                                <select
                                                    x-model="form.commission_type"
                                                    class="h-12 border-l border-zinc-300 bg-white px-3 text-sm text-zinc-700 outline-none focus:ring-0"
                                                >
                                                    <option value="percent">%</option>
                                                    <option value="amount">Monto</option>
                                                </select>
                                            </div>
                                            <p class="text-sm text-rose-600" x-show="errors.sale_commission" x-text="errors.sale_commission" x-cloak></p>
                                        </div>

                                        <div class="flex items-end">
                                            <div class="rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-4">
                                                <flux:switch x-model="form.includes_tax" label="Precio incluye IVA en comprobante de caja" align="left" />
                                            </div>
                                        </div>

                                        <div class="flex items-end">
                                            <div class="rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-4">
                                                <flux:switch x-model="form.is_active" label="Producto activo" align="left" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:textarea x-model="form.description" label="Descripción" rows="5" class="rounded-2xl" />
                                        <p class="text-sm text-rose-600" x-show="errors.description" x-text="errors.description" x-cloak></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="activeTab === 'alarms'" x-cloak class="space-y-4">
                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                                    Te damos la opción de configurar el stock límite de este producto por cada local para que recibas un aviso de bajo stock y puedas reponerlo.
                                </div>

                                <div class="mt-5">
                                    <flux:heading size="base">Alarma de stock</flux:heading>
                                </div>

                                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <flux:input x-model="form.stock_alarm_limit" label="Stock en locales" type="number" min="0" step="0.01" placeholder="00" class="rounded-2xl" />
                                        <p class="text-sm text-zinc-500">
                                            Indica la cantidad de stock actual del producto en cada uno de tus locales.
                                        </p>
                                        <p class="text-sm text-rose-600" x-show="errors.stock_alarm_limit" x-text="errors.stock_alarm_limit" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input
                                            x-model="form.stock_alarm_emails"
                                            label="Email para notificaciones"
                                            type="text"
                                            placeholder="mail1@email.com, mail2@email.com"
                                            class="rounded-2xl"
                                        />
                                        <p class="text-sm text-zinc-500">
                                            Ingresa los correos que recibirán los avisos, si agregas más de uno sepáralo por comas.
                                        </p>
                                        <p class="text-sm text-rose-600" x-show="errors.stock_alarm_emails" x-text="errors.stock_alarm_emails" x-cloak></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div x-show="activeTab === 'movements' && isEditing" x-cloak class="space-y-4">
                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-5">
                                    <flux:heading size="base">Movimientos de stock del ultimo anio</flux:heading>
                                </div>

                                <template x-if="movementLoading">
                                    <div class="flex min-h-[18rem] items-center justify-center rounded-2xl border border-zinc-200/80 bg-zinc-50 text-sm text-zinc-500">
                                        Cargando movimientos del producto...
                                    </div>
                                </template>

                                <template x-if="!movementLoading">
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
                                                <template x-if="movementHistory.length === 0">
                                                    <tr>
                                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500">
                                                            Todavia no hay movimientos de stock registrados para este producto.
                                                        </td>
                                                    </tr>
                                                </template>
                                                <template x-for="movement in movementHistory" :key="movement.id">
                                                    <tr class="text-sm text-zinc-700">
                                                        <td class="px-4 py-3" x-text="movement.occurred_at"></td>
                                                        <td class="px-4 py-3" x-text="movement.branch"></td>
                                                        <td class="px-4 py-3">
                                                            <div class="inline-flex items-center gap-2 font-medium text-zinc-900">
                                                                <span
                                                                    class="inline-flex size-5 items-center justify-center rounded-full"
                                                                    :class="movement.direction === 'up' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"
                                                                >
                                                                    <span class="text-[10px] leading-none" x-text="movement.direction === 'up' ? '+' : '-'"></span>
                                                                </span>
                                                                <span x-text="movementAdjustmentLabel(movement)"></span>
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
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-zinc-500" x-text="footerMessage()"></div>

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

        <div
            x-show="quickCreateOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[75] flex items-center justify-center bg-zinc-950/50 px-3 py-6 backdrop-blur-[2px]"
            @keydown.escape.window="closeQuickCreate()"
            @click.self="closeQuickCreate()"
        >
            <div class="relative flex w-full max-w-lg flex-col overflow-hidden rounded-[28px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-6 py-5">
                    <div>
                        <flux:heading size="lg" x-text="quickCreateTitle()"></flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500" x-text="quickCreateDescription()"></flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        @click="closeQuickCreate()"
                        aria-label="Cerrar modal de alta rápida"
                    >
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>

                <form class="space-y-5 px-6 py-5" @submit.prevent="saveQuickCreate()">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-zinc-700" x-text="`Nombre de la ${quickCreateLabel()} *`"></label>
                        <input
                            x-model="quickCreateName"
                            type="text"
                            class="h-12 w-full rounded-2xl border border-zinc-300 bg-white px-4 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                            :placeholder="quickCreatePlaceholder()"
                            autofocus
                        />
                        <p class="text-sm text-rose-600" x-show="quickCreateErrors.name" x-text="quickCreateErrors.name" x-cloak></p>
                    </div>

                    <div class="rounded-2xl border border-violet-100 bg-violet-50 px-4 py-3 text-sm text-violet-900">
                        Al crear este registro, quedará seleccionado automáticamente en el formulario del producto.
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                            @click="closeQuickCreate()"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="quickCreateSaving"
                        >
                            <span x-text="quickCreateSubmitLabel()"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-show="stockAdjustmentOpen"
            x-transition.opacity
            class="fixed inset-0 z-[70] flex items-center justify-center bg-zinc-950/50 px-3 py-6 backdrop-blur-[2px]"
            @keydown.escape.window="closeStockAdjustment()"
            @click.self="closeStockAdjustment()"
        >
            <div class="relative flex w-full max-w-5xl max-h-[92vh] flex-col overflow-hidden rounded-[24px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)]">
                <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-6 py-5">
                    <div>
                        <flux:heading size="lg" x-text="stockAdjustmentTitle()"></flux:heading>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        @click="closeStockAdjustment()"
                        aria-label="Cerrar modal de stock"
                    >
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="space-y-5">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800" x-text="stockAdjustmentHelpText()"></div>

                        <template x-if="stockAdjustmentLoading">
                            <div class="flex min-h-[18rem] items-center justify-center rounded-2xl border border-zinc-200/80 bg-zinc-50 text-sm text-zinc-500">
                                Cargando datos del producto...
                            </div>
                        </template>

                        <template x-if="!stockAdjustmentLoading && stockAdjustmentProduct">
                            <div class="space-y-5">
                                <div class="overflow-hidden rounded-[24px] border border-zinc-200/80">
                                    <div class="grid gap-4 border-b border-zinc-200 bg-zinc-50 px-6 py-4 text-sm font-semibold text-zinc-700 lg:grid-cols-[12rem_minmax(0,1fr)_12rem]">
                                        <div>Stock actual</div>
                                        <div>Local</div>
                                        <div class="lg:text-center">Nuevo stock</div>
                                    </div>

                                    <div class="grid items-end gap-4 px-6 py-5 lg:grid-cols-[12rem_minmax(0,1fr)_12rem]">
                                        <div class="flex items-center justify-center gap-3 text-4xl font-semibold text-zinc-900">
                                            <span x-text="formatStock(selectedStockAdjustmentCurrent())"></span>
                                            <span x-text="stockAdjustmentMode === 'increase' ? '+' : '-'"></span>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="space-y-1.5">
                                                <label class="text-sm font-medium text-zinc-700">Local</label>
                                                <select
                                                    x-model="stockAdjustmentForm.branch_id"
                                                    class="h-12 w-full rounded-2xl border border-zinc-300 bg-white px-4 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                                                >
                                                    <option value="">Selecciona un local</option>
                                                    <template x-for="branch in stockAdjustmentBranches" :key="branch.id">
                                                        <option :value="branch.id" x-text="branch.name"></option>
                                                    </template>
                                                </select>
                                                <p class="text-sm text-rose-600" x-show="stockAdjustmentErrors.branch_id" x-text="stockAdjustmentErrors.branch_id" x-cloak></p>
                                            </div>

                                            <div class="space-y-1.5">
                                                <label class="text-sm font-medium text-zinc-700" x-text="stockAdjustmentQuantityLabel()"></label>
                                                <input
                                                    x-model="stockAdjustmentForm.quantity"
                                                    type="number"
                                                    min="0.01"
                                                    step="0.01"
                                                    placeholder="00"
                                                    class="h-12 w-full rounded-2xl border border-zinc-300 bg-white px-4 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                                                />
                                                <p class="text-sm text-rose-600" x-show="stockAdjustmentErrors.quantity" x-text="stockAdjustmentErrors.quantity" x-cloak></p>
                                            </div>
                                        </div>

                                        <div class="text-center text-4xl font-semibold text-zinc-900">
                                            = <span x-text="formatStock(stockAdjustmentPreviewStock())"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-zinc-700">Comentarios</label>
                                    <textarea
                                        x-model="stockAdjustmentForm.comment"
                                        rows="4"
                                        class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                                        :placeholder="stockAdjustmentCommentPlaceholder()"
                                    ></textarea>
                                    <p class="text-sm text-rose-600" x-show="stockAdjustmentErrors.comment" x-text="stockAdjustmentErrors.comment" x-cloak></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <button
                        type="button"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                        @click="closeStockAdjustment()"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="stockAdjustmentSaving || stockAdjustmentLoading"
                        @click="saveStockAdjustment()"
                    >
                        <span x-text="stockAdjustmentSubmitLabel()"></span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</x-layouts::app>
