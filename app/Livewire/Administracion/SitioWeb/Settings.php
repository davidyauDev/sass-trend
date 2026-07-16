<?php

namespace App\Livewire\Administracion\SitioWeb;

use App\Actions\Website\UpdateWebsiteSettingAction;
use App\Livewire\Forms\WebsiteSettingsForm;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\User;
use App\Models\WebsiteSetting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Perfil web')]
class Settings extends Component
{
    use WithFileUploads;

    public WebsiteSettingsForm $form;

    public int $activeStep = 0;

    public ?string $editingSection = null;

    /** @var array<int, string> */
    public array $dayNames = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public function mount(): void
    {
        abort_unless($this->authUser()->isAdministrator() && tenant() !== null, 403);

        $this->form->fillFromSettings($this->settings());

        if ($this->form->primary_location_id === null) {
            $this->form->primary_location_id = $this->locations()->first()?->id;
        }

        $this->loadSchedule();
        $this->loadLocationDetails();
    }

    public function save(UpdateWebsiteSettingAction $updateWebsiteSetting): void
    {
        if (count($this->form->existingGalleryPaths) + count($this->form->gallery_uploads) > 10) {
            $this->addError('form.gallery_uploads', 'Puedes publicar un máximo de 10 imágenes.');

            return;
        }

        $this->form->validate();

        $settings = $updateWebsiteSetting->handle($this->settings(), $this->form->payload());

        $this->form->fillFromSettings($settings);

        Flux::toast(
            variant: 'success',
            text: 'Tu perfil web fue actualizado correctamente.',
        );
    }

    public function saveAndContinue(UpdateWebsiteSettingAction $updateWebsiteSetting): void
    {
        $this->save($updateWebsiteSetting);

        if ($this->getErrorBag()->isEmpty()) {
            $this->activeStep = min(6, $this->activeStep + 1);
        }
    }

    public function openEditor(string $section): void
    {
        abort_unless(in_array($section, ['essentials', 'description', 'location', 'hours', 'images'], true), 404);

        $this->resetValidation();
        $this->editingSection = $section;
    }

    public function closeEditor(): void
    {
        $this->resetValidation();
        $this->form->fillFromSettings($this->settings());
        $this->loadSchedule();
        $this->loadLocationDetails();
        $this->editingSection = null;
    }

    public function saveSection(UpdateWebsiteSettingAction $updateWebsiteSetting): void
    {
        if ($this->editingSection === 'description' && mb_strlen(trim($this->form->description)) < 200) {
            $this->addError('form.description', 'La descripción debe tener al menos 200 caracteres.');

            return;
        }

        if ($this->editingSection === 'hours') {
            foreach ($this->form->schedule as $day => $hours) {
                if ($hours['is_open'] && $hours['closes_at'] <= $hours['opens_at']) {
                    $this->addError("form.schedule.{$day}.closes_at", 'La hora de cierre debe ser posterior a la apertura.');
                }
            }

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        $this->save($updateWebsiteSetting);

        if ($this->getErrorBag()->isEmpty()) {
            $this->editingSection = null;
        }
    }

    public function goToStep(int $step): void
    {
        $this->activeStep = max(0, min(6, $step));
    }

    public function updatedFormPrimaryLocationId(): void
    {
        $this->loadSchedule();
        $this->loadLocationDetails();
    }

    public function removeGalleryImage(int $index): void
    {
        $path = $this->form->existingGalleryPaths[$index] ?? null;

        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);

        $paths = $this->form->existingGalleryPaths;
        unset($paths[$index]);
        $this->form->existingGalleryPaths = array_values($paths);
        $this->settings()->update(['gallery_paths' => $this->form->existingGalleryPaths]);
    }

    public function makeGalleryCover(int $index): void
    {
        $path = $this->form->existingGalleryPaths[$index] ?? null;

        if ($path === null || $index === 0) {
            return;
        }

        $paths = $this->form->existingGalleryPaths;
        unset($paths[$index]);
        array_unshift($paths, $path);

        $this->form->existingGalleryPaths = array_values($paths);
        $this->settings()->update(['gallery_paths' => $this->form->existingGalleryPaths]);
    }

    #[Computed]
    public function bookingUrl(): string
    {
        return route('perfil.publico', ['tenant' => tenant('slug')]);
    }

    #[Computed]
    public function settings(): WebsiteSetting
    {
        return WebsiteSetting::current();
    }

    /**
     * @return Collection<int, Location>
     */
    #[Computed]
    public function locations(): Collection
    {
        return Location::query()
            ->with('schedules')
            ->where('is_active', true)
            ->where('accepts_online_bookings', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function completionPercentage(): int
    {
        $checks = [
            filled($this->form->site_name) && filled($this->form->contact_phone),
            $this->form->primary_location_id !== null,
            collect($this->form->schedule)->contains(fn (array $day): bool => $day['is_open']),
            count($this->form->existingGalleryPaths) + count($this->form->gallery_uploads) >= 3,
            count($this->form->amenities) + count($this->form->highlights) > 0,
            mb_strlen(trim($this->form->description)) >= 80,
        ];

        return (int) round(collect($checks)->filter()->count() / count($checks) * 100);
    }

    /**
     * @return array{appointments: int, value: float, clients: int, roi: float}
     */
    #[Computed]
    public function performanceMetrics(): array
    {
        $webAppointments = Appointment::query()
            ->where('reference_code', 'like', 'WEB-%');

        $appointments = (clone $webAppointments)->count();
        $value = (float) (clone $webAppointments)->sum('price');
        $clients = (clone $webAppointments)->distinct()->count('client_id');

        return [
            'appointments' => $appointments,
            'value' => $value,
            'clients' => $clients,
            'roi' => 0.0,
        ];
    }

    /**
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function recentWebAppointments(): Collection
    {
        return Appointment::query()
            ->with(['client', 'service'])
            ->where('reference_code', 'like', 'WEB-%')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.administracion.sitio-web.settings')
            ->layout('layouts.app');
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function loadSchedule(): void
    {
        $location = $this->locations()->firstWhere('id', $this->form->primary_location_id);

        $this->form->schedule = collect($this->dayNames)
            ->mapWithKeys(function (string $name, int $day) use ($location): array {
                $schedule = $location?->schedules->firstWhere('day_of_week', $day);

                if (! $schedule instanceof LocationSchedule) {
                    return [$day => [
                        'is_open' => $day <= 6,
                        'opens_at' => '09:00',
                        'closes_at' => '18:00',
                    ]];
                }

                return [$day => [
                    'is_open' => $schedule->is_open,
                    'opens_at' => $schedule->opens_at !== null ? substr($schedule->opens_at, 0, 5) : '09:00',
                    'closes_at' => $schedule->closes_at !== null ? substr($schedule->closes_at, 0, 5) : '18:00',
                ]];
            })
            ->all();
    }

    private function loadLocationDetails(): void
    {
        $location = $this->locations()->firstWhere('id', $this->form->primary_location_id);

        if (! $location instanceof Location) {
            $this->form->location_address = '';

            return;
        }

        $this->form->location_address = $location->address;
    }
}
