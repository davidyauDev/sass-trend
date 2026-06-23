<?php

namespace App\Livewire\Forms;

use App\Models\ProfessionalGroup;
use Livewire\Form;

class ProfessionalGroupForm extends Form
{
    public ?int $groupId = null;

    public string $name = '';

    public ?int $location_id = null;

    public bool $is_active = true;

    /** @var array<int, int> */
    public array $member_ids = [];

    public function resetForm(): void
    {
        $this->groupId = null;
        $this->name = '';
        $this->location_id = null;
        $this->is_active = true;
        $this->member_ids = [];
    }

    public function fillFromGroup(ProfessionalGroup $group): void
    {
        $this->groupId = $group->id;
        $this->name = $group->name;
        $this->location_id = $group->location_id;
        $this->is_active = $group->is_active;
        $this->member_ids = $group->professionals->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all();
    }

    /**
     * @return array{name:string,location_id:int,is_active:bool,member_ids:list<int>}
     */
    public function payload(): array
    {
        return [
            'name' => trim($this->name),
            'location_id' => (int) $this->location_id,
            'is_active' => $this->is_active,
            'member_ids' => collect($this->member_ids)
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->pipe(fn ($ids): array => array_values($ids->all())),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'is_active' => ['boolean'],
            'member_ids' => ['array'],
            'member_ids.*' => ['integer', 'exists:professionals,id'],
        ];
    }
}
