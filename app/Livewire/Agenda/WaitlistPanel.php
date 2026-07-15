<?php

namespace App\Livewire\Agenda;

use App\Actions\Agenda\CreateWaitlistEntryAction;
use App\Livewire\Forms\WaitlistEntryForm;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WaitlistPanel extends Component
{
    public WaitlistEntryForm $form;

    public bool $open = false;

    public bool $creating = false;

    public string $tab = 'waiting';

    public string $dateFilter = 'upcoming';

    public string $sort = 'oldest';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Appointment::class), 403);
        $this->form->resetForm();
    }

    public function openPanel(): void
    {
        $this->open = true;
    }

    public function closePanel(): void
    {
        $this->open = false;
        $this->creating = false;
        $this->resetValidation();
    }

    public function openCreate(): void
    {
        $this->form->resetForm();
        $this->form->branchId = $this->branches()->first()?->id;
        $this->creating = true;
        $this->resetValidation();
    }

    public function cancelCreate(): void
    {
        $this->creating = false;
        $this->form->resetForm();
        $this->resetValidation();
    }

    public function save(CreateWaitlistEntryAction $createWaitlistEntry): void
    {
        $this->authorize('create', Appointment::class);
        $this->form->validate();

        $createWaitlistEntry->handle($this->authUser(), $this->form->payload());

        $this->creating = false;
        $this->form->resetForm();
        Flux::toast(variant: 'success', text: 'Cliente agregado a la lista de espera.');
    }

    public function bookNow(int $entryId): void
    {
        $entry = WaitlistEntry::query()->findOrFail($entryId);

        abort_unless($entry->status === WaitlistEntry::STATUS_WAITING, 422);

        $this->open = false;
        $this->dispatch('agenda-book-waitlist', entryId: $entry->id);
    }

    public function durationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return $remainingMinutes.' min';
        }

        $label = $hours.' h';

        return $remainingMinutes > 0 ? $label.' '.$remainingMinutes.' min' : $label;
    }

    /** @return Collection<int, WaitlistEntry> */
    #[Computed]
    public function entries(): Collection
    {
        return WaitlistEntry::query()
            ->with(['branch', 'client', 'service', 'professional'])
            ->when($this->tab === 'waiting', fn (Builder $query): Builder => $query
                ->where('status', WaitlistEntry::STATUS_WAITING)
                ->whereDate('desired_date', '>=', today()))
            ->when($this->tab === 'expired', fn (Builder $query): Builder => $query
                ->where('status', WaitlistEntry::STATUS_WAITING)
                ->whereDate('desired_date', '<', today()))
            ->when($this->tab === 'booked', fn (Builder $query): Builder => $query
                ->where('status', WaitlistEntry::STATUS_BOOKED))
            ->when($this->dateFilter === 'today', fn (Builder $query): Builder => $query->whereDate('desired_date', today()))
            ->when($this->dateFilter === 'week', fn (Builder $query): Builder => $query->whereBetween('desired_date', [today(), today()->addDays(7)]))
            ->orderBy('created_at', $this->sort === 'newest' ? 'desc' : 'asc')
            ->get();
    }

    /** @return array{waiting: int, expired: int, booked: int} */
    #[Computed]
    public function counts(): array
    {
        return [
            'waiting' => WaitlistEntry::query()->where('status', WaitlistEntry::STATUS_WAITING)->whereDate('desired_date', '>=', today())->count(),
            'expired' => WaitlistEntry::query()->where('status', WaitlistEntry::STATUS_WAITING)->whereDate('desired_date', '<', today())->count(),
            'booked' => WaitlistEntry::query()->where('status', WaitlistEntry::STATUS_BOOKED)->count(),
        ];
    }

    /** @return SupportCollection<int, Branch> */
    #[Computed]
    public function branches(): SupportCollection
    {
        return Branch::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return SupportCollection<int, Client> */
    #[Computed]
    public function clients(): SupportCollection
    {
        return Client::query()->orderBy('first_name')->orderBy('last_name')->get();
    }

    /** @return SupportCollection<int, Service> */
    #[Computed]
    public function services(): SupportCollection
    {
        return Service::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return SupportCollection<int, User> */
    #[Computed]
    public function professionals(): SupportCollection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('professionalProfile', fn (Builder $query): Builder => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.agenda.waitlist-panel');
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
