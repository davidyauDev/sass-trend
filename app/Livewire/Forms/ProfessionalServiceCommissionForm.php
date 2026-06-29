<?php

namespace App\Livewire\Forms;

use App\Models\Professional;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProfessionalServiceCommissionForm extends Form
{
    public ?int $professionalId = null;

    /**
     * @var array<int, array{
     *     service_id: int,
     *     service_name: string,
     *     sale_commission: string,
     *     commission_type: string
     * }>
     */
    public array $rows = [];

    public function resetForm(): void
    {
        $this->professionalId = null;
        $this->rows = [];
    }

    public function fillFromProfessional(Professional $professional): void
    {
        $this->professionalId = $professional->id;
        $defaultCommission = (string) $professional->sale_commission;
        $defaultType = $professional->commission_type;

        $this->rows = $professional->services
            ->sortBy('name')
            ->values()
            ->map(static function ($service) use ($defaultCommission, $defaultType): array {
                return [
                    'service_id' => (int) $service->id,
                    'service_name' => (string) $service->name,
                    'sale_commission' => (string) ($service->pivot?->sale_commission ?? $defaultCommission),
                    'commission_type' => (string) ($service->pivot?->commission_type ?? $defaultType),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{
     *     service_id: int,
     *     service_name: string,
     *     sale_commission: float,
     *     commission_type: string
     * }>
     */
    public function payload(): array
    {
        return array_map(static fn (array $row): array => [
            'service_id' => (int) $row['service_id'],
            'service_name' => (string) $row['service_name'],
            'sale_commission' => (float) $row['sale_commission'],
            'commission_type' => (string) $row['commission_type'],
        ], $this->rows);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'rows' => ['required', 'array'],
            'rows.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'rows.*.sale_commission' => ['required', 'numeric', 'min:0'],
            'rows.*.commission_type' => ['required', 'string', Rule::in(['percent', 'amount'])],
        ];
    }
}
