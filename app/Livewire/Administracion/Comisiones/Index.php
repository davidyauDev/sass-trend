<?php

namespace App\Livewire\Administracion\Comisiones;

use App\Livewire\Forms\ProductCommissionForm;
use App\Livewire\Forms\ProfessionalDefaultCommissionForm;
use App\Livewire\Forms\ProfessionalServiceCommissionForm;
use App\Models\Product;
use App\Models\Professional;
use App\Models\ProfessionalCommission;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Comisiones')]
class Index extends Component
{
    public string $section = 'services';

    public string $professionalSearch = '';

    public string $productSearch = '';

    public ?int $selectedProfessionalId = null;

    public ?int $selectedProductId = null;

    #[Validate('required|numeric|min:0|max:100')]
    public string $bulkProductCommissionPercentage = '10';

    public ProfessionalDefaultCommissionForm $professionalDefaultForm;

    public ProfessionalServiceCommissionForm $professionalServiceForm;

    public ProductCommissionForm $productForm;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('viewAny', ProfessionalCommission::class) === true, 403);
    }

    public function updatedProfessionalSearch(): void
    {
        //
    }

    public function updatedProductSearch(): void
    {
        //
    }

    public function showServices(): void
    {
        $this->section = 'services';
    }

    public function showProducts(): void
    {
        $this->section = 'products';
    }

    public function openProfessionalServicesModal(int $professionalId): void
    {
        $professional = Professional::query()
            ->with(['services' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($professionalId);

        $this->selectedProfessionalId = $professional->id;
        $this->professionalServiceForm->resetForm();
        $this->professionalServiceForm->fillFromProfessional($professional);
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('professional-services')->show();
    }

    public function openProfessionalDefaultModal(int $professionalId): void
    {
        $professional = Professional::query()->findOrFail($professionalId);

        $this->selectedProfessionalId = $professional->id;
        $this->professionalDefaultForm->resetForm();
        $this->professionalDefaultForm->fillFromProfessional($professional);
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('professional-default-commission')->show();
    }

    public function openProductModal(int $productId): void
    {
        $product = Product::query()
            ->with(['brand', 'presentation'])
            ->findOrFail($productId);

        $this->selectedProductId = $product->id;
        $this->productForm->resetForm();
        $this->productForm->fillFromProduct($product);
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('product-commission')->show();
    }

    public function closeProfessionalServicesModal(): void
    {
        $this->selectedProfessionalId = null;
        $this->professionalServiceForm->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('professional-services')->close();
    }

    public function closeProfessionalDefaultModal(): void
    {
        $this->selectedProfessionalId = null;
        $this->professionalDefaultForm->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('professional-default-commission')->close();
    }

    public function closeProductModal(): void
    {
        $this->selectedProductId = null;
        $this->productForm->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('product-commission')->close();
    }

    public function saveProfessionalServices(): void
    {
        $this->professionalServiceForm->validate();

        if ($this->professionalServiceForm->professionalId === null) {
            return;
        }

        $professional = Professional::query()
            ->with(['services' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($this->professionalServiceForm->professionalId);

        DB::transaction(function () use ($professional): void {
            foreach ($this->professionalServiceForm->payload() as $row) {
                $professional->services()->updateExistingPivot((int) $row['service_id'], [
                    'sale_commission' => $row['sale_commission'],
                    'commission_type' => $row['commission_type'],
                ]);
            }
        });

        $this->closeProfessionalServicesModal();
        Flux::toast(variant: 'success', text: 'Comisiones del profesional actualizadas.');
    }

    public function saveProfessionalDefaultCommission(): void
    {
        $this->professionalDefaultForm->validate();

        if ($this->professionalDefaultForm->professionalId === null) {
            return;
        }

        $professional = Professional::query()->findOrFail($this->professionalDefaultForm->professionalId);
        $professional->update($this->professionalDefaultForm->payload());

        $this->closeProfessionalDefaultModal();
        Flux::toast(variant: 'success', text: 'Comisión por defecto actualizada.');
    }

    public function saveProductCommission(): void
    {
        $this->productForm->validate();

        if ($this->productForm->productId === null) {
            return;
        }

        $product = Product::query()->findOrFail($this->productForm->productId);
        $product->update($this->productForm->payload());

        $this->closeProductModal();
        Flux::toast(variant: 'success', text: 'Comisión del producto actualizada.');
    }

    public function applyCommissionToAllProducts(): void
    {
        $this->validate([
            'bulkProductCommissionPercentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Product::query()
            ->where('is_active', true)
            ->update([
                'sale_commission' => (float) $this->bulkProductCommissionPercentage,
                'commission_type' => 'percent',
            ]);

        Flux::toast(variant: 'success', text: 'Comisión aplicada a todos los productos activos.');
    }

    /**
     * @return EloquentCollection<int, Professional>
     */
    #[Computed]
    public function professionals(): EloquentCollection
    {
        $search = trim($this->professionalSearch);

        return Professional::query()
            ->with(['services' => fn ($query) => $query->orderBy('name')])
            ->withCount('services')
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($searchQuery) use ($like): void {
                    $searchQuery
                        ->where('public_name', 'like', $like)
                        ->orWhereHas('services', fn ($serviceQuery) => $serviceQuery->where('name', 'like', $like));
                });
            })
            ->orderBy('public_name')
            ->get();
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    #[Computed]
    public function products(): EloquentCollection
    {
        return Product::query()
            ->with(['brand', 'presentation'])
            ->where('is_active', true)
            ->search(trim($this->productSearch))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedProfessional(): ?Professional
    {
        if ($this->selectedProfessionalId === null) {
            return null;
        }

        return Professional::query()
            ->with(['services' => fn ($query) => $query->orderBy('name')])
            ->find($this->selectedProfessionalId);
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        if ($this->selectedProductId === null) {
            return null;
        }

        return Product::query()
            ->with(['brand', 'presentation'])
            ->find($this->selectedProductId);
    }

    public function commissionBadge(float|string $amount, string $type): string
    {
        return $type === 'amount'
            ? 'S/ '.number_format((float) $amount, 1)
            : number_format((float) $amount, 1).'%';
    }

    public function render(): View
    {
        return view('livewire.administracion.comisiones.index')->layout('layouts.app');
    }
}
