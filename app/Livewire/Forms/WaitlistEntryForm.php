<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class WaitlistEntryForm extends Form
{
    public ?int $branchId = null;

    public ?int $clientId = null;

    public ?int $serviceId = null;

    public ?int $professionalId = null;

    public string $desiredDate = '';

    public string $availableFrom = '09:00';

    public string $availableUntil = '18:00';

    public string $notes = '';

    public function resetForm(): void
    {
        $this->reset();
        $this->desiredDate = now()->toDateString();
        $this->availableFrom = '09:00';
        $this->availableUntil = '18:00';
    }

    /** @return array<string, int|string|null> */
    public function payload(): array
    {
        return [
            'branch_id' => $this->branchId,
            'client_id' => $this->clientId,
            'service_id' => $this->serviceId,
            'professional_id' => $this->professionalId,
            'desired_date' => $this->desiredDate,
            'available_from' => $this->availableFrom,
            'available_until' => $this->availableUntil,
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
        ];
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'branchId' => ['required', 'integer', Rule::exists('branches', 'id')],
            'clientId' => ['required', 'integer', Rule::exists('clients', 'id')],
            'serviceId' => ['required', 'integer', Rule::exists('services', 'id')],
            'professionalId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'desiredDate' => ['required', 'date'],
            'availableFrom' => ['required', 'date_format:H:i'],
            'availableUntil' => ['required', 'date_format:H:i', 'after:form.availableFrom'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
