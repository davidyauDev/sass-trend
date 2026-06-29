<?php

namespace App\Livewire\Forms;

use App\Models\Service;
use App\Models\ServiceSchedule;
use App\Services\Services\ServicePaymentTypeCatalog;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Livewire\Form;

class ServiceForm extends Form
{
    public ?int $serviceId = null;

    public string $name = '';

    public ?int $service_category_id = null;

    public string $new_category_name = '';

    public string $price = '';

    public string $duration_minutes = '';

    public bool $is_active = true;

    /** @var array<int, int> */
    /** @var list<int> */
    public array $professional_ids = [];

    public bool $is_bookable_online = true;

    public string $description = '';

    public mixed $image = null;

    public ?string $existingImagePath = null;

    public ?int $professional_location_filter_id = null;

    public string $online_payment_type = ServicePaymentTypeCatalog::NOT_ALLOWED;

    public string $deposit_amount = '';

    public string $deposit_percentage = '';

    public bool $is_video_conference = false;

    public bool $is_home_service = false;

    public bool $has_special_schedule = false;

    /** @var array<int, array<string, mixed>> */
    public array $schedules = [];

    public function resetForm(): void
    {
        $this->serviceId = null;
        $this->name = '';
        $this->service_category_id = null;
        $this->new_category_name = '';
        $this->price = '';
        $this->duration_minutes = '';
        $this->is_active = true;
        $this->professional_ids = [];
        $this->is_bookable_online = true;
        $this->description = '';
        $this->image = null;
        $this->existingImagePath = null;
        $this->professional_location_filter_id = null;
        $this->online_payment_type = ServicePaymentTypeCatalog::NOT_ALLOWED;
        $this->deposit_amount = '';
        $this->deposit_percentage = '';
        $this->is_video_conference = false;
        $this->is_home_service = false;
        $this->has_special_schedule = false;
        $this->schedules = self::defaultSchedules();
    }

    public function fillFromService(Service $service): void
    {
        $this->serviceId = $service->id;
        $this->name = $service->name;
        $this->service_category_id = $service->service_category_id;
        $this->new_category_name = '';
        $this->price = (string) $service->price;
        $this->duration_minutes = (string) $service->duration_minutes;
        $this->is_active = $service->is_active;
        $this->professional_ids = $service->professionalProfiles->isNotEmpty()
            ? $service->professionalProfiles
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all()
            : $service->professionals
                ->map(fn ($user): ?int => $user->professionalProfile?->id)
                ->filter()
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all();
        $this->is_bookable_online = $service->is_bookable_online;
        $this->description = $service->description ?? '';
        $this->image = null;
        $this->existingImagePath = $service->image_path;
        $this->professional_location_filter_id = null;
        $this->online_payment_type = $service->online_payment_type ?? ServicePaymentTypeCatalog::NOT_ALLOWED;
        $this->deposit_amount = $service->deposit_amount !== null ? (string) $service->deposit_amount : '';
        $this->deposit_percentage = $service->deposit_percentage !== null ? (string) $service->deposit_percentage : '';
        $this->is_video_conference = $service->is_video_conference;
        $this->is_home_service = $service->is_home_service;
        $this->has_special_schedule = $service->has_special_schedule;

        $storedSchedules = $service->schedules
            ->keyBy('day_of_week')
            ->map(fn (ServiceSchedule $schedule): array => [
                'day_of_week' => $schedule->day_of_week,
                'label' => self::dayLabels()[$schedule->day_of_week],
                'is_active' => $schedule->is_active,
                'starts_at' => $schedule->starts_at ?? '',
                'ends_at' => $schedule->ends_at ?? '',
            ])
            ->all();

        $this->schedules = collect(self::defaultSchedules())
            ->map(fn (array $schedule): array => $storedSchedules[$schedule['day_of_week']] ?? $schedule)
            ->all();
    }

    public function withBusinessValidation(): self
    {
        return $this->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                $price = (float) ($this->price === '' ? 0 : $this->price);
                $depositAmount = (float) ($this->deposit_amount === '' ? 0 : $this->deposit_amount);
                $depositPercentage = (int) ($this->deposit_percentage === '' ? 0 : $this->deposit_percentage);

                if ($this->online_payment_type === ServicePaymentTypeCatalog::REQUIRED && $price === 0.0) {
                    $validator->errors()->add('online_payment_type', 'No puedes exigir pago online en un servicio con precio 0.');
                }

                if ($this->deposit_amount !== '' && $depositAmount > $price) {
                    $validator->errors()->add('deposit_amount', 'El abono no puede ser mayor al precio del servicio.');
                }

                if ($this->deposit_percentage !== '' && $depositPercentage > 100) {
                    $validator->errors()->add('deposit_percentage', 'El porcentaje de abono no puede superar el 100%.');
                }

                if ($this->has_special_schedule) {
                    foreach ($this->schedules as $index => $schedule) {
                        if (! ($schedule['is_active'] ?? false)) {
                            continue;
                        }

                        $startsAt = (string) ($schedule['starts_at'] ?? '');
                        $endsAt = (string) ($schedule['ends_at'] ?? '');

                        if ($startsAt === '') {
                            $validator->errors()->add("schedules.{$index}.starts_at", 'La hora inicio es obligatoria cuando el horario especial está activo.');
                        }

                        if ($endsAt === '') {
                            $validator->errors()->add("schedules.{$index}.ends_at", 'La hora fin es obligatoria cuando el horario especial está activo.');
                        }

                        if ($startsAt !== '' && $endsAt !== '' && $endsAt <= $startsAt) {
                            $validator->errors()->add("schedules.{$index}.ends_at", 'La hora fin debe ser posterior a la hora inicio.');
                        }
                    }
                }
            });
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'name' => trim($this->name),
            'service_category_id' => $this->service_category_id,
            'new_category_name' => $this->normalizeNullableString($this->new_category_name),
            'price' => (float) $this->price,
            'duration_minutes' => (int) $this->duration_minutes,
            'is_active' => $this->is_active,
            'professional_ids' => collect($this->professional_ids)->map(fn (mixed $id): int => (int) $id)->unique()->values()->all(),
            'is_bookable_online' => $this->is_bookable_online,
            'description' => $this->normalizeNullableString($this->description),
            'image' => $this->image,
            'online_payment_type' => $this->online_payment_type,
            'deposit_amount' => $this->normalizeNullableNumeric($this->deposit_amount),
            'deposit_percentage' => $this->normalizeNullableInteger($this->deposit_percentage),
            'is_video_conference' => $this->is_video_conference,
            'is_home_service' => $this->is_home_service,
            'has_special_schedule' => $this->has_special_schedule,
            'schedules' => collect($this->schedules)
                ->map(fn (array $schedule): array => [
                    'day_of_week' => (int) $schedule['day_of_week'],
                    'is_active' => (bool) $schedule['is_active'],
                    'starts_at' => $schedule['is_active'] ? $this->normalizeNullableString((string) $schedule['starts_at']) : null,
                    'ends_at' => $schedule['is_active'] ? $this->normalizeNullableString((string) $schedule['ends_at']) : null,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'service_category_id' => ['required_without:new_category_name', 'nullable', 'integer', Rule::exists('service_categories', 'id')],
            'new_category_name' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'online_payment_type' => ['nullable', 'string', Rule::in(ServicePaymentTypeCatalog::values())],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'professional_ids' => ['array'],
            'professional_ids.*' => ['integer', Rule::exists('professionals', 'id')],
            'is_bookable_online' => ['boolean'],
            'is_active' => ['boolean'],
            'is_video_conference' => ['boolean'],
            'is_home_service' => ['boolean'],
            'has_special_schedule' => ['boolean'],
            'schedules' => ['array', 'size:7'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'schedules.*.is_active' => ['boolean'],
            'schedules.*.starts_at' => ['nullable', 'date_format:H:i'],
            'schedules.*.ends_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function dayLabels(): array
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function defaultSchedules(): array
    {
        return collect(self::dayLabels())
            ->map(fn (string $label, int $day): array => [
                'day_of_week' => $day,
                'label' => $label,
                'is_active' => false,
                'starts_at' => '',
                'ends_at' => '',
            ])
            ->values()
            ->all();
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeNullableNumeric(string $value): ?float
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : (float) $trimmed;
    }

    private function normalizeNullableInteger(string $value): ?int
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : (int) $trimmed;
    }
}
