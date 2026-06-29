<?php

namespace App\Livewire\Forms;

use App\Models\Professional;
use App\Models\ProfessionalSchedule;
use App\Models\User;
use Livewire\Form;

class ProfessionalForm extends Form
{
    public ?int $professionalId = null;

    public string $public_name = '';

    public bool $accepts_online_bookings = false;

    public bool $has_system_access = false;

    public string $email = '';

    public string $bio = '';

    public mixed $photo = null;

    public ?string $existingPhotoPath = null;

    public bool $is_active = true;

    /** @var array<int, int> */
    public array $service_ids = [];

    /** @var array<int, array<string, mixed>> */
    public array $schedules = [];

    public function resetForm(): void
    {
        $this->professionalId = null;
        $this->public_name = '';
        $this->accepts_online_bookings = false;
        $this->has_system_access = false;
        $this->email = '';
        $this->bio = '';
        $this->photo = null;
        $this->existingPhotoPath = null;
        $this->is_active = true;
        $this->service_ids = [];
        $this->schedules = self::defaultSchedules();
    }

    public function fillFromProfessional(Professional $professional): void
    {
        $this->professionalId = $professional->id;
        $this->public_name = $professional->public_name;
        $this->accepts_online_bookings = $professional->accepts_online_bookings;
        $this->has_system_access = $professional->has_system_access;
        $this->email = $professional->email ?? ($professional->user instanceof User ? $professional->user->email : '');
        $this->bio = $professional->bio ?? '';
        $this->photo = null;
        $this->existingPhotoPath = $professional->photo_path;
        $this->is_active = $professional->is_active;
        $this->service_ids = $professional->services->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all();

        $storedSchedules = $professional->schedules
            ->keyBy('day_of_week')
            ->map(fn (ProfessionalSchedule $schedule): array => [
                'day_of_week' => $schedule->day_of_week,
                'label' => self::dayLabels()[$schedule->day_of_week],
                'is_working' => $schedule->is_working,
                'starts_at' => $schedule->starts_at ?? '',
                'ends_at' => $schedule->ends_at ?? '',
                'breaks' => $schedule->breaks
                    ->map(fn ($break): array => [
                        'starts_at' => $break->starts_at ?? '',
                        'ends_at' => $break->ends_at ?? '',
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        $this->schedules = collect(self::defaultSchedules())
            ->map(fn (array $schedule): array => $storedSchedules[$schedule['day_of_week']] ?? $schedule)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'public_name' => trim($this->public_name),
            'accepts_online_bookings' => $this->accepts_online_bookings,
            'has_system_access' => $this->has_system_access,
            'email' => $this->normalizeNullableString($this->email),
            'bio' => $this->normalizeNullableString($this->bio),
            'photo' => $this->photo,
            'is_active' => $this->is_active,
            'service_ids' => collect($this->service_ids)
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
            'schedules' => collect($this->schedules)
                ->map(fn (array $schedule): array => [
                    'day_of_week' => (int) $schedule['day_of_week'],
                    'is_working' => (bool) $schedule['is_working'],
                    'starts_at' => $schedule['is_working'] ? $this->normalizeNullableString((string) $schedule['starts_at']) : null,
                    'ends_at' => $schedule['is_working'] ? $this->normalizeNullableString((string) $schedule['ends_at']) : null,
                    'breaks' => array_values(array_map(fn (array $break): array => [
                        'starts_at' => $this->normalizeNullableString((string) ($break['starts_at'] ?? '')),
                        'ends_at' => $this->normalizeNullableString((string) ($break['ends_at'] ?? '')),
                    ], (array) ($schedule['breaks'] ?? []))),
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
            'public_name' => ['required', 'string', 'max:255'],
            'accepts_online_bookings' => ['boolean'],
            'has_system_access' => ['boolean'],
            'email' => ['nullable', 'email', 'max:255'],
            'bio' => ['nullable', 'string', 'max:600'],
            'photo' => ['nullable', 'image', 'max:3072'],
            'is_active' => ['boolean'],
            'service_ids' => ['array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'schedules' => ['required', 'array', 'size:7'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'schedules.*.is_working' => ['boolean'],
            'schedules.*.starts_at' => ['nullable', 'date_format:H:i'],
            'schedules.*.ends_at' => ['nullable', 'date_format:H:i'],
            'schedules.*.breaks' => ['array'],
            'schedules.*.breaks.*.starts_at' => ['nullable', 'date_format:H:i'],
            'schedules.*.breaks.*.ends_at' => ['nullable', 'date_format:H:i'],
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
                'is_working' => $day !== 7,
                'starts_at' => $day !== 7 ? '09:00' : '',
                'ends_at' => $day !== 7 ? '20:00' : '',
                'breaks' => [],
            ])
            ->values()
            ->all();
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
