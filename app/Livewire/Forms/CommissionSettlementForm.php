<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class CommissionSettlementForm extends Form
{
    public ?int $branch_id = null;

    public string $period_type = 'monthly';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $notes = '';

    public function resetForm(): void
    {
        $this->branch_id = null;
        $this->period_type = 'monthly';
        $this->starts_at = '';
        $this->ends_at = '';
        $this->notes = '';
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'period_type' => $this->period_type,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer'],
            'period_type' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
