<?php

namespace App\Livewire\Administracion\Empresa;

use App\Actions\Website\UpdateWebsiteSettingAction;
use App\Livewire\Forms\WebsiteSettingsForm;
use App\Models\Location;
use App\Models\User;
use App\Models\WebsiteSetting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Empresa')]
class Settings extends Component
{
    use WithFileUploads;

    public WebsiteSettingsForm $form;

    public function mount(): void
    {
        abort_unless($this->authUser()->isAdministrator() && tenant() !== null, 403);

        $this->form->fillFromSettings($this->settings());
    }

    public function save(UpdateWebsiteSettingAction $updateWebsiteSetting): void
    {
        $this->form->validate();

        $settings = $updateWebsiteSetting->handle($this->settings(), $this->form->payload());

        $this->form->fillFromSettings($settings);

        Flux::toast(
            variant: 'success',
            text: 'La configuración de empresa fue actualizada correctamente.',
        );
    }

    #[Computed]
    public function bookingUrl(): string
    {
        return route('reservas.index', ['tenant' => tenant('slug')]);
    }

    #[Computed]
    public function onlineLocationsCount(): int
    {
        return Location::query()
            ->where('is_active', true)
            ->where('accepts_online_bookings', true)
            ->whereNotNull('branch_id')
            ->count();
    }

    #[Computed]
    public function settings(): WebsiteSetting
    {
        return WebsiteSetting::current();
    }

    public function render(): View
    {
        return view('livewire.administracion.empresa.settings')
            ->layout('layouts.app');
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
