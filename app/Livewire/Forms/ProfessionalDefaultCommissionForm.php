<?php

namespace App\Livewire\Forms;

use App\Models\Professional;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProfessionalDefaultCommissionForm extends Form
{
    public ?int $professionalId = null;

    public string $sale_commission = '0';

    public string $commission_type = 'percent';

    public function resetForm(): void
    {
        $this->professionalId = null;
        $this->sale_commission = '0';
        $this->commission_type = 'percent';
    }

    public function fillFromProfessional(Professional $professional): void
    {
        $this->professionalId = $professional->id;
        $this->sale_commission = (string) $professional->sale_commission;
        $this->commission_type = $professional->commission_type;
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
