<?php

namespace App\Http\Controllers;

use App\Actions\Products\AdjustProductStockAction;
use App\Actions\Products\CreateProductSaleAction;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use App\Models\ProductSale;
use App\Models\ProductStockMovement;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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

    public function salesIndex(Request $request): View
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

        $salesQuery = $this->salesQuery($from, $to, $branchId, $productId);

        $sales = (clone $salesQuery)
            ->with(['branch', 'user', 'items.product.presentation', 'items.product.brand'])
            ->latest('sold_at')
            ->paginate(10)
            ->withQueryString();

        $salesSummary = [
            'revenue' => (float) (clone $salesQuery)->sum('total'),
            'sales_count' => (int) (clone $salesQuery)->count(),
            'units_sold' => (float) ProductSale::query()
                ->selectRaw('coalesce(sum(product_sale_items.quantity), 0) as units_sold')
                ->join('product_sale_items', 'product_sales.id', '=', 'product_sale_items.product_sale_id')
                ->whereDate('product_sales.sold_at', '>=', $from)
                ->whereDate('product_sales.sold_at', '<=', $to)
                ->when($branchId !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('product_sales.branch_id', $branchId))
                ->when($productId !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('product_sale_items.product_id', $productId))
                ->value('units_sold'),
            'average_ticket' => (float) ((clone $salesQuery)->count() > 0 ? (clone $salesQuery)->avg('total') : 0),
        ];

        return view('products.sales', [
            'from' => $from,
            'to' => $to,
            'branchId' => $branchId,
            'productId' => $productId,
            'hasFilters' => $hasFilters,
            'sales' => $sales,
            'branches' => $this->branchPayload($branches),
            'products' => $this->productCatalogPayload($products),
            'salesSummary' => $salesSummary,
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
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                ],
            ],
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
                    'detail' => url('/products/movements'),
                    'store' => url('/products/movements'),
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
                ->latest('occurred_at')
                ->limit(15)
                ->get()
                ->map(fn (ProductStockMovement $movement): array => $this->movementPayload($movement))
                ->values()
                ->all(),
        ]);
    }

    public function movementStore(Request $request, Product $product, AdjustProductStockAction $action): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'stock_by_branch' => ['required', 'array'],
            'stock_by_branch.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $action->handle($request->user(), $product, $data['stock_by_branch']);

        return $this->respond($request, 'Stock actualizado correctamente.');
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
