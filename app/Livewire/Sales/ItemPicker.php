<?php

namespace App\Livewire\Sales;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class ItemPicker extends Component
{
    private const TABS = ['recent', 'services', 'products'];

    public string $search = '';

    public string $tab = 'recent';

    /**
     * @var array{products: array<int, int>, services: array<int, int>}
     */
    public array $cartQuantities = [
        'products' => [],
        'services' => [],
    ];

    public function setTab(string $tab): void
    {
        if (! in_array($tab, self::TABS, true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function selectService(int $serviceId): void
    {
        $this->dispatch('sales-item-picker-service-selected', serviceId: $serviceId);
    }

    public function selectProduct(int $productId): void
    {
        $this->dispatch('sales-item-picker-product-selected', productId: $productId);
    }

    /**
     * @return EloquentCollection<int, Service>
     */
    #[Computed]
    public function servicesCatalog(): EloquentCollection
    {
        return Service::query()
            ->with(['professionalProfiles' => fn ($query) => $query->where('is_active', true)->orderBy('public_name')])
            ->where('is_active', true)
            ->search(trim($this->search))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    #[Computed]
    public function productsCatalog(): EloquentCollection
    {
        return Product::query()
            ->with(['brand', 'category', 'presentation'])
            ->where('is_active', true)
            ->search(trim($this->search))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return EloquentCollection<int, SaleItem>
     */
    #[Computed]
    public function recentItems(): EloquentCollection
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
     * @return EloquentCollection<int, SaleItem>
     */
    #[Computed]
    public function filteredRecentItems(): EloquentCollection
    {
        $term = mb_strtolower(trim($this->search));

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

    public function cartQuantityForProduct(int $productId): int
    {
        return (int) ($this->cartQuantities['products'][$productId] ?? 0);
    }

    public function cartQuantityForService(int $serviceId): int
    {
        return (int) ($this->cartQuantities['services'][$serviceId] ?? 0);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.sales.item-picker');
    }
}
