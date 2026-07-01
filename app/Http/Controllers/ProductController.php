<?php

namespace App\Http\Controllers;

use App\Actions\Products\AdjustProductStockAction;
use App\Actions\Products\CreateProductSaleAction;
use App\Actions\Products\ImportInventoryFromExcelAction;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\ProductStockMovement;
use App\Models\SaleItem;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));

        $products = Product::query()
            ->with(['brand', 'category', 'presentation'])
            ->search($search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'search' => $search,
            'products' => $products,
            'inventoryConfig' => [
                'csrf' => csrf_token(),
                'endpoints' => [
                    'store' => route('products.store'),
                    'updateBase' => url('/products'),
                    'destroyBase' => url('/products'),
                    'brands' => route('product-brands.store'),
                    'categories' => route('product-categories.store'),
                    'presentations' => route('product-presentations.store'),
                    'movementDetail' => url('/products/movimientos'),
                    'movementStore' => url('/products/movimientos'),
                ],
                'catalogs' => [
                    'brands' => $this->catalogPayload(ProductBrand::query()->orderBy('name')->get()),
                    'categories' => $this->catalogPayload(ProductCategory::query()->orderBy('name')->get()),
                    'presentations' => $this->catalogPayload(ProductPresentation::query()->orderBy('name')->get()),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $product = Product::query()->create($this->validatedProductData($request));

        return $this->respond($request, 'Producto creado correctamente.', 201, [
            'product' => $this->productPayload($product->load(['brand', 'category', 'presentation'])),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $product->update($this->validatedProductData($request));
        $product = $product->fresh(['brand', 'category', 'presentation']);

        return $this->respond($request, 'Producto actualizado correctamente.', 200, [
            'product' => $product instanceof Product ? $this->productPayload($product) : [],
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $productName = $product->name;
        $product->delete();

        return $this->respond($request, "Producto {$productName} eliminado correctamente.");
    }

    public function import(Request $request, ImportInventoryFromExcelAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'inventory_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        try {
            $summary = $action->handle($validated['inventory_file']);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('products.index')
                ->with('error', $exception->getMessage());
        }

        $message = sprintf(
            'Inventario importado correctamente. Se cargaron %d productos, %d marcas, %d categorias y %d presentaciones.',
            $summary['products'],
            $summary['brands'],
            $summary['categories'],
            $summary['presentations'],
        );

        return redirect()
            ->route('products.index')
            ->with('success', $message);
    }

    public function salesIndex(Request $request): View
    {
        $hasFilters = $request->filled('from')
            || $request->filled('to')
            || $request->filled('product_status')
            || $request->filled('product_id')
            || $request->filled('seller_id');

        [$from, $to, $branchId, $productId] = $this->resolveRangeFilters($request);
        $from ??= now()->subDays(30)->toDateString();
        $to ??= now()->toDateString();
        $branchId = null;
        $productStatus = $this->resolveProductStatusFilter($request);
        $sellerKey = $this->resolveSellerFilter($request);
        $detailTab = $this->resolveSalesDetailTab($request);

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with(['presentation', 'brand'])
            ->orderBy('name')
            ->get();

        $salesRows = $this->unifiedProductSalesRows($from, $to, $branchId, $productId, $productStatus, $sellerKey);
        $productBreakdown = $this->salesByProduct($salesRows);
        $sellerBreakdown = $this->salesBySeller($salesRows);

        $salesSummary = [
            'revenue' => (float) $productBreakdown->sum('revenue'),
            'units_sold' => (float) $productBreakdown->sum('units_sold'),
        ];

        $highestProduct = $productBreakdown->sortByDesc('revenue')->first();
        $lowestProduct = $productBreakdown->sortBy('revenue')->first();
        $highestSeller = $sellerBreakdown->sortByDesc('revenue')->first();
        $lowestSeller = $sellerBreakdown->sortBy('revenue')->first();

        return view('products.sales', [
            'from' => $from,
            'to' => $to,
            'branchId' => $branchId,
            'productId' => $productId,
            'sellerKey' => $sellerKey,
            'hasFilters' => $hasFilters,
            'detailTab' => $detailTab,
            'productStatus' => $productStatus,
            'branches' => $this->branchPayload($branches),
            'products' => $this->productCatalogPayload($products),
            'salesSummary' => $salesSummary,
            'productBreakdown' => $productBreakdown,
            'sellerBreakdown' => $sellerBreakdown,
            'highlights' => [
                'highest_product' => $highestProduct,
                'lowest_product' => $lowestProduct,
                'highest_seller' => $highestSeller,
                'lowest_seller' => $lowestSeller,
            ],
            'salesConfig' => [
                'csrf' => csrf_token(),
                'endpoints' => [
                    'store' => route('products.sales.store'),
                ],
                'branches' => $this->branchPayload($branches),
                'products' => $this->productCatalogPayload($products),
                'filters' => [
                    'from' => $from,
                    'to' => $to,
                    'product_id' => $productId,
                    'seller_key' => $sellerKey,
                    'product_status' => $productStatus,
                ],
            ],
        ]);
    }

    public function salesExport(Request $request): StreamedResponse
    {
        [$from, $to, $branchId, $productId] = $this->resolveRangeFilters($request);
        $from ??= now()->subDays(30)->toDateString();
        $to ??= now()->toDateString();
        $branchId = null;

        $productStatus = $this->resolveProductStatusFilter($request);
        $sellerKey = $this->resolveSellerFilter($request);
        $detailTab = $this->resolveSalesDetailTab($request);
        $salesRows = $this->unifiedProductSalesRows($from, $to, $branchId, $productId, $productStatus, $sellerKey);

        $rows = $detailTab === 'vendors'
            ? $this->salesBySeller($salesRows)
            : $this->salesByProduct($salesRows);

        $headers = $detailTab === 'vendors'
            ? ['Vendedor', 'Tipo de usuario', 'Unidades vendidas', 'Recaudacion']
            : ['Producto', 'Formato/Presentacion', 'Unidades vendidas', 'Recaudacion'];

        $filename = sprintf(
            'ventas-productos-%s-%s-%s.csv',
            $detailTab,
            $from,
            $to,
        );

        return response()->streamDownload(function () use ($rows, $headers, $detailTab): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $detailTab === 'vendors'
                    ? [
                        $row['seller_name'],
                        $row['user_type'],
                        $this->exportNumeric($row['units_sold']),
                        $this->exportNumeric($row['revenue']),
                    ]
                    : [
                        $row['product_name'],
                        $row['presentation_name'],
                        $this->exportNumeric($row['units_sold']),
                        $this->exportNumeric($row['revenue']),
                    ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function movementsIndex(Request $request): View
    {
        $hasFilters = $request->filled('from')
            || $request->filled('to')
            || $request->filled('branch_id')
            || $request->filled('product_id');

        [$from, $to, $branchId, $productId] = $this->resolveRangeFilters($request);
        $from ??= now()->subDays(30)->toDateString();
        $to ??= now()->toDateString();

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with(['presentation', 'brand'])
            ->orderBy('name')
            ->get();

        $movementsQuery = $this->movementsQuery($from, $to, $branchId, $productId);

        $movements = (clone $movementsQuery)
            ->with(['product.presentation', 'branch', 'user'])
            ->latest('occurred_at')
            ->paginate(10)
            ->withQueryString();

        $movementSummary = [
            'positive' => (int) (clone $movementsQuery)->where('quantity_delta', '>', 0)->count(),
            'negative' => (int) (clone $movementsQuery)->where('quantity_delta', '<', 0)->count(),
            'total' => (int) (clone $movementsQuery)->count(),
            'products' => (int) (clone $movementsQuery)->select('product_id')->distinct()->count('product_id'),
        ];

        return view('products.movements', [
            'from' => $from,
            'to' => $to,
            'branchId' => $branchId,
            'productId' => $productId,
            'hasFilters' => $hasFilters,
            'movements' => $movements,
            'branches' => $this->branchPayload($branches),
            'products' => $this->productCatalogPayload($products),
            'movementSummary' => $movementSummary,
            'movementConfig' => [
                'csrf' => csrf_token(),
                'endpoints' => [
                    'detail' => url('/products/movimientos'),
                    'store' => url('/products/movimientos'),
                ],
            ],
        ]);
    }

    public function salesStore(Request $request, CreateProductSaleAction $action): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $sale = $action->handle($request->user(), $data);

        return $this->respond($request, 'Venta registrada correctamente.', 201, [
            'sale' => $this->salePayload($sale),
        ]);
    }

    public function movementShow(Product $product): JsonResponse
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $product->load(['branchStocks.branch', 'stockMovements.branch', 'stockMovements.user']);

        return response()->json([
            'product' => $this->productDetailPayload($product),
            'branches' => $this->branchPayload($branches),
            'branchStocks' => $this->movementBranchStockPayloads($product, $branches),
            'history' => $product->stockMovements()
                ->with(['branch', 'user'])
                ->where('occurred_at', '>=', now()->subYear())
                ->latest('occurred_at')
                ->limit(50)
                ->get()
                ->map(fn (ProductStockMovement $movement): array => $this->movementPayload($movement))
                ->values()
                ->all(),
        ]);
    }

    public function movementStore(Request $request, Product $product, AdjustProductStockAction $action): JsonResponse|RedirectResponse
    {
        if ($request->has('stock_by_branch')) {
            $data = $request->validate([
                'stock_by_branch' => ['required', 'array'],
                'stock_by_branch.*' => ['nullable', 'numeric', 'min:0'],
            ]);

            $action->handle($request->user(), $product, $data['stock_by_branch']);

            return $this->respond($request, 'Stock actualizado correctamente.');
        }

        $data = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'adjustment_type' => ['required', Rule::in(['increase', 'decrease'])],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'comment' => ['nullable', 'string'],
        ]);

        $branch = Branch::query()->findOrFail((int) $data['branch_id']);

        $action->handleSingleBranchAdjustment(
            $request->user(),
            $product,
            $branch,
            (float) $data['quantity'],
            (string) $data['adjustment_type'],
            $this->nullableString($data['comment'] ?? null),
        );

        $message = $data['adjustment_type'] === 'increase'
            ? 'Stock aumentado correctamente.'
            : 'Stock reducido correctamente.';

        return $this->respond($request, $message);
    }

    public function storeBrand(Request $request): JsonResponse|RedirectResponse
    {
        return $this->storeCatalogEntry($request, ProductBrand::class, 'Marca creada correctamente.');
    }

    public function storeCategory(Request $request): JsonResponse|RedirectResponse
    {
        return $this->storeCatalogEntry($request, ProductCategory::class, 'Categoría creada correctamente.');
    }

    public function storePresentation(Request $request): JsonResponse|RedirectResponse
    {
        return $this->storeCatalogEntry($request, ProductPresentation::class, 'Formato creado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProductData(Request $request): array
    {
        $tenantId = tenant()?->id;

        $scopeTenant = static function (string $table) use ($tenantId): Exists {
            $rule = Rule::exists($table, 'id');

            if (is_string($tenantId) && $tenantId !== '') {
                $rule->where(static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId));
            }

            return $rule;
        };

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'brand_id' => ['required', 'integer', $scopeTenant('product_brands')],
            'category_id' => ['required', 'integer', $scopeTenant('product_categories')],
            'presentation_id' => ['required', 'integer', $scopeTenant('product_presentations')],
            'public_sale_price' => ['nullable', 'numeric', 'min:0'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'internal_sale_price' => ['nullable', 'numeric', 'min:0'],
            'sale_commission' => ['nullable', 'numeric', 'min:0'],
            'commission_type' => ['required', Rule::in(['percent', 'amount'])],
            'includes_tax' => ['boolean'],
            'description' => ['nullable', 'string'],
            'stock_alarm_enabled' => ['boolean'],
            'stock_alarm_limit' => [
                Rule::requiredIf(static fn (): bool => $request->boolean('stock_alarm_enabled')),
                'nullable',
                'numeric',
                'min:0',
            ],
            'stock_alarm_emails' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $emails = array_values(array_filter(array_map('trim', explode(',', (string) $value))));

                    foreach ($emails as $email) {
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail('Ingresa correos válidos separados por coma.');

                            return;
                        }
                    }
                },
            ],
            'is_active' => ['boolean'],
        ]);

        return [
            'name' => trim((string) $validated['name']),
            'barcode' => $this->nullableString($validated['barcode'] ?? null),
            'brand_id' => (int) $validated['brand_id'],
            'category_id' => (int) $validated['category_id'],
            'presentation_id' => (int) $validated['presentation_id'],
            'public_sale_price' => $validated['public_sale_price'] ?? 0,
            'current_stock' => $validated['current_stock'] ?? 0,
            'purchase_cost' => $validated['purchase_cost'] ?? 0,
            'internal_sale_price' => $validated['internal_sale_price'] ?? 0,
            'sale_commission' => $validated['sale_commission'] ?? 0,
            'commission_type' => $validated['commission_type'],
            'includes_tax' => (bool) ($validated['includes_tax'] ?? false),
            'description' => $this->nullableString($validated['description'] ?? null),
            'stock_alarm_enabled' => (bool) ($validated['stock_alarm_enabled'] ?? false),
            'stock_alarm_limit' => $validated['stock_alarm_limit'] ?? null,
            'stock_alarm_emails' => $this->normalizeEmailList($validated['stock_alarm_emails'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     */
    private function storeCatalogEntry(Request $request, string $modelClass, string $message): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** @var TModel $record */
        $record = $modelClass::query()->firstOrCreate([
            'name' => trim((string) $data['name']),
        ], [
            'is_active' => true,
        ]);

        return $this->respond($request, $message, 201, [
            'record' => $this->catalogItemPayload($record),
        ]);
    }

    /**
     * @return array{0:?string, 1:?string, 2:?int, 3:?int}
     */
    private function resolveRangeFilters(Request $request): array
    {
        $from = $request->string('from')->trim()->toString();
        $to = $request->string('to')->trim()->toString();
        $branchId = $request->integer('branch_id') ?: null;
        $productId = $request->integer('product_id') ?: null;

        return [
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
            $branchId,
            $productId,
        ];
    }

    /**
     * @return EloquentBuilder<ProductSale>
     */
    private function salesQuery(string $from, string $to, ?int $branchId, ?int $productId): EloquentBuilder
    {
        $query = ProductSale::query();
        $this->applySalesFilters($query, $from, $to, $branchId, $productId);

        return $query;
    }

    /**
     * @param  EloquentBuilder<ProductSale>  $query
     * @return EloquentBuilder<ProductSale>
     */
    private function applySalesFilters(EloquentBuilder $query, string $from, string $to, ?int $branchId, ?int $productId): EloquentBuilder
    {
        return $query
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->when($branchId !== null, fn (EloquentBuilder $saleQuery): EloquentBuilder => $saleQuery->where('branch_id', $branchId))
            ->when($productId !== null, fn (EloquentBuilder $saleQuery): EloquentBuilder => $saleQuery->whereHas('items', fn (EloquentBuilder $itemQuery): EloquentBuilder => $itemQuery->where('product_id', $productId)));
    }

    private function resolveProductStatusFilter(Request $request): string
    {
        $status = $request->string('product_status')->trim()->toString();

        return in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'active';
    }

    private function resolveSellerFilter(Request $request): ?string
    {
        $sellerKey = $request->string('seller_key')->trim()->toString();

        return $sellerKey !== '' ? $sellerKey : null;
    }

    private function resolveSalesDetailTab(Request $request): string
    {
        $detail = $request->string('detail')->trim()->toString();

        return in_array($detail, ['products', 'vendors'], true) ? $detail : 'products';
    }

    /**
     * @return Collection<int, array{
     *     product_id:int,
     *     product_name:string,
     *     presentation_name:string,
     *     seller_key:string,
     *     seller_name:string,
     *     user_type:string,
     *     sold_at:string,
     *     units_sold:float,
     *     revenue:float
     * }>
     */
    private function unifiedProductSalesRows(
        string $from,
        string $to,
        ?int $branchId,
        ?int $productId,
        string $productStatus,
        ?string $sellerKey,
    ): Collection {
        $directRows = ProductSale::query()
            ->with(['user.role', 'items.product.presentation'])
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->when($branchId !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('branch_id', $branchId))
            ->when($productId !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->whereHas('items', fn (EloquentBuilder $itemQuery): EloquentBuilder => $itemQuery->where('product_id', $productId)))
            ->when($productStatus !== 'all', fn (EloquentBuilder $query): EloquentBuilder => $query->whereHas('items.product', fn (EloquentBuilder $productQuery): EloquentBuilder => $productQuery->where('is_active', $productStatus === 'active')))
            ->get()
            ->flatMap(function (ProductSale $sale): Collection {
                return $sale->items->map(function (ProductSaleItem $item) use ($sale): array {
                    $product = $item->product;
                    $sellerName = (string) data_get($sale, 'user.name', 'Sin vendedor');
                    $sellerType = (string) data_get($sale, 'user.role.name', 'Usuario');

                    return [
                        'product_id' => (int) $item->product_id,
                        'product_name' => (string) data_get($product, 'name', 'Producto'),
                        'presentation_name' => (string) data_get($product, 'presentation.name', 'Sin formato'),
                        'seller_key' => 'user:'.$sale->user_id,
                        'seller_name' => $sellerName,
                        'user_type' => $sellerType,
                        'sold_at' => (string) $sale->sold_at,
                        'units_sold' => (float) $item->quantity,
                        'revenue' => (float) $item->subtotal,
                    ];
                });
            });

        $checkoutRows = SaleItem::query()
            ->with(['sale.user.role', 'product.presentation'])
            ->where('item_type', 'product')
            ->whereHas('sale', function (EloquentBuilder $query) use ($from, $to, $branchId): EloquentBuilder {
                return $query
                    ->whereDate('sold_at', '>=', $from)
                    ->whereDate('sold_at', '<=', $to)
                    ->when($branchId !== null, fn (EloquentBuilder $saleQuery): EloquentBuilder => $saleQuery->where('branch_id', $branchId));
            })
            ->when($productId !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('product_id', $productId))
            ->when($productStatus !== 'all', fn (EloquentBuilder $query): EloquentBuilder => $query->whereHas('product', fn (EloquentBuilder $productQuery): EloquentBuilder => $productQuery->where('is_active', $productStatus === 'active')))
            ->get()
            ->map(function (SaleItem $item): array {
                $sale = $item->sale;
                $product = $item->product;
                $professionalId = data_get($item->meta, 'professional_id');
                $professionalName = trim((string) data_get($item->meta, 'professional_name', ''));
                $sellerKey = $professionalId !== null ? 'professional:'.$professionalId : 'user:'.$sale?->user_id;
                $sellerName = $professionalName !== ''
                    ? $professionalName
                    : (string) data_get($sale, 'user.name', 'Sin vendedor');
                $sellerType = $professionalName !== ''
                    ? 'Profesional'
                    : (string) data_get($sale, 'user.role.name', 'Usuario');

                return [
                    'product_id' => (int) $item->product_id,
                    'product_name' => (string) data_get($product, 'name', 'Producto'),
                    'presentation_name' => (string) data_get($product, 'presentation.name', 'Sin formato'),
                    'seller_key' => $sellerKey,
                    'seller_name' => $sellerName,
                    'user_type' => $sellerType,
                    'sold_at' => (string) $sale?->sold_at,
                    'units_sold' => (float) $item->quantity,
                    'revenue' => (float) $item->subtotal,
                ];
            });

        return $directRows
            ->merge($checkoutRows)
            ->when($sellerKey !== null, fn (Collection $rows): Collection => $rows->where('seller_key', $sellerKey))
            ->values();
    }

    /**
     * @param  Collection<int, array{
     *     product_id:int,
     *     product_name:string,
     *     presentation_name:string,
     *     seller_key:string,
     *     seller_name:string,
     *     user_type:string,
     *     sold_at:string,
     *     units_sold:float,
     *     revenue:float
     * }>
     */
    private function salesByProduct(Collection $rows): Collection
    {
        return $rows
            ->groupBy('product_id')
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'product_id' => (int) $first['product_id'],
                    'product_name' => (string) $first['product_name'],
                    'presentation_name' => (string) $first['presentation_name'],
                    'units_sold' => (float) $group->sum('units_sold'),
                    'revenue' => (float) $group->sum('revenue'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    /**
     * @param  Collection<int, array{
     *     product_id:int,
     *     product_name:string,
     *     presentation_name:string,
     *     seller_key:string,
     *     seller_name:string,
     *     user_type:string,
     *     sold_at:string,
     *     units_sold:float,
     *     revenue:float
     * }>
     */
    private function salesBySeller(Collection $rows): Collection
    {
        return $rows
            ->groupBy('seller_key')
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'seller_key' => (string) $first['seller_key'],
                    'seller_name' => (string) $first['seller_name'],
                    'user_type' => (string) $first['user_type'],
                    'units_sold' => (float) $group->sum('units_sold'),
                    'revenue' => (float) $group->sum('revenue'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    private function exportNumeric(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0, '.', '')
            : number_format($value, 2, '.', '');
    }

    /**
     * @return EloquentBuilder<ProductStockMovement>
     */
    private function movementsQuery(string $from, string $to, ?int $branchId, ?int $productId): EloquentBuilder
    {
        return ProductStockMovement::query()
            ->whereDate('occurred_at', '>=', $from)
            ->whereDate('occurred_at', '<=', $to)
            ->when($branchId !== null, fn (EloquentBuilder $movementQuery): EloquentBuilder => $movementQuery->where('branch_id', $branchId))
            ->when($productId !== null, fn (EloquentBuilder $movementQuery): EloquentBuilder => $movementQuery->where('product_id', $productId));
    }

    /**
     * @param  iterable<Branch>  $branches
     * @return array<int, array{id:int, name:string, is_active:bool}>
     */
    private function branchPayload(iterable $branches): array
    {
        $payload = [];

        foreach ($branches as $branch) {
            $payload[] = [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'is_active' => (bool) $branch->is_active,
            ];
        }

        return $payload;
    }

    /**
     * @param  iterable<Product>  $products
     * @return array<int, array{id:int, name:string, brand:string, presentation:string, public_sale_price:string, current_stock:string, is_active:bool}>
     */
    private function productCatalogPayload(iterable $products): array
    {
        $payload = [];

        foreach ($products as $product) {
            $payload[] = $this->productSummaryPayload($product);
        }

        return $payload;
    }

    /**
     * @return array{id:int, name:string, brand:string, presentation:string, public_sale_price:string, current_stock:string, is_active:bool}
     */
    private function productSummaryPayload(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'brand' => (string) data_get($product, 'brand.name', ''),
            'presentation' => (string) data_get($product, 'presentation.name', ''),
            'public_sale_price' => (string) $product->public_sale_price,
            'current_stock' => (string) $product->current_stock,
            'is_active' => (bool) $product->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productDetailPayload(Product $product): array
    {
        return [
            ...$this->productSummaryPayload($product),
            'barcode' => $product->barcode ?? '',
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'presentation_id' => $product->presentation_id,
            'description' => $product->description ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function salePayload(ProductSale $sale): array
    {
        $item = $sale->items->first();

        return [
            'id' => $sale->id,
            'branch_id' => $sale->branch_id,
            'branch' => (string) data_get($sale, 'branch.name', ''),
            'sold_at' => CarbonImmutable::parse((string) $sale->sold_at)->toDateTimeString(),
            'total' => (string) $sale->total,
            'notes' => $sale->notes ?? '',
            'user' => (string) data_get($sale, 'user.name', ''),
            'item' => $item !== null ? [
                'product_id' => $item->product_id,
                'product' => (string) data_get($item, 'product.name', ''),
                'presentation' => (string) data_get($item, 'product.presentation.name', ''),
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'subtotal' => (string) $item->subtotal,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function movementPayload(ProductStockMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'branch_id' => $movement->branch_id,
            'branch' => (string) data_get($movement, 'branch.name', ''),
            'product_id' => $movement->product_id,
            'occurred_at' => CarbonImmutable::parse((string) $movement->occurred_at)->format('d/m/Y H:i'),
            'movement_type' => $movement->movement_type,
            'previous_stock' => (string) $movement->previous_stock,
            'quantity_delta' => (string) $movement->quantity_delta,
            'new_stock' => (string) $movement->new_stock,
            'reason' => $movement->reason ?? '',
            'comment' => $movement->comment ?? '',
            'user' => (string) data_get($movement, 'user.name', 'N/A'),
            'direction' => (float) $movement->quantity_delta >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * @param  iterable<Branch>  $branches
     * @return array<int, array{id:int, name:string, current_stock:string}>
     */
    private function movementBranchStockPayloads(Product $product, iterable $branches): array
    {
        $branchStocks = $product->branchStocks->keyBy('branch_id');
        $hasBranchStocks = $branchStocks->isNotEmpty();
        $payload = [];

        foreach ($branches as $index => $branch) {
            /** @var ProductBranchStock|null $stock */
            $stock = $branchStocks->get($branch->id);

            $payload[] = [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'current_stock' => $stock !== null
                    ? (string) $stock->current_stock
                    : ($hasBranchStocks || $index > 0 ? '0.00' : (string) $product->current_stock),
            ];
        }

        return $payload;
    }

    /**
     * @param  iterable<ProductBrand|ProductCategory|ProductPresentation>  $records
     * @return array<int, array{id: int, name: string, is_active: bool}>
     */
    private function catalogPayload(iterable $records): array
    {
        $payload = [];

        foreach ($records as $record) {
            $payload[] = $this->catalogItemPayload($record);
        }

        return $payload;
    }

    /**
     * @return array{id: int, name: string, is_active: bool}
     */
    private function catalogItemPayload(Model $record): array
    {
        return [
            'id' => (int) $record->getAttribute('id'),
            'name' => (string) $record->getAttribute('name'),
            'is_active' => (bool) $record->getAttribute('is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product): array
    {
        return [
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
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(Request $request, string $message, int $status = 200, array $payload = []): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect()->route('products.index')->with('success', $message);
        }

        return response()->json(array_merge([
            'message' => $message,
        ], $payload), $status);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeEmailList(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $emails = collect(explode(',', $value))
            ->map(static fn (string $email): string => trim($email))
            ->filter(static fn (string $email): bool => $email !== '')
            ->values();

        return $emails->isEmpty() ? null : $emails->implode(', ');
    }
}
