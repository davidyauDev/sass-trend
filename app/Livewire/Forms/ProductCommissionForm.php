<?php

namespace App\Livewire\Forms;

use App\Models\Product;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProductCommissionForm extends Form
{
    public ?int $productId = null;

    public string $sale_commission = '0';

    public string $commission_type = 'percent';

    public function resetForm(): void
    {
        $this->productId = null;
        $this->sale_commission = '0';
        $this->commission_type = 'percent';
    }

    public function fillFromProduct(Product $product): void
    {
        $this->productId = $product->id;
        $this->sale_commission = (string) $product->sale_commission;
        $this->commission_type = $product->commission_type;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'sale_commission' => (float) $this->sale_commission,
            'commission_type' => $this->commission_type,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'sale_commission' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', 'string', Rule::in(['percent', 'amount'])],
        ];
    }
}
