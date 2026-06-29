<?php

namespace App\Livewire\Sales;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\DeleteSaleAction;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\User;
use App\Services\Sales\SaleListingQuery;
use App\Services\Sales\SaleManagementGuard;
use App\Services\Sales\SalePaymentMethodCatalog;
use App\Services\Sales\SaleStatusCatalog;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Ventas')]
class Index extends Component
{
    use WithPagination;

    private const array ITEM_PICKER_TABS = ['recent', 'services', 'products'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'period')]
    public string $periodFilter = '7';

    #[Url(as: 'client')]
    public string $clientFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'payment')]
    public string $paymentMethodFilter = '';

    #[Url(as: 'branch')]
    public string $branchFilter = '';

    #[Url]
    public int $perPage = 25;

    public bool $isDrawerOpen = false;

    /** @var 'cart'|'client-search'|'client-create'|'item-picker'|'product-config'|'service-professional'|'payment'|'success' */
    public string $drawerStep = 'cart';

    /** @var 'recent'|'services'|'products' */
    public string $itemPickerTab = 'recent';

    public ?int $serviceProfessionalPickerServiceId = null;

    public ?int $serviceProfessionalPickerProfessionalId = null;

    public ?int $productConfigurationProductId = null;

    public ?int $productConfigurationProfessionalId = null;

    public int $productConfigurationQuantity = 1;

    public string $productConfigurationPrice = '';

    public string $productConfigurationDiscountType = 'percent';

    public string $productConfigurationDiscountValue = '0';

    public string $itemSearch = '';

    /** @var 'success'|'detail' */
    public string $saleSummaryMode = 'success';

    /** @var array<string, bool> */
    public array $visibleColumns = [
        'id' => true,
        'date' => true,
        'amount' => true,
        'client' => true,
        'branch' => true,
        'status' => false,
    ];

    public bool $showColumnEditor = false;

    /** @var array<string, mixed> */
    public array $saleForm = [];

    /** @var array<string, string|null> */
    public array $clientCreateForm = [];

    public string $clientSearch = '';

    public ?int $selectedSaleId = null;

    public ?int $saleIdPendingDeletion = null;

    public function mount(SaleManagementGuard $guard): void
    {
        $guard->ensureCanView($this->authUser());
        $this->resetSaleForm();
        $this->resetClientCreateForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedClientFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 25, 50], true)) {
            $this->perPage = 25;
        }

        $this->resetPage();
    }

    public function sortColumnsToggle(): void
    {
        $this->showColumnEditor = ! $this->showColumnEditor;
    }

    public function openCreateSale(): void
    {
        $this->resetSaleForm();
        $this->resetClientCreateForm();
        $this->drawerStep = 'cart';
        $this->itemPickerTab = 'recent';
        $this->serviceProfessionalPickerServiceId = null;
        $this->serviceProfessionalPickerProfessionalId = null;
        $this->resetProductConfiguration();
        $this->itemSearch = '';
        $this->clientSearch = '';
        $this->selectedSaleId = null;
        $this->saleSummaryMode = 'success';
        $this->isDrawerOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function closeDrawer(): void
    {
        $this->isDrawerOpen = false;
        $this->drawerStep = 'cart';
        $this->itemPickerTab = 'recent';
        $this->serviceProfessionalPickerServiceId = null;
        $this->serviceProfessionalPickerProfessionalId = null;
        $this->resetProductConfiguration();
        $this->itemSearch = '';
        $this->saleSummaryMode = 'success';
        $this->resetSaleForm();
        $this->resetClientCreateForm();
        $this->clientSearch = '';
        $this->selectedSaleId = null;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openClientSearch(): void
    {
        $this->drawerStep = 'client-search';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openClientCreate(): void
    {
        $this->drawerStep = 'client-create';
        $this->resetClientCreateForm();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function backToCart(): void
    {
        $this->drawerStep = 'cart';
        $this->serviceProfessionalPickerServiceId = null;
        $this->serviceProfessionalPickerProfessionalId = null;
        $this->resetProductConfiguration();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function backToItemPicker(): void
    {
        $this->drawerStep = 'item-picker';
        $this->serviceProfessionalPickerServiceId = null;
        $this->serviceProfessionalPickerProfessionalId = null;
        $this->resetProductConfiguration();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openItemPicker(string $tab = 'recent'): void
    {
        $this->drawerStep = 'item-picker';
        $this->itemPickerTab = in_array($tab, self::ITEM_PICKER_TABS, true) ? $tab : 'recent';
        $this->itemSearch = '';
        $this->serviceProfessionalPickerServiceId = null;
        $this->serviceProfessionalPickerProfessionalId = null;
        $this->resetProductConfiguration();
    }

    public function setItemPickerTab(string $tab): void
    {
        if (! in_array($tab, self::ITEM_PICKER_TABS, true)) {
            return;
        }

        $this->itemPickerTab = $tab;
    }

    public function openServiceProfessionalPicker(int $serviceId): void
    {
        $service = Service::query()
            ->with(['professionalProfiles' => fn ($query) => $query->where('is_active', true)->orderBy('public_name')])
            ->findOrFail($serviceId);

        if ($service->professionalProfiles->isEmpty()) {
            Flux::toast(variant: 'danger', text: 'Este servicio aún no tiene profesionales asignados.');

            return;
        }

        $this->serviceProfessionalPickerServiceId = $service->id;
        $this->serviceProfessionalPickerProfessionalId = $service->professionalProfiles->count() === 1
            ? $service->professionalProfiles->first()?->id
            : null;
        $this->drawerStep = 'service-professional';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openProductConfiguration(int $productId): void
    {
        $product = Product::query()
            ->with(['brand', 'presentation'])
            ->findOrFail($productId);

        $this->productConfigurationProductId = $product->id;
        $this->productConfigurationProfessionalId = $this->professionalsCatalog()->count() === 1
            ? $this->professionalsCatalog()->first()?->id
            : null;
        $this->productConfigurationQuantity = 1;
        $this->productConfigurationPrice = (string) $product->public_sale_price;
        $this->productConfigurationDiscountType = 'percent';
        $this->productConfigurationDiscountValue = '0';
        $this->drawerStep = 'product-config';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function selectServiceProfessional(int $professionalId): void
    {
        if ($this->serviceProfessionalPickerServiceId === null) {
            return;
        }

        $service = Service::query()
            ->with(['professionalProfiles' => fn ($query) => $query->where('is_active', true)->orderBy('public_name')])
            ->findOrFail($this->serviceProfessionalPickerServiceId);

        $professional = $service->professionalProfiles->firstWhere('id', $professionalId);

        if (! $professional instanceof Professional) {
            return;
        }

        $this->serviceProfessionalPickerProfessionalId = $professional->id;
        $this->addServiceToCart($service, $professional);
        $this->drawerStep = 'cart';
        $this->serviceProfessionalPickerServiceId = null;
        $this->serviceProfessionalPickerProfessionalId = null;
    }

    public function increaseProductConfigurationQuantity(): void
    {
        $this->productConfigurationQuantity++;
    }

    public function decreaseProductConfigurationQuantity(): void
    {
        if ($this->productConfigurationQuantity <= 1) {
            $this->backToItemPicker();

            return;
        }

        $this->productConfigurationQuantity--;
    }

    public function saveProductConfiguration(): void
    {
        $this->validate([
            'productConfigurationProductId' => ['required', 'integer', Rule::exists('products', 'id')],
            'productConfigurationProfessionalId' => ['required', 'integer', Rule::exists('professionals', 'id')],
            'productConfigurationQuantity' => ['required', 'integer', 'min:1'],
            'productConfigurationPrice' => ['required', 'numeric', 'min:0'],
            'productConfigurationDiscountType' => ['required', 'string', Rule::in(['percent', 'amount'])],
            'productConfigurationDiscountValue' => ['required', 'numeric', 'min:0'],
        ]);

        $product = Product::query()
            ->with(['brand', 'presentation'])
            ->findOrFail($this->productConfigurationProductId);

        $professional = $this->professionalsCatalog()->firstWhere('id', $this->productConfigurationProfessionalId);

        if (! $professional instanceof Professional) {
            throw ValidationException::withMessages([
                'productConfigurationProfessionalId' => 'Selecciona un vendedor válido.',
            ]);
        }

        $this->addConfiguredProductToCart(
            $product,
            $professional,
            $this->productConfigurationQuantity,
            (float) $this->productConfigurationPrice,
            $this->productConfigurationDiscountType,
            (float) $this->productConfigurationDiscountValue,
        );

        $this->drawerStep = 'cart';
        $this->resetProductConfiguration();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function addServiceToCart(Service|int $service, Professional|int|null $professional = null): void
    {
        $service = $service instanceof Service
            ? $service
            : Service::query()
                ->with(['professionalProfiles' => fn ($query) => $query->where('is_active', true)->orderBy('public_name')])
                ->findOrFail($service);

        $professionalModel = null;

        if ($professional instanceof Professional) {
            $professionalModel = $professional;
        } elseif (is_int($professional)) {
            $professionalModel = $service->professionalProfiles->firstWhere('id', $professional);
        } elseif ($service->professionalProfiles->count() === 1) {
            $professionalModel = $service->professionalProfiles->first();
        }

        $key = $professionalModel instanceof Professional
            ? 'service:'.$service->id.':professional:'.$professionalModel->id
            : 'service:'.$service->id;

        if (isset($this->saleForm['cart'][$key])) {
            $this->saleForm['cart'][$key]['quantity'] = (string) ((float) $this->saleForm['cart'][$key]['quantity'] + 1);
            $this->saleForm['cart'][$key]['subtotal'] = round((float) $this->saleForm['cart'][$key]['quantity'] * (float) $this->saleForm['cart'][$key]['unit_price'], 2);

            return;
        }

        $this->saleForm['cart'][$key] = [
            'key' => $key,
            'item_type' => 'service',
            'service_id' => $service->id,
            'product_id' => null,
            'item_name' => $service->name,
            'item_detail' => $service->duration_minutes.' min',
            'quantity' => '1',
            'unit_price' => (string) $service->price,
            'subtotal' => (float) $service->price,
            'meta' => [
                'professional_id' => $professionalModel?->id,
                'professional_name' => $professionalModel?->displayName(),
                'discount_type' => 'none',
                'discount_value' => '0',
                'discount_amount' => 0.0,
            ],
        ];
    }

    public function addProductToCart(int $productId): void
    {
        $this->openProductConfiguration($productId);
    }

    public function decreaseProductToCart(int $productId): void
    {
        $key = collect($this->saleForm['cart'] ?? [])
            ->first(function (array $item) use ($productId): bool {
                return ($item['item_type'] ?? null) === 'product'
                    && (int) ($item['product_id'] ?? 0) === $productId;
            })['key'] ?? null;

        if (! is_string($key)) {
            return;
        }

        $this->adjustCartItemQuantity($key, -1);
    }

    public function cartQuantityForProduct(int $productId): int
    {
        return (int) round(collect($this->saleForm['cart'] ?? [])
            ->filter(function (array $item) use ($productId): bool {
                return ($item['item_type'] ?? null) === 'product'
                    && (int) ($item['product_id'] ?? 0) === $productId;
            })
            ->sum(fn (array $item): float => (float) ($item['quantity'] ?? 0)));
    }

    public function cartQuantityForService(int $serviceId): int
    {
        return (int) round(collect($this->saleForm['cart'] ?? [])
            ->filter(function (array $item) use ($serviceId): bool {
                return ($item['item_type'] ?? null) === 'service'
                    && (int) ($item['service_id'] ?? 0) === $serviceId;
            })
            ->sum(fn (array $item): float => (float) ($item['quantity'] ?? 0)));
    }

    public function removeCartItem(string $key): void
    {
        unset($this->saleForm['cart'][$key]);
    }

    public function decreaseCartItem(string $key): void
    {
        $this->adjustCartItemQuantity($key, -1);
    }

    public function increaseCartItem(string $key): void
    {
        $this->adjustCartItemQuantity($key, 1);
    }

    private function addConfiguredProductToCart(
        Product $product,
        Professional $professional,
        int $quantity,
        float $unitPrice,
        string $discountType,
        float $discountValue,
    ): void {
        $key = $this->productCartKey($product->id, $professional->id, $unitPrice, $discountType, $discountValue);

        $lineSubtotal = $this->lineSubtotal($quantity, $unitPrice, $discountType, $discountValue);
        $lineDiscountAmount = $this->lineDiscountAmount($quantity, $unitPrice, $discountType, $discountValue);

        if (isset($this->saleForm['cart'][$key])) {
            $currentQuantity = (float) ($this->saleForm['cart'][$key]['quantity'] ?? 0);
            $newQuantity = (int) round($currentQuantity + $quantity);

            $this->saleForm['cart'][$key]['quantity'] = (string) $newQuantity;
            $this->saleForm['cart'][$key]['subtotal'] = $this->lineSubtotal($newQuantity, $unitPrice, $discountType, $discountValue);
            $this->saleForm['cart'][$key]['meta']['discount_amount'] = $this->lineDiscountAmount($newQuantity, $unitPrice, $discountType, $discountValue);

            return;
        }

        $this->saleForm['cart'][$key] = [
            'key' => $key,
            'item_type' => 'product',
            'service_id' => null,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'item_detail' => trim(implode(' | ', array_filter([
                $product->brand?->name,
                $product->presentation?->name,
            ]))),
            'quantity' => (string) $quantity,
            'unit_price' => (string) $unitPrice,
            'subtotal' => $lineSubtotal,
            'meta' => [
                'professional_id' => $professional->id,
                'professional_name' => $professional->displayName(),
                'discount_type' => $discountType,
                'discount_value' => (string) $discountValue,
                'discount_amount' => $lineDiscountAmount,
            ],
        ];
    }

    private function adjustCartItemQuantity(string $key, int $delta, ?array $defaultItem = null): void
    {
        if (! isset($this->saleForm['cart'][$key])) {
            if ($delta > 0 && $defaultItem !== null) {
                $this->saleForm['cart'][$key] = $defaultItem;
            }

            return;
        }

        $currentQuantity = (float) ($this->saleForm['cart'][$key]['quantity'] ?? 0);
        $newQuantity = round($currentQuantity + $delta, 2);

        if ($newQuantity <= 0) {
            unset($this->saleForm['cart'][$key]);

            return;
        }

        $unitPrice = (float) ($this->saleForm['cart'][$key]['unit_price'] ?? 0);
        $discountType = (string) data_get($this->saleForm['cart'][$key], 'meta.discount_type', 'none');
        $discountValue = (float) data_get($this->saleForm['cart'][$key], 'meta.discount_value', 0);
        $this->saleForm['cart'][$key]['quantity'] = (string) $newQuantity;
        $this->saleForm['cart'][$key]['subtotal'] = $this->lineSubtotal((int) $newQuantity, $unitPrice, $discountType, $discountValue);
        $this->saleForm['cart'][$key]['meta']['discount_amount'] = $this->lineDiscountAmount((int) $newQuantity, $unitPrice, $discountType, $discountValue);
    }

    private function resetProductConfiguration(): void
    {
        $this->productConfigurationProductId = null;
        $this->productConfigurationProfessionalId = null;
        $this->productConfigurationQuantity = 1;
        $this->productConfigurationPrice = '';
        $this->productConfigurationDiscountType = 'percent';
        $this->productConfigurationDiscountValue = '0';
    }

    private function productCartKey(int $productId, int $professionalId, float $unitPrice, string $discountType, float $discountValue): string
    {
        return sprintf(
            'product:%d:professional:%d:price:%s:discount:%s:%s',
            $productId,
            $professionalId,
            number_format($unitPrice, 2, '.', ''),
            $discountType,
            number_format($discountValue, 2, '.', ''),
        );
    }

    private function lineSubtotal(int $quantity, float $unitPrice, string $discountType, float $discountValue): float
    {
        $gross = round($quantity * $unitPrice, 2);
        $discount = $this->lineDiscountAmount($quantity, $unitPrice, $discountType, $discountValue);

        return round(max(0, $gross - $discount), 2);
    }

    private function lineDiscountAmount(int $quantity, float $unitPrice, string $discountType, float $discountValue): float
    {
        $gross = round($quantity * $unitPrice, 2);

        return match ($discountType) {
            'amount' => round(min($gross, max(0, $discountValue)), 2),
            'percent' => round($gross * max(0, min(100, $discountValue)) / 100, 2),
            default => 0.0,
        };
    }

    public function selectClient(int $clientId): void
    {
        $this->saleForm['client_id'] = $clientId;
        $this->drawerStep = 'cart';
    }

    public function clearClient(): void
    {
        $this->saleForm['client_id'] = null;
    }

    public function saveInlineClient(CreateClientAction $createClient): void
    {
        $validated = $this->validate($this->inlineClientRules(), [], [
            'clientCreateForm.first_name' => 'nombre',
            'clientCreateForm.last_name' => 'apellido',
            'clientCreateForm.email' => 'email',
            'clientCreateForm.phone' => 'teléfono',
        ]);

        $client = $createClient->handle($validated['clientCreateForm']);

        $this->saleForm['client_id'] = $client->id;
        $this->resetClientCreateForm();
        $this->drawerStep = 'cart';

        Flux::toast(variant: 'success', text: 'Cliente creado y asociado correctamente.');
    }

    public function proceedToPayment(): void
    {
        if ($this->cartItems() === []) {
            throw ValidationException::withMessages([
                'cart' => 'Agrega al menos un ítem al carrito.',
            ]);
        }

        if ($this->saleForm['branch_id'] === null || $this->saleForm['branch_id'] === '') {
            throw ValidationException::withMessages([
                'saleForm.branch_id' => 'Selecciona un local para continuar.',
            ]);
        }

        if ($this->saleForm['client_id'] === null) {
            $this->openClientSearch();

            return;
        }

        $this->drawerStep = 'payment';
    }

    public function selectPaymentMethod(string $method): void
    {
        if (! array_key_exists($method, SalePaymentMethodCatalog::options())) {
            return;
        }

        $this->saleForm['selected_payment_method'] = $method;
        $this->saleForm['payments'] = [[
            'method' => $method,
            'amount' => (string) $this->cartTotal(),
            'reference' => null,
        ]];
    }

    public function completeSale(string $method, CreateSaleAction $createSale): void
    {
        $this->selectPaymentMethod($method);

        try {
            $sale = $createSale->handle($this->authUser(), $this->salePayloadForAction(SaleStatusCatalog::PAID));
            $this->selectedSaleId = $sale->id;
            $this->saleSummaryMode = 'success';
            $this->drawerStep = 'success';
        } catch (ValidationException $exception) {
            Flux::toast(variant: 'danger', text: $this->validationMessage($exception));
        } catch (\Throwable $throwable) {
            $message = app()->hasDebugModeEnabled() && $throwable->getMessage() !== ''
                ? $throwable->getMessage()
                : 'No se pudo registrar la venta. Revisa los datos e inténtalo otra vez.';

            Flux::toast(variant: 'danger', text: $message);
            report($throwable);
        }
    }

    public function saveDraft(CreateSaleAction $createSale): void
    {
        $sale = $createSale->handle($this->authUser(), $this->salePayloadForAction(SaleStatusCatalog::DRAFT));
        $this->selectedSaleId = $sale->id;

        Flux::toast(variant: 'success', text: 'Carrito guardado como borrador.');

        $this->closeDrawer();
    }

    public function openSaleDetail(int $saleId): void
    {
        $this->selectedSaleId = $saleId;
        $this->saleSummaryMode = 'detail';
        $this->drawerStep = 'success';
        $this->isDrawerOpen = true;
    }

    public function confirmDelete(int $saleId): void
    {
        $this->saleIdPendingDeletion = $saleId;
        $this->modal('delete-sale')->show();
    }

    public function closeDeleteModal(): void
    {
        $this->saleIdPendingDeletion = null;
    }

    public function deleteSale(DeleteSaleAction $deleteSale): void
    {
        if ($this->saleIdPendingDeletion === null) {
            return;
        }

        $sale = Sale::query()->findOrFail($this->saleIdPendingDeletion);
        $deleteSale->handle($this->authUser(), $sale);

        $this->closeDeleteModal();
        $this->modal('delete-sale')->close();

        Flux::toast(variant: 'success', text: 'Venta eliminada correctamente.');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'periodFilter', 'clientFilter', 'statusFilter', 'paymentMethodFilter', 'branchFilter']);
        $this->periodFilter = '7';
        $this->perPage = 25;
        $this->resetPage();
    }

    /**
     * @return Collection<int, Branch>
     */
    #[Computed]
    public function branchesCatalog(): Collection
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clientsCatalog(): Collection
    {
        return Client::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function searchedClients(): Collection
    {
        if (mb_strlen(trim($this->clientSearch)) < 3) {
            return new Collection;
        }

        $term = '%'.trim($this->clientSearch).'%';

        return Client::query()
            ->where(function (Builder $query) use ($term): void {
                $query
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('dni', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            })
            ->orderBy('first_name')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function servicesCatalog(): Collection
    {
        return Service::query()
            ->with(['professionalProfiles'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function productsCatalog(): Collection
    {
        return Product::query()
            ->with(['brand', 'presentation'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, SaleItem>
     */
    #[Computed]
    public function recentItems(): Collection
    {
        $items = SaleItem::query()
            ->with(['product.brand', 'product.presentation', 'service.professionalProfiles'])
            ->where('item_type', '!=', 'giftcard')
            ->latest()
            ->limit(12)
            ->get();

        return $items->unique(function (SaleItem $item): string {
            $professionalId = data_get($item->meta, 'professional_id');

            return $item->item_type.':'.($item->service_id ?? $item->product_id).':'.($professionalId ?? '');
        })->values();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function filteredServicesCatalog(): Collection
    {
        return Service::query()
            ->with(['professionalProfiles' => fn ($query) => $query->where('is_active', true)->orderBy('public_name')])
            ->where('is_active', true)
            ->search(trim($this->itemSearch))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function filteredProductsCatalog(): Collection
    {
        return Product::query()
            ->with(['brand', 'presentation'])
            ->where('is_active', true)
            ->search(trim($this->itemSearch))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, SaleItem>
     */
    #[Computed]
    public function filteredRecentItems(): Collection
    {
        $term = mb_strtolower(trim($this->itemSearch));

        if ($term === '') {
            return $this->recentItems();
        }

        return $this->recentItems()
            ->filter(function (SaleItem $item) use ($term): bool {
                $haystack = mb_strtolower(trim(implode(' ', array_filter([
                    $item->item_name,
                    $item->item_detail,
                ]))));

                return str_contains($haystack, $term);
            })
            ->values();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function paymentMethods(): array
    {
        return SalePaymentMethodCatalog::options();
    }

    /**
     * @return Collection<int, Professional>
     */
    #[Computed]
    public function professionalsCatalog(): Collection
    {
        return Professional::query()
            ->with('user')
            ->where('is_active', true)
            ->orderBy('public_name')
            ->get();
    }

    #[Computed]
    public function serviceProfessionalPickerService(): ?Service
    {
        if ($this->serviceProfessionalPickerServiceId === null) {
            return null;
        }

        return Service::query()
            ->with(['professionalProfiles' => fn ($query) => $query->where('is_active', true)->orderBy('public_name')])
            ->find($this->serviceProfessionalPickerServiceId);
    }

    /**
     * @return Collection<int, Professional>
     */
    #[Computed]
    public function serviceProfessionalPickerProfessionals(): Collection
    {
        return $this->serviceProfessionalPickerService()?->professionalProfiles ?? collect();
    }

    #[Computed]
    public function selectedSale(): ?Sale
    {
        if ($this->selectedSaleId === null) {
            return null;
        }

        return Sale::query()
            ->withTrashed()
            ->with(['client', 'branch', 'items.product.presentation', 'items.service', 'payments'])
            ->find($this->selectedSaleId);
    }

    #[Computed]
    public function productConfigurationProduct(): ?Product
    {
        if ($this->productConfigurationProductId === null) {
            return null;
        }

        return Product::query()
            ->with(['brand', 'presentation'])
            ->find($this->productConfigurationProductId);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function metrics(): array
    {
        return [
            'all' => (int) Sale::query()->count(),
            'partial' => (int) Sale::query()->where('status', SaleStatusCatalog::PARTIAL)->count(),
            'deleted' => (int) Sale::onlyTrashed()->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.sales.index', [
            'sales' => $this->sales(),
            'selectedSale' => $this->selectedSale(),
            'salePendingDeletion' => $this->saleIdPendingDeletion ? Sale::query()->find($this->saleIdPendingDeletion) : null,
        ])->layout('layouts.app');
    }

    /** @return LengthAwarePaginator<int, Sale> */
    private function sales(): LengthAwarePaginator
    {
        $query = app(SaleListingQuery::class)->handle($this->salesFilters());

        return $query->latest('sold_at')->paginate($this->perPage);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cartItems(): array
    {
        return array_values($this->saleForm['cart']);
    }

    private function cartTotal(): float
    {
        return round(collect($this->cartItems())->sum(fn (array $item): float => (float) $item['subtotal']), 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function salePayloadForAction(string $status): array
    {
        $items = collect($this->cartItems())
            ->map(function (array $item): array {
                $quantity = max(0.01, (float) $item['quantity']);
                $unitPrice = max(0, (float) $item['unit_price']);
                $discountType = (string) data_get($item, 'meta.discount_type', 'none');
                $discountValue = (float) data_get($item, 'meta.discount_value', 0);
                $discountAmount = $this->lineDiscountAmount((int) round($quantity), $unitPrice, $discountType, $discountValue);
                $grossSubtotal = round($quantity * $unitPrice, 2);
                $finalSubtotal = round(max(0, $grossSubtotal - $discountAmount), 2);

                return [
                    'item_type' => $item['item_type'],
                    'service_id' => $item['service_id'],
                    'product_id' => $item['product_id'],
                    'item_name' => $item['item_name'],
                    'item_detail' => $item['item_detail'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $finalSubtotal,
                    'discount_amount' => $discountAmount,
                    'meta' => $item['meta'],
                ];
            })
            ->values()
            ->all();

        $subtotalTotal = round(array_reduce(
            $items,
            fn (float $carry, array $item): float => $carry + round($item['quantity'] * $item['unit_price'], 2),
            0.0,
        ), 2);

        $discountTotal = round(array_reduce(
            $items,
            fn (float $carry, array $item): float => $carry + (float) ($item['discount_amount'] ?? 0),
            0.0,
        ), 2);

        $itemsTotal = round(array_reduce(
            $items,
            fn (float $carry, array $item): float => $carry + (float) ($item['subtotal'] ?? 0),
            0.0,
        ), 2);

        $payments = [];

        if ($status !== SaleStatusCatalog::DRAFT) {
            foreach (($this->saleForm['payments'] ?? []) as $payment) {
                if (! is_array($payment)) {
                    continue;
                }

                $normalizedPayment = [
                    'method' => $payment['method'],
                    'amount' => max(0, round((float) $payment['amount'], 2)),
                    'reference' => $payment['reference'],
                ];

                if ($normalizedPayment['amount'] <= 0) {
                    continue;
                }

                $payments[] = $normalizedPayment;
            }
        }

        if ($status !== SaleStatusCatalog::DRAFT && $payments === []) {
            throw ValidationException::withMessages([
                'payments' => 'Debes registrar al menos un pago para completar la venta.',
            ]);
        }

        $paidTotal = round(array_reduce(
            $payments,
            fn (float $carry, array $payment): float => $carry + (float) $payment['amount'],
            0.0,
        ), 2);

        if ($status === SaleStatusCatalog::PAID && $paidTotal < $itemsTotal) {
            throw ValidationException::withMessages([
                'payments' => 'El monto pagado debe cubrir el total de la venta.',
            ]);
        }

        if ($status === SaleStatusCatalog::PARTIAL && ($paidTotal <= 0 || $paidTotal >= $itemsTotal)) {
            throw ValidationException::withMessages([
                'payments' => 'El abono debe ser mayor a cero y menor al total de la venta.',
            ]);
        }

        return [
            'branch_id' => (int) $this->saleForm['branch_id'],
            'client_id' => $this->saleForm['client_id'] !== null ? (int) $this->saleForm['client_id'] : null,
            'notes' => $this->nullableString($this->saleForm['notes']),
            'status' => $status,
            'subtotal' => $subtotalTotal,
            'discount_total' => $discountTotal,
            'total' => $itemsTotal,
            'items' => $items,
            'payments' => $payments,
        ];
    }

    private function resetSaleForm(): void
    {
        $defaultBranchId = $this->branchesCatalog()->first()?->id;
        $defaultMethod = array_key_first(SalePaymentMethodCatalog::options());

        $this->saleForm = [
            'branch_id' => $defaultBranchId,
            'client_id' => null,
            'notes' => '',
            'cart' => [],
            'selected_payment_method' => $defaultMethod,
            'payments' => [[
                'method' => $defaultMethod,
                'amount' => '0.00',
                'reference' => null,
            ]],
        ];
    }

    private function resetClientCreateForm(): void
    {
        $this->clientCreateForm = [
            'first_name' => '',
            'last_name' => '',
            'birth_date' => null,
            'age' => null,
            'dni' => null,
            'gender' => null,
            'client_number' => null,
            'email' => '',
            'phone' => '',
            'address' => null,
            'district' => null,
            'city' => null,
        ];
    }

    /**
     * @return array<string, list<string|Rule>>
     */
    private function inlineClientRules(): array
    {
        return [
            'clientCreateForm.first_name' => ['required', 'string', 'max:255'],
            'clientCreateForm.last_name' => ['required', 'string', 'max:255'],
            'clientCreateForm.email' => ['nullable', 'email', 'max:255', 'unique:clients,email'],
            'clientCreateForm.phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function validationMessage(ValidationException $exception): string
    {
        $message = collect($exception->errors())
            ->flatten()
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->first();

        return is_string($message) && $message !== ''
            ? $message
            : 'No se pudo registrar la venta. Revisa los datos e inténtalo otra vez.';
    }

    /**
     * @return array{search:string,period:string,client:string,status:string,payment:string,branch:string}
     */
    private function salesFilters(): array
    {
        return [
            'search' => $this->search,
            'period' => $this->periodFilter,
            'client' => $this->clientFilter,
            'status' => $this->statusFilter,
            'payment' => $this->paymentMethodFilter,
            'branch' => $this->branchFilter,
        ];
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
