<?php

namespace App\Livewire\Forms;

use App\Models\Appointment;
use App\Models\Service;
use App\Services\Agenda\AppointmentStatusCatalog;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Livewire\Form;

class AppointmentForm extends Form
{
    public ?int $appointmentId = null;

    public ?int $branch_id = null;

    public ?int $client_id = null;

    public ?int $service_id = null;

    public ?int $resource_id = null;

    public ?int $professional_id = null;

    public string $title = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $duration_minutes = '';

    public string $timezone = 'America/Lima';

    public string $price = '';

    public string $currency = 'PEN';

    public string $notes = '';

    public string $status_slug = AppointmentStatusCatalog::PENDING;

    public string $cancellation_reason = '';

    public function resetForm(): void
    {
        $this->appointmentId = null;
        $this->branch_id = null;
        $this->client_id = null;
        $this->service_id = null;
        $this->resource_id = null;
        $this->professional_id = null;
        $this->title = '';
        $this->starts_at = '';
        $this->ends_at = '';
        $this->duration_minutes = '';
        $this->timezone = 'America/Lima';
        $this->price = '';
        $this->currency = 'PEN';
        $this->notes = '';
        $this->status_slug = AppointmentStatusCatalog::PENDING;
        $this->cancellation_reason = '';
    }

    public function fillFromAppointment(Appointment $appointment): void
    {
        $this->appointmentId = $appointment->id;
        $this->branch_id = $appointment->branch_id;
        $this->client_id = $appointment->client_id;
        $this->service_id = $appointment->service_id;
        $this->resource_id = $appointment->resource_id;
        $this->professional_id = $appointment->professional_id;
        $this->title = $appointment->title;
        $this->starts_at = $appointment->starts_at->format('Y-m-d\TH:i');
        $this->ends_at = $appointment->ends_at->format('Y-m-d\TH:i');
        $this->duration_minutes = (string) $appointment->duration_minutes;
        $this->timezone = $appointment->timezone;
        $this->price = (string) $appointment->price;
        $this->currency = $appointment->currency;
        $this->notes = $appointment->notes ?? '';
        $this->status_slug = $appointment->status->slug;
        $this->cancellation_reason = $appointment->cancellation_reason ?? '';
    }

    public function fillFromService(Service $service): void
    {
        $this->service_id = $service->id;
        $this->title = $service->name;
        $this->duration_minutes = (string) $service->duration_minutes;
        $this->price = (string) $service->price;
        $this->currency = 'PEN';
        $this->status_slug = AppointmentStatusCatalog::PENDING;
    }

    /**
     * @return array<string, string|int|float|null>
     */
    public function payload(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'client_id' => $this->client_id,
            'service_id' => $this->service_id,
            'resource_id' => $this->resource_id,
            'professional_id' => $this->professional_id,
            'title' => trim($this->title),
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'duration_minutes' => (int) $this->duration_minutes,
            'timezone' => $this->timezone,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'notes' => trim($this->notes),
            'status_slug' => $this->status_slug,
            'cancellation_reason' => trim($this->cancellation_reason),
        ];
    }

    public function withAvailabilityValidation(): self
    {
        return $this->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                if ($this->starts_at !== '' && $this->ends_at !== '' && $this->ends_at <= $this->starts_at) {
                    $validator->errors()->add('ends_at', 'La hora final debe ser posterior a la hora inicial.');
                }
            });
        });
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'resource_id' => ['nullable', 'integer', Rule::exists('resources', 'id')],
            'professional_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'timezone' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status_slug' => ['required', 'string', Rule::in([
                AppointmentStatusCatalog::PENDING,
                AppointmentStatusCatalog::CONFIRMED,
                AppointmentStatusCatalog::ARRIVED,
                AppointmentStatusCatalog::IN_PROGRESS,
                AppointmentStatusCatalog::COMPLETED,
                AppointmentStatusCatalog::CANCELLED,
                AppointmentStatusCatalog::NO_SHOW,
                AppointmentStatusCatalog::RESCHEDULED,
            ])],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
