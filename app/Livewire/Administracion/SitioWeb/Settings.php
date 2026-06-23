<?php

namespace App\Livewire\Administracion\SitioWeb;

use App\Actions\Website\UpdateWebsiteSettingAction;
use App\Livewire\Forms\WebsiteSettingsForm;
use App\Models\User;
use App\Models\WebsiteSetting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Sitio web')]
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
            text: 'La configuracion del sitio web fue actualizada correctamente.',
        );
    }

    #[Computed]
    public function bookingUrl(): string
    {
        return route('reservas.index', ['tenant' => tenant('slug')]);
    }

    #[Computed]
    public function settings(): WebsiteSetting
    {
        return WebsiteSetting::current();
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
}
