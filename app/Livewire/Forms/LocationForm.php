<?php

namespace App\Livewire\Forms;

use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\WebsiteSetting;
use Illuminate\Validation\Validator;
use Livewire\Form;

class LocationForm extends Form
{
    public ?int $locationId = null;

    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $timezone = '';

    public ?int $branch_id = null;

    public string $site_name = '';

    public string $tagline = '';

    public string $description = '';

    public mixed $logo = null;

    public ?string $existingLogoPath = null;

    public mixed $hero_image = null;

    public ?string $existingHeroImagePath = null;

    public string $primary_color = '#4b3626';

    public string $contact_phone = '';

    public string $contact_email = '';

    public string $whatsapp_phone = '';

    public string $instagram_url = '';

    public string $facebook_url = '';

    public string $tiktok_url = '';

    public string $booking_button_label = 'Reservar ahora';

    public string $booking_intro = '';

    public bool $accepts_online_bookings = false;

    public string $secondary_phone = '';

    public bool $is_active = true;

    public mixed $image = null;

    /** @var array<int, array<string, mixed>> */
    public array $schedules = [];

    public function resetForm(): void
    {
        $defaults = $this->sharedWebsiteDefaults();

        $this->locationId = null;
        $this->name = '';
        $this->address = '';
        $this->phone = '';
        $this->email = '';
        $this->timezone = '';
        $this->branch_id = null;
        $this->site_name = $defaults['site_name'];
        $this->tagline = $defaults['tagline'];
        $this->description = $defaults['description'];
        $this->logo = null;
        $this->existingLogoPath = null;
        $this->hero_image = null;
        $this->existingHeroImagePath = null;
        $this->primary_color = $defaults['primary_color'];
        $this->contact_phone = $defaults['contact_phone'];
        $this->contact_email = $defaults['contact_email'];
        $this->whatsapp_phone = $defaults['whatsapp_phone'];
        $this->instagram_url = $defaults['instagram_url'];
        $this->facebook_url = $defaults['facebook_url'];
        $this->tiktok_url = $defaults['tiktok_url'];
        $this->booking_button_label = $defaults['booking_button_label'];
        $this->booking_intro = $defaults['booking_intro'];
        $this->accepts_online_bookings = false;
        $this->secondary_phone = '';
        $this->is_active = true;
        $this->image = null;
        $this->schedules = self::defaultSchedules();
    }

    public function fillFromLocation(Location $location): void
    {
        $defaults = $this->sharedWebsiteDefaults();

        $this->locationId = $location->id;
        $this->name = $location->name;
        $this->address = $location->address;
        $this->phone = $location->phone ?? '';
        $this->email = $location->email ?? '';
        $this->timezone = $location->timezone ?? '';
        $this->branch_id = $location->branch_id;
        $this->site_name = $location->site_name ?? $defaults['site_name'] ?? $location->name;
        $this->tagline = $location->tagline ?? $defaults['tagline'];
        $this->description = $location->description ?? $defaults['description'];
        $this->logo = null;
        $this->existingLogoPath = $location->logo_path;
        $this->hero_image = null;
        $this->existingHeroImagePath = $location->hero_image_path ?? $location->image_path;
        $this->primary_color = $location->primary_color ?? $defaults['primary_color'];
        $this->contact_phone = $location->contact_phone ?? $defaults['contact_phone'];
        $this->contact_email = $location->contact_email ?? $defaults['contact_email'];
        $this->whatsapp_phone = $location->whatsapp_phone ?? $defaults['whatsapp_phone'];
        $this->instagram_url = $location->instagram_url ?? $defaults['instagram_url'];
        $this->facebook_url = $location->facebook_url ?? $defaults['facebook_url'];
        $this->tiktok_url = $location->tiktok_url ?? $defaults['tiktok_url'];
        $this->booking_button_label = $location->booking_button_label ?? $defaults['booking_button_label'];
        $this->booking_intro = $location->booking_intro ?? $defaults['booking_intro'];
        $this->accepts_online_bookings = $location->accepts_online_bookings;
        $this->secondary_phone = $location->secondary_phone ?? '';
        $this->is_active = $location->is_active;

        $storedSchedules = $location->schedules
            ->keyBy('day_of_week')
            ->map(fn (LocationSchedule $schedule): array => [
                'day_of_week' => $schedule->day_of_week,
                'label' => self::dayLabels()[$schedule->day_of_week],
                'is_open' => $schedule->is_open,
                'opens_at' => $schedule->opens_at ?? '',
                'closes_at' => $schedule->closes_at ?? '',
            ])
            ->all();

        $this->schedules = collect(self::defaultSchedules())
            ->map(fn (array $schedule): array => $storedSchedules[$schedule['day_of_week']] ?? $schedule)
            ->all();
    }

    public function withScheduleValidation(): self
    {
        return $this->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                foreach ($this->schedules as $index => $schedule) {
                    if (! ($schedule['is_open'] ?? false)) {
                        continue;
                    }

                    $opensAt = (string) ($schedule['opens_at'] ?? '');
                    $closesAt = (string) ($schedule['closes_at'] ?? '');

                    if ($opensAt === '') {
                        $validator->errors()->add("schedules.{$index}.opens_at", 'La hora de apertura es obligatoria cuando el día está activo.');
                    }

                    if ($closesAt === '') {
                        $validator->errors()->add("schedules.{$index}.closes_at", 'La hora de cierre es obligatoria cuando el día está activo.');
                    }

                    if ($opensAt !== '' && $closesAt !== '' && $closesAt <= $opensAt) {
                        $validator->errors()->add("schedules.{$index}.closes_at", 'La hora de cierre debe ser posterior a la hora de apertura.');
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
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->normalizeNullableString($this->phone),
            'email' => $this->normalizeNullableString($this->email),
            'timezone' => $this->normalizeNullableString($this->timezone),
            'branch_id' => $this->branch_id,
            'site_name' => $this->normalizeNullableString($this->site_name),
            'tagline' => $this->normalizeNullableString($this->tagline),
            'description' => $this->normalizeNullableString($this->description),
            'logo' => $this->logo,
            'hero_image' => $this->hero_image,
            'primary_color' => trim($this->primary_color),
            'contact_phone' => $this->normalizeNullableString($this->contact_phone),
            'contact_email' => $this->normalizeNullableString($this->contact_email),
            'whatsapp_phone' => $this->normalizeNullableString($this->whatsapp_phone),
            'instagram_url' => $this->normalizeNullableString($this->instagram_url),
            'facebook_url' => $this->normalizeNullableString($this->facebook_url),
            'tiktok_url' => $this->normalizeNullableString($this->tiktok_url),
            'booking_button_label' => trim($this->booking_button_label),
            'booking_intro' => $this->normalizeNullableString($this->booking_intro),
            'accepts_online_bookings' => $this->accepts_online_bookings,
            'secondary_phone' => $this->normalizeNullableString($this->secondary_phone),
            'is_active' => $this->is_active,
            'schedules' => collect($this->schedules)
                ->map(fn (array $schedule): array => [
                    'day_of_week' => (int) $schedule['day_of_week'],
                    'is_open' => (bool) $schedule['is_open'],
                    'opens_at' => $schedule['is_open'] ? $this->normalizeNullableString((string) $schedule['opens_at']) : null,
                    'closes_at' => $schedule['is_open'] ? $this->normalizeNullableString((string) $schedule['closes_at']) : null,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:50'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'booking_button_label' => ['nullable', 'string', 'max:80'],
            'booking_intro' => ['nullable', 'string', 'max:1000'],
            'accepts_online_bookings' => ['boolean'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'schedules' => ['required', 'array', 'size:7'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'schedules.*.is_open' => ['boolean'],
            'schedules.*.opens_at' => ['nullable', 'date_format:H:i'],
            'schedules.*.closes_at' => ['nullable', 'date_format:H:i'],
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
                'is_open' => false,
                'opens_at' => '',
                'closes_at' => '',
            ])
            ->values()
            ->all();
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<string, string>
     */
    private function sharedWebsiteDefaults(): array
    {
        $settings = WebsiteSetting::current();

        return [
            'site_name' => $settings->site_name,
            'tagline' => $settings->tagline ?? '',
            'description' => $settings->description ?? '',
            'primary_color' => $settings->primary_color,
            'contact_phone' => $settings->contact_phone ?? '',
            'contact_email' => $settings->contact_email ?? '',
            'whatsapp_phone' => $settings->whatsapp_phone ?? '',
            'instagram_url' => $settings->instagram_url ?? '',
            'facebook_url' => $settings->facebook_url ?? '',
            'tiktok_url' => $settings->tiktok_url ?? '',
            'booking_button_label' => $settings->booking_button_label,
            'booking_intro' => $settings->booking_intro ?? '',
        ];
    }
}
