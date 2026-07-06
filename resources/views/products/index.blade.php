<x-layouts::app :title="__('Inventario de productos')">
    <section
        x-data="productInventory(@js($inventoryConfig))"
        x-cloak
        x-init="if ({{ $errors->has('inventory_file') ? 'true' : 'false' }}) { importInventoryOpen = true }"
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

        <div class="relative w-full overflow-hidden rounded-[24px]">
            <div class="space-y-5 px-1 py-2 sm:px-3 lg:px-0">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-[2rem] font-semibold tracking-tight text-slate-900 dark:text-white">Inventario</h1>
                        <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">Gestiona todos los productos, marcas y categorías de tu negocio.</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <flux:button variant="outline" icon="arrow-up-tray" type="button" @click="openImportInventory()" class="h-11 rounded-xl border-zinc-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm dark:border-white/10 dark:bg-white/[0.02] dark:text-white dark:shadow-none">
                            Importar Excel
                        </flux:button>

                        <flux:button variant="primary" icon="plus" type="button" @click="openCreate()" class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none">
                            Nuevo producto
                        </flux:button>
                    </div>
                </div>

                <form method="GET" action="{{ route('products.index') }}" class="rounded-[24px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">

                    <div class="grid items-end gap-4 lg:grid-cols-[minmax(18rem,1.6fr)_minmax(10rem,0.9fr)_minmax(10rem,0.9fr)_minmax(10rem,0.9fr)]">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-transparent select-none">Buscar</label>
                            <flux:input
                                name="q"
                                value="{{ $search }}"
                                type="search"
                                oninput="this.form.requestSubmit()"
                                icon="magnifying-glass"
                                placeholder="Buscar por nombre, marca o código..."
                                class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Marca</label>
                            <div
                                x-data="filterableSelect({
                                    value: @js((string) ($brandId ?? '')),
                                    placeholder: 'Todas las marcas',
                                    options: @js(
                                        collect($filterBrands)
                                            ->map(fn ($brand): array => ['value' => (string) $brand->id, 'label' => $brand->name])
                                            ->prepend(['value' => '', 'label' => 'Todas las marcas'])
                                            ->values()
                                    ),
                                })"
                                class="relative"
                                @click.outside="closePanel()"
                            >
                                <input x-ref="input" type="hidden" name="brand_id" x-model="value">

                                <button
                                    type="button"
                                    @click="open ? closePanel() : openPanel()"
                                    class="flex h-12 w-full items-center justify-between rounded-xl border border-zinc-200 bg-white px-3 text-left text-sm text-slate-800 shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                >
                                    <span class="truncate" x-text="selectedLabel"></span>
                                    <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" x-bind:class="{ 'rotate-180': open }" />
                                </button>

                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity.scale.origin.top
                                    class="absolute left-0 z-30 mt-2 w-full rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-white/10 dark:bg-[#0f1720]"
                                >
                                    <flux:input
                                        x-ref="search"
                                        x-model="query"
                                        type="search"
                                        placeholder="Buscar marca..."
                                        class="h-10 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0b1118] dark:text-white"
                                    />

                                    <div class="mt-2 max-h-56 overflow-y-auto">
                                        <template x-for="option in filteredOptions" :key="`${option.value}-${option.label}`">
                                            <button
                                                type="button"
                                                @click="choose(option.value)"
                                                class="flex w-full items-center rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
                                                :class="String(option.value) === value ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                                            >
                                                <span class="truncate" x-text="option.label"></span>
                                            </button>
                                        </template>

                                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            No se encontraron marcas.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Categoría</label>
                            <div
                                x-data="filterableSelect({
                                    value: @js((string) ($categoryId ?? '')),
                                    placeholder: 'Todas las categorías',
                                    options: @js(
                                        collect($filterCategories)
                                            ->map(fn ($category): array => ['value' => (string) $category->id, 'label' => $category->name])
                                            ->prepend(['value' => '', 'label' => 'Todas las categorías'])
                                            ->values()
                                    ),
                                })"
                                class="relative"
                                @click.outside="closePanel()"
                            >
                                <input x-ref="input" type="hidden" name="category_id" x-model="value">

                                <button
                                    type="button"
                                    @click="open ? closePanel() : openPanel()"
                                    class="flex h-12 w-full items-center justify-between rounded-xl border border-zinc-200 bg-white px-3 text-left text-sm text-slate-800 shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                >
                                    <span class="truncate" x-text="selectedLabel"></span>
                                    <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" x-bind:class="{ 'rotate-180': open }" />
                                </button>

                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity.scale.origin.top
                                    class="absolute left-0 z-30 mt-2 w-full rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-white/10 dark:bg-[#0f1720]"
                                >
                                    <flux:input
                                        x-ref="search"
                                        x-model="query"
                                        type="search"
                                        placeholder="Buscar categoría..."
                                        class="h-10 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0b1118] dark:text-white"
                                    />

                                    <div class="mt-2 max-h-56 overflow-y-auto">
                                        <template x-for="option in filteredOptions" :key="`${option.value}-${option.label}`">
                                            <button
                                                type="button"
                                                @click="choose(option.value)"
                                                class="flex w-full items-center rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
                                                :class="String(option.value) === value ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                                            >
                                                <span class="truncate" x-text="option.label"></span>
                                            </button>
                                        </template>

                                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            No se encontraron categorías.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Formato</label>
                            <div
                                x-data="filterableSelect({
                                    value: @js((string) ($presentationId ?? '')),
                                    placeholder: 'Todos los formatos',
                                    options: @js(
                                        collect($filterPresentations)
                                            ->map(fn ($presentation): array => ['value' => (string) $presentation->id, 'label' => $presentation->name])
                                            ->prepend(['value' => '', 'label' => 'Todos los formatos'])
                                            ->values()
                                    ),
                                })"
                                class="relative"
                                @click.outside="closePanel()"
                            >
                                <input x-ref="input" type="hidden" name="presentation_id" x-model="value">

                                <button
                                    type="button"
                                    @click="open ? closePanel() : openPanel()"
                                    class="flex h-12 w-full items-center justify-between rounded-xl border border-zinc-200 bg-white px-3 text-left text-sm text-slate-800 shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                >
                                    <span class="truncate" x-text="selectedLabel"></span>
                                    <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" x-bind:class="{ 'rotate-180': open }" />
                                </button>

                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity.scale.origin.top
                                    class="absolute left-0 z-30 mt-2 w-full rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-white/10 dark:bg-[#0f1720]"
                                >
                                    <flux:input
                                        x-ref="search"
                                        x-model="query"
                                        type="search"
                                        placeholder="Buscar formato..."
                                        class="h-10 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0b1118] dark:text-white"
                                    />

                                    <div class="mt-2 max-h-56 overflow-y-auto">
                                        <template x-for="option in filteredOptions" :key="`${option.value}-${option.label}`">
                                            <button
                                                type="button"
                                                @click="choose(option.value)"
                                                class="flex w-full items-center rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
                                                :class="String(option.value) === value ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                                            >
                                                <span class="truncate" x-text="option.label"></span>
                                            </button>
                                        </template>

                                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            No se encontraron formatos.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

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

                <div
                    x-show="importInventoryOpen"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-[70] flex items-start justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:px-4 sm:py-6"
                    @keydown.escape.window="closeImportInventory()"
                    @click.self="closeImportInventory()"
                >
                    <div class="relative w-full max-w-lg overflow-hidden rounded-[24px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200">
                        <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6">
                            <div>
                                <flux:heading size="lg">Importar Excel</flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-500">
                                    Selecciona el archivo .xlsx para reemplazar el inventario actual.
                                </flux:text>
                            </div>

                            <button
                                type="button"
                                class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                                @click="closeImportInventory()"
                                aria-label="Cerrar modal de importación"
                            >
                                <flux:icon name="x-mark" class="size-6" />
                            </button>
                        </div>

                        <form
                            x-ref="importInventoryForm"
                            method="POST"
                            action="{{ route('products.import') }}"
                            enctype="multipart/form-data"
                            class="space-y-5 px-4 py-4 sm:px-6"
                            onsubmit="return confirm('Se eliminara el inventario actual y se reemplazara con el contenido del Excel. Deseas continuar?')"
                        >
                            @csrf

                            <div class="space-y-2">
                                <label for="inventory_file" class="text-sm font-medium text-zinc-700">Archivo Excel</label>
                                <input
                                    id="inventory_file"
                                    name="inventory_file"
                                    type="file"
                                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    required
                                    @change="onImportFileChange($event)"
                                    class="block w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-600 shadow-sm file:mr-3 file:rounded-xl file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200"
                                />
                                <p class="text-xs text-zinc-500">
                                    Formato aceptado: .xlsx
                                </p>
                                <p class="text-sm text-rose-600" x-show="{{ $errors->has('inventory_file') ? 'true' : 'false' }}" x-cloak>
                                    {{ $errors->first('inventory_file') }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                Esta acción reemplazará el inventario actual. Asegúrate de subir el archivo correcto.
                            </div>

                            <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                <button
                                    type="button"
                                    class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                                    @click="closeImportInventory()"
                                >
                                    Cancelar
                                </button>

                                <button
                                    type="submit"
                                    class="inline-flex h-10 items-center justify-center rounded-xl bg-amber-500 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600"
                                >
                                    Importar Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                    @if ($products->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                            <div class="flex size-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <flux:icon name="cube" class="size-8" />
                            </div>

                            <div class="space-y-1">
                                <flux:heading size="lg">{{ $hasFilters ? 'No se encontraron productos' : 'No hay productos aún' }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $hasFilters
                                        ? 'Prueba quitando los filtros o cambia la búsqueda para ver resultados.'
                                        : 'Crea tu primer producto para comenzar a registrar precios, stock y alarmas.' }}
                                </flux:text>
                            </div>

                            <div class="flex flex-wrap items-center justify-center gap-2">
                                @if ($hasFilters)
                                    <flux:button variant="ghost" href="{{ route('products.index') }}" icon="x-mark">
                                        Limpiar filtros
                                    </flux:button>
                                @endif

                                <flux:button variant="primary" icon="plus" type="button" @click="openCreate()">
                                    Nuevo producto
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <div class="space-y-2 px-0 pb-4 pt-0 md:hidden">
                            @foreach ($products as $product)
                                @php
                                    $productActionPayload = [
                                        'id' => $product->id,
                                        'name' => $product->name,
                                    ];

                                    $productEditPayload = [
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
                                    ];
                                @endphp

                                <article
                                    x-data="{ expanded: false }"
                                    class="rounded-none border-x-0 border-y border-zinc-200/80 bg-white shadow-sm first:border-t-0 dark:border-white/10 dark:bg-[#111820] dark:shadow-none"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-start gap-3 px-3 py-3 text-left sm:px-4"
                                        @click="expanded = !expanded"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-base font-semibold leading-tight text-zinc-900 dark:text-white">
                                                {{ $product->name }}
                                            </div>
                                            <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $product->barcode ?: 'Sin código de barras' }}
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <div class="text-sm font-semibold leading-tight text-emerald-600">
                                                S/ {{ number_format((float) $product->public_sale_price, 2) }}
                                            </div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ rtrim(rtrim(number_format((float) $product->current_stock, 2, '.', ''), '0'), '.') }} stock
                                            </div>
                                        </div>

                                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 transition dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-300"
                                            :class="expanded ? 'border-violet-300 text-violet-700 dark:border-emerald-500/50 dark:text-emerald-400' : ''">
                                            <flux:icon name="chevron-down" class="size-4 transition-transform" :class="expanded ? 'rotate-180' : ''" />
                                        </span>
                                    </button>

                                    <div x-show="expanded" x-cloak x-transition class="border-t border-zinc-100 px-3 py-4 sm:px-4 dark:border-white/10">
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div class="rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-white/[0.03]">
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-500">Marca</div>
                                                <div class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $product->brand?->name ?? 'Sin marca' }}</div>
                                            </div>

                                            <div class="rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-white/[0.03]">
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-500">Categoría</div>
                                                <div class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $product->category?->name ?? 'Sin categoría' }}</div>
                                            </div>

                                            <div class="rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-white/[0.03]">
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-500">Formato</div>
                                                <div class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $product->presentation?->name ?? 'Sin formato' }}</div>
                                            </div>

                                            <div class="rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-white/[0.03]">
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-500">Activo</div>
                                                <div class="mt-1 font-medium {{ $product->is_active ? 'text-emerald-600' : 'text-rose-600' }}">
                                                    {{ $product->is_active ? 'Sí' : 'No' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 transition hover:border-sky-300 hover:bg-sky-100"
                                                @click="openStockAdjustment('increase', @js($productActionPayload))"
                                            >
                                                <flux:icon name="plus" class="size-4" />
                                                <span>Stock +</span>
                                            </button>

                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 transition hover:border-sky-300 hover:bg-sky-100"
                                                @click="openStockAdjustment('decrease', @js($productActionPayload))"
                                            >
                                                <flux:icon name="minus" class="size-4" />
                                                <span>Stock -</span>
                                            </button>
                                        </div>

                                        <div class="mt-3 flex gap-2">
                                            <flux:button
                                                class="flex-1"
                                                size="sm"
                                                variant="ghost"
                                                icon="pencil-square"
                                                type="button"
                                                aria-label="Editar producto"
                                                title="Editar producto"
                                                @click="openEdit(@js($productEditPayload))"
                                            />

                                            <flux:button
                                                class="flex-1"
                                                size="sm"
                                                variant="danger"
                                                icon="trash"
                                                type="button"
                                                aria-label="Eliminar producto"
                                                title="Eliminar producto"
                                                @click="deleteProduct({{ $product->id }}, @js($product->name))"
                                            />
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="hidden md:block">
                            <div class="overflow-x-auto">
                                <table class="min-w-full table-auto">
                                    <thead>
                                        <tr class="border-b border-zinc-200 bg-white text-left text-xs font-semibold text-slate-800 dark:border-white/10 dark:bg-[#111820] dark:text-zinc-200">
                                            <th class="px-4 py-4">Producto</th>
                                            <th class="px-4 py-4">Marca</th>
                                            <th class="px-4 py-4">Categoría</th>
                                            <th class="px-4 py-4">Formato</th>
                                            <th class="px-4 py-4">Precio</th>
                                            <th class="px-4 py-4">Stock</th>
                                            <th class="px-4 py-4 text-center">+Stock</th>
                                            <th class="px-4 py-4 text-center">-Stock</th>
                                            <th class="px-4 py-4 text-center">Opciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200">
                                        @foreach ($products as $product)
                                            <tr class="text-sm text-slate-700 dark:text-zinc-300">
                                                <td class="px-4 py-5 align-middle">
                                                    <div class="min-w-0">
                                                        <div class="font-semibold text-slate-900 dark:text-white">{{ $product->name }}</div>
                                                        <div class="mt-1 text-xs text-slate-500 dark:text-zinc-500">
                                                            {{ $product->barcode ?: 'Sin código de barras' }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-5 align-middle">{{ $product->brand?->name ?? 'Sin marca' }}</td>
                                                <td class="px-4 py-5 align-middle">{{ $product->category?->name ?? 'Sin categoría' }}</td>
                                                <td class="px-4 py-5 align-middle">{{ $product->presentation?->name ?? 'Sin formato' }}</td>
                                                <td class="px-4 py-5 align-middle whitespace-nowrap text-slate-600 dark:text-zinc-300">S/ {{ number_format((float) $product->public_sale_price, 2) }}</td>
                                                <td class="px-4 py-5 align-middle whitespace-nowrap text-slate-600 dark:text-zinc-300">{{ rtrim(rtrim(number_format((float) $product->current_stock, 2, '.', ''), '0'), '.') }}</td>
                                                <td class="px-4 py-5 align-middle text-center">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm font-medium text-sky-600 transition hover:text-sky-700"
                                                        @click="openStockAdjustment('increase', @js([
                                                            'id' => $product->id,
                                                            'name' => $product->name,
                                                        ]))"
                                                    >
                                                        <flux:icon name="plus" class="size-4" />
                                                        <span>Stock</span>
                                                    </button>
                                                </td>
                                                <td class="px-4 py-5 align-middle text-center">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm font-medium text-sky-600 transition hover:text-sky-700"
                                                        @click="openStockAdjustment('decrease', @js([
                                                            'id' => $product->id,
                                                            'name' => $product->name,
                                                        ]))"
                                                    >
                                                        <flux:icon name="minus" class="size-4" />
                                                        <span>Stock</span>
                                                    </button>
                                                </td>
                                                <td class="px-4 py-5 align-middle text-center">
                                                    <div class="inline-flex items-center justify-center gap-2">
                                                        <button
                                                            type="button"
                                                            class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-slate-500 shadow-sm transition hover:border-zinc-300 hover:text-slate-700 dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-300 dark:shadow-none dark:hover:border-white/20 dark:hover:text-white"
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
                                                        >
                                                            <flux:icon name="pencil-square" class="size-4" />
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="inline-flex size-9 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm transition hover:bg-rose-600"
                                                            aria-label="Eliminar producto"
                                                            title="Eliminar producto"
                                                            @click="deleteProduct({{ $product->id }}, @js($product->name))"
                                                        >
                                                            <flux:icon name="trash" class="size-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-zinc-200 px-4 py-4 md:flex-row md:items-center md:justify-between dark:border-white/10">
                            <div class="text-sm text-slate-500 dark:text-zinc-400">
                                Mostrando {{ $products->firstItem() ?? 0 }} a {{ $products->lastItem() ?? 0 }} de {{ $products->total() }} productos
                            </div>

                            <div class="flex items-center gap-3">
                                <div>
                                    {{ $products->onEachSide(1)->links('vendor.pagination.products-table') }}
                                </div>

                                <form method="GET" action="{{ route('products.index') }}" class="min-w-[8rem]">
                                    <input type="hidden" name="q" value="{{ $search }}">
                                    <input type="hidden" name="brand_id" value="{{ $brandId }}">
                                    <input type="hidden" name="category_id" value="{{ $categoryId }}">
                                    <input type="hidden" name="presentation_id" value="{{ $presentationId }}">
                                    <flux:select name="per_page" onchange="this.form.submit()" class="h-11 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                                        @foreach ([10, 25, 50] as $size)
                                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} por página</option>
                                        @endforeach
                                    </flux:select>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div
            x-show="isOpen"
            x-transition.opacity
            class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-950/50 px-0 py-0 backdrop-blur-[2px] sm:items-center sm:px-3 sm:py-6"
            @keydown.escape.window="closeModal()"
            @click.self="closeModal()"
        >
            <div class="relative flex h-full w-full max-w-[1500px] max-h-[100vh] flex-col overflow-hidden rounded-none bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200 sm:max-h-[92vh] sm:rounded-[30px]">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6 sm:py-5">
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

                <div class="border-b border-violet-200 px-0 pt-3 sm:px-6 sm:pt-4">
                    <div class="flex gap-2 overflow-x-auto px-4 pb-1 sm:flex-wrap sm:px-0">
                        <button
                            type="button"
                            class="shrink-0 rounded-t-2xl border px-3 py-2.5 text-sm font-medium transition sm:px-4 sm:py-3"
                            :class="activeTab === 'basic'
                                ? 'border-violet-300 bg-violet-50 text-violet-700 shadow-sm'
                                : 'border-transparent bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                            @click="activeTab = 'basic'"
                        >
                            Datos básicos
                        </button>

                        <button
                            type="button"
                            class="shrink-0 rounded-t-2xl border px-3 py-2.5 text-sm font-medium transition sm:px-4 sm:py-3"
                            :class="activeTab === 'advanced'
                                ? 'border-violet-300 bg-violet-50 text-violet-700 shadow-sm'
                                : 'border-transparent bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                            @click="activeTab = 'advanced'"
                        >
                            Opciones avanzadas
                        </button>

                        <button
                            type="button"
                            class="shrink-0 rounded-t-2xl border px-3 py-2.5 text-sm font-medium transition sm:px-4 sm:py-3"
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
                            class="shrink-0 rounded-t-2xl border px-3 py-2.5 text-sm font-medium transition sm:px-4 sm:py-3"
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
                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
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
                                        <template x-if="brands.length > 7">
                                            <div
                                                x-data="filterableSelect({
                                                    value: '',
                                                    placeholder: 'Selecciona una marca',
                                                    submitOnChoose: false,
                                                    options: brands.map((brand) => ({ value: String(brand.id), label: brand.name })),
                                                    onChoose: (value) => { form.brand_id = value; },
                                                })"
                                                x-effect="value = String(form.brand_id ?? ''); options = brands.map((brand) => ({ value: String(brand.id), label: brand.name }));"
                                                class="space-y-1.5"
                                                @click.outside="closePanel()"
                                            >
                                                <flux:label>Marca *</flux:label>
                                                <input x-ref="input" type="hidden" x-model="form.brand_id">

                                                <button
                                                    type="button"
                                                    @click="open ? closePanel() : openPanel()"
                                                    class="flex h-12 w-full items-center justify-between rounded-2xl border border-zinc-200 bg-white px-3 text-left text-sm text-zinc-900 dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                                >
                                                    <span class="truncate" x-text="selectedLabel"></span>
                                                    <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" x-bind:class="{ 'rotate-180': open }" />
                                                </button>

                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    x-transition.opacity.scale.origin.top
                                                    class="relative z-30 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-white/10 dark:bg-[#0f1720]"
                                                >
                                                    <flux:input
                                                        x-ref="search"
                                                        x-model="query"
                                                        type="search"
                                                        placeholder="Buscar marca..."
                                                        class="h-10 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0b1118] dark:text-white"
                                                    />

                                                    <div class="mt-2 max-h-56 overflow-y-auto">
                                                        <template x-for="option in filteredOptions" :key="`${option.value}-${option.label}`">
                                                            <button
                                                                type="button"
                                                                @click="choose(option.value)"
                                                                class="flex w-full items-center rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
                                                                :class="String(option.value) === String(form.brand_id ?? '') ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                                                            >
                                                                <span class="truncate" x-text="option.label"></span>
                                                            </button>
                                                        </template>

                                                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                            No se encontraron marcas.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="brands.length <= 7">
                                            <flux:select x-model="form.brand_id" label="Marca *" class="rounded-2xl">
                                                <option value="">Selecciona una marca</option>
                                                <template x-for="brand in brands" :key="brand.id">
                                                    <option :value="brand.id" x-text="brand.name"></option>
                                                </template>
                                            </flux:select>
                                        </template>
                                        <button type="button" class="text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-4 hover:text-violet-700" @click="openQuickCreate('brands')">
                                            + Nueva marca
                                        </button>
                                        <p class="text-sm text-rose-600" x-show="errors.brand_id" x-text="errors.brand_id" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <template x-if="categories.length > 7">
                                            <div
                                                x-data="filterableSelect({
                                                    value: '',
                                                    placeholder: 'Selecciona una categoría',
                                                    submitOnChoose: false,
                                                    options: categories.map((category) => ({ value: String(category.id), label: category.name })),
                                                    onChoose: (value) => { form.category_id = value; },
                                                })"
                                                x-effect="value = String(form.category_id ?? ''); options = categories.map((category) => ({ value: String(category.id), label: category.name }));"
                                                class="space-y-1.5"
                                                @click.outside="closePanel()"
                                            >
                                                <flux:label>Categoría *</flux:label>
                                                <input x-ref="input" type="hidden" x-model="form.category_id">

                                                <button
                                                    type="button"
                                                    @click="open ? closePanel() : openPanel()"
                                                    class="flex h-12 w-full items-center justify-between rounded-2xl border border-zinc-200 bg-white px-3 text-left text-sm text-zinc-900 dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                                >
                                                    <span class="truncate" x-text="selectedLabel"></span>
                                                    <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" x-bind:class="{ 'rotate-180': open }" />
                                                </button>

                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    x-transition.opacity.scale.origin.top
                                                    class="relative z-30 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-white/10 dark:bg-[#0f1720]"
                                                >
                                                    <flux:input
                                                        x-ref="search"
                                                        x-model="query"
                                                        type="search"
                                                        placeholder="Buscar categoría..."
                                                        class="h-10 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0b1118] dark:text-white"
                                                    />

                                                    <div class="mt-2 max-h-56 overflow-y-auto">
                                                        <template x-for="option in filteredOptions" :key="`${option.value}-${option.label}`">
                                                            <button
                                                                type="button"
                                                                @click="choose(option.value)"
                                                                class="flex w-full items-center rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
                                                                :class="String(option.value) === String(form.category_id ?? '') ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                                                            >
                                                                <span class="truncate" x-text="option.label"></span>
                                                            </button>
                                                        </template>

                                                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                            No se encontraron categorías.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="categories.length <= 7">
                                            <flux:select x-model="form.category_id" label="Categoría *" class="rounded-2xl">
                                                <option value="">Selecciona una categoría</option>
                                                <template x-for="category in categories" :key="category.id">
                                                    <option :value="category.id" x-text="category.name"></option>
                                                </template>
                                            </flux:select>
                                        </template>
                                        <button type="button" class="text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-4 hover:text-violet-700" @click="openQuickCreate('categories')">
                                            + Nueva categoría
                                        </button>
                                        <p class="text-sm text-rose-600" x-show="errors.category_id" x-text="errors.category_id" x-cloak></p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <template x-if="presentations.length > 7">
                                            <div
                                                x-data="filterableSelect({
                                                    value: '',
                                                    placeholder: 'Selecciona un formato',
                                                    submitOnChoose: false,
                                                    options: presentations.map((presentation) => ({ value: String(presentation.id), label: presentation.name })),
                                                    onChoose: (value) => { form.presentation_id = value; },
                                                })"
                                                x-effect="value = String(form.presentation_id ?? ''); options = presentations.map((presentation) => ({ value: String(presentation.id), label: presentation.name }));"
                                                class="space-y-1.5"
                                                @click.outside="closePanel()"
                                            >
                                                <flux:label>Formato/Presentación *</flux:label>
                                                <input x-ref="input" type="hidden" x-model="form.presentation_id">

                                                <button
                                                    type="button"
                                                    @click="open ? closePanel() : openPanel()"
                                                    class="flex h-12 w-full items-center justify-between rounded-2xl border border-zinc-200 bg-white px-3 text-left text-sm text-zinc-900 dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                                >
                                                    <span class="truncate" x-text="selectedLabel"></span>
                                                    <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" x-bind:class="{ 'rotate-180': open }" />
                                                </button>

                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    x-transition.opacity.scale.origin.top
                                                    class="relative z-30 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-white/10 dark:bg-[#0f1720]"
                                                >
                                                    <flux:input
                                                        x-ref="search"
                                                        x-model="query"
                                                        type="search"
                                                        placeholder="Buscar formato..."
                                                        class="h-10 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0b1118] dark:text-white"
                                                    />

                                                    <div class="mt-2 max-h-56 overflow-y-auto">
                                                        <template x-for="option in filteredOptions" :key="`${option.value}-${option.label}`">
                                                            <button
                                                                type="button"
                                                                @click="choose(option.value)"
                                                                class="flex w-full items-center rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
                                                                :class="String(option.value) === String(form.presentation_id ?? '') ? 'bg-zinc-100 font-medium dark:bg-white/10' : ''"
                                                            >
                                                                <span class="truncate" x-text="option.label"></span>
                                                            </button>
                                                        </template>

                                                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                            No se encontraron formatos.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="presentations.length <= 7">
                                            <flux:select x-model="form.presentation_id" label="Formato/Presentación *" class="rounded-2xl">
                                                <option value="">Selecciona un formato</option>
                                                <template x-for="presentation in presentations" :key="presentation.id">
                                                    <option :value="presentation.id" x-text="presentation.name"></option>
                                                </template>
                                            </flux:select>
                                        </template>
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

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="text-xs text-zinc-500 sm:text-sm" x-text="footerMessage()"></div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button
                                type="button"
                                class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200 sm:w-auto"
                                @click="closeModal()"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                x-bind:disabled="saving || !canSubmitProduct()"
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
