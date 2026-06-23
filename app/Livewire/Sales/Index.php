<?php

namespace App\Livewire\Sales;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\DeleteSaleAction;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
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

    /** @var 'cart'|'client-search'|'client-create'|'item-picker'|'payment'|'success' */
    public string $drawerStep = 'cart';

    /** @var 'recent'|'services'|'products'|'giftcards' */
    public string $itemPickerTab = 'recent';

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
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openItemPicker(string $tab = 'recent'): void
    {
        $this->drawerStep = 'item-picker';
        $this->itemPickerTab = in_array($tab, ['recent', 'services', 'products', 'giftcards'], true) ? $tab : 'recent';
        $this->itemSearch = '';
    }

    public function setItemPickerTab(string $tab): void
    {
        if (! in_array($tab, ['recent', 'services', 'products', 'giftcards'], true)) {
            return;
        }

        $this->itemPickerTab = $tab;
    }

    public function addServiceToCart(int $serviceId): void
    {
        $service = Service::query()->findOrFail($serviceId);
        $key = 'service:'.$service->id;

        if (isset($this->saleForm['cart'][$key])) {
            $this->saleForm['cart'][$key]['quantity'] = (string) ((float) $this->saleForm['cart'][$key]['quantity'] + 1);
            $this->saleForm['cart'][$key]['subtotal'] = round((float) $this->saleForm['cart'][$key]['quantity'] * (float) $this->saleForm['cart'][$key]['unit_price'], 2);
        } else {
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
                'meta' => null,
            ];
        }
    }

    public function addProductToCart(int $productId): void
    {
        $product = Product::query()->with(['brand', 'presentation'])->findOrFail($productId);
        $key = 'product:'.$product->id;

        if (isset($this->saleForm['cart'][$key])) {
            $this->saleForm['cart'][$key]['quantity'] = (string) ((float) $this->saleForm['cart'][$key]['quantity'] + 1);
            $this->saleForm['cart'][$key]['subtotal'] = round((float) $this->saleForm['cart'][$key]['quantity'] * (float) $this->saleForm['cart'][$key]['unit_price'], 2);
        } else {
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
                'quantity' => '1',
                'unit_price' => (string) $product->public_sale_price,
                'subtotal' => (float) $product->public_sale_price,
                'meta' => null,
            ];
        }
    }

    public function removeCartItem(string $key): void
    {
        unset($this->saleForm['cart'][$key]);
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

        $this->drawerStep = 'payment';
    }

    public function selectPaymentMethod(string $method): void
    {
        if (! array_key_exists($method, SalePaymentMethodCatalog::options())) {
            return;
        }

        $this->saleForm['selected_payment_method'] = $method;

        if ($this->saleForm['payment_option'] === 'single') {
            $this->saleForm['payments'] = [[
                'method' => $method,
                'amount' => (string) $this->cartTotal(),
                'reference' => null,
            ]];
        }
    }

    public function enablePaymentOption(string $option): void
    {
        if (! in_array($option, ['single', 'split', 'partial'], true)) {
            return;
        }

        $this->saleForm['payment_option'] = $option;

        if ($option === 'single') {
            $this->saleForm['payments'] = [[
                'method' => $this->saleForm['selected_payment_method'],
                'amount' => (string) $this->cartTotal(),
                'reference' => null,
            ]];
        } elseif ($option === 'partial') {
            $this->saleForm['payments'] = [[
                'method' => $this->saleForm['selected_payment_method'],
                'amount' => (string) round($this->cartTotal() / 2, 2),
                'reference' => null,
            ]];
        } else {
            $half = round($this->cartTotal() / 2, 2);
            $remaining = round($this->cartTotal() - $half, 2);
            $this->saleForm['payments'] = [
                ['method' => $this->saleForm['selected_payment_method'], 'amount' => (string) $half, 'reference' => null],
                ['method' => SalePaymentMethodCatalog::CASH, 'amount' => (string) $remaining, 'reference' => null],
            ];
        }
    }

    public function addSplitPayment(): void
    {
        $this->saleForm['payments'][] = [
            'method' => SalePaymentMethodCatalog::CASH,
            'amount' => '0.00',
            'reference' => null,
        ];
    }

    public function removeSplitPayment(int $index): void
    {
        if (($this->saleForm['payments'] ?? []) === []) {
            return;
        }

        unset($this->saleForm['payments'][$index]);
        $this->saleForm['payments'] = array_values($this->saleForm['payments']);
    }

    public function saveDraft(CreateSaleAction $createSale): void
    {
        $sale = $createSale->handle($this->authUser(), $this->salePayloadForAction(SaleStatusCatalog::DRAFT));
        $this->selectedSaleId = $sale->id;

        Flux::toast(variant: 'success', text: 'Carrito guardado como borrador.');

        $this->closeDrawer();
    }

    public function finalizeSale(CreateSaleAction $createSale): void
    {
        $status = $this->saleForm['payment_option'] === 'partial' ? SaleStatusCatalog::PARTIAL : SaleStatusCatalog::PAID;
        $sale = $createSale->handle($this->authUser(), $this->salePayloadForAction($status));
        $this->selectedSaleId = $sale->id;
        $this->saleSummaryMode = 'success';
        $this->drawerStep = 'success';
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
            ->with(['product.brand', 'product.presentation', 'service'])
            ->latest()
            ->limit(12)
            ->get();

        return $items->unique(fn ($item) => $item->item_type.':'.($item->service_id ?? $item->product_id))->values();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function filteredServicesCatalog(): Collection
    {
        return Service::query()
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

                return [
                    'item_type' => $item['item_type'],
                    'service_id' => $item['service_id'],
                    'product_id' => $item['product_id'],
                    'item_name' => $item['item_name'],
                    'item_detail' => $item['item_detail'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'meta' => $item['meta'],
                ];
            })
            ->values()
            ->all();

        $itemsTotal = round(array_reduce(
            $items,
            fn (float $carry, array $item): float => $carry + round($item['quantity'] * $item['unit_price'], 2),
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
            'payment_option' => 'single',
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
