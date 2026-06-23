<?php

namespace App\Livewire\Forms;

use App\Models\CommissionRule;
use App\Services\Commissions\CommissionRulePriorityCatalog;
use App\Services\Commissions\CommissionSourceCatalog;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CommissionRuleForm extends Form
{
    public ?int $commissionRuleId = null;

    public ?int $branch_id = null;

    public ?int $service_id = null;

    public ?int $service_category_id = null;

    public ?int $commission_type_id = null;

    public string $name = '';

    public string $slug = '';

    public string $priority = '60';

    public string $source_type = CommissionSourceCatalog::APPOINTMENT;

    public string $calculation_mode = 'percentage';

    public string $percentage = '';

    public string $fixed_amount = '';

    public string $min_revenue = '';

    public string $min_quantity = '';

    public bool $is_active = true;

    public string $notes = '';

    public function resetForm(): void
    {
        $this->commissionRuleId = null;
        $this->branch_id = null;
        $this->service_id = null;
        $this->service_category_id = null;
        $this->commission_type_id = null;
        $this->name = '';
        $this->slug = '';
        $this->priority = (string) CommissionRulePriorityCatalog::COMPANY_DEFAULT;
        $this->source_type = CommissionSourceCatalog::APPOINTMENT;
        $this->calculation_mode = 'percentage';
        $this->percentage = '';
        $this->fixed_amount = '';
        $this->min_revenue = '';
        $this->min_quantity = '';
        $this->is_active = true;
        $this->notes = '';
    }

    public function fillFromRule(CommissionRule $rule): void
    {
        $this->commissionRuleId = $rule->id;
        $this->branch_id = $rule->branch_id;
        $this->service_id = $rule->service_id;
        $this->service_category_id = $rule->service_category_id;
        $this->commission_type_id = $rule->commission_type_id;
        $this->name = $rule->name;
        $this->slug = $rule->slug;
        $this->priority = (string) $rule->priority;
        $this->source_type = $rule->source_type ?? CommissionSourceCatalog::APPOINTMENT;
        $this->calculation_mode = $rule->calculation_mode;
        $this->percentage = $rule->percentage !== null ? (string) $rule->percentage : '';
        $this->fixed_amount = $rule->fixed_amount !== null ? (string) $rule->fixed_amount : '';
        $this->min_revenue = $rule->min_revenue !== null ? (string) $rule->min_revenue : '';
        $this->min_quantity = $rule->min_quantity !== null ? (string) $rule->min_quantity : '';
        $this->is_active = $rule->is_active;
        $this->notes = $rule->notes ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'service_id' => $this->service_id,
            'service_category_id' => $this->service_category_id,
            'commission_type_id' => $this->commission_type_id,
            'name' => trim($this->name),
            'slug' => trim($this->slug),
            'priority' => (int) $this->priority,
            'source_type' => $this->source_type,
            'calculation_mode' => $this->calculation_mode,
            'percentage' => $this->percentage === '' ? null : (float) $this->percentage,
            'fixed_amount' => $this->fixed_amount === '' ? null : (float) $this->fixed_amount,
            'min_revenue' => $this->min_revenue === '' ? null : (float) $this->min_revenue,
            'min_quantity' => $this->min_quantity === '' ? null : (int) $this->min_quantity,
            'condition_json' => null,
            'is_active' => $this->is_active,
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'service_category_id' => ['nullable', 'integer', Rule::exists('service_categories', 'id')],
            'commission_type_id' => ['required', 'integer', Rule::exists('commission_types', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:1', 'max:100'],
            'source_type' => ['required', 'string', Rule::in(CommissionSourceCatalog::values())],
            'calculation_mode' => ['required', 'string', Rule::in(['percentage', 'fixed', 'profit', 'quantity'])],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'min_revenue' => ['nullable', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
