<?php

namespace App\Livewire\Clients;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\DeleteClientAction;
use App\Models\Client;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Clientes')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $sortBy = 'created_at';

    /** @var 'asc'|'desc' */
    #[Url]
    public string $sortDirection = 'desc';

    #[Url]
    public int $perPage = 10;

    /** @var array<string, string|null> */
    public array $form = [
        'first_name' => '',
        'last_name' => '',
        'birth_date' => '',
        'age' => '',
        'dni' => '',
        'gender' => '',
        'client_number' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'district' => '',
        'city' => '',
    ];

    public ?int $selectedClientId = null;

    public ?int $clientIdPendingDeletion = null;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 25, 50], true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('create-client')->show();
    }

    public function closeCreateModal(): void
    {
        $this->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function save(CreateClientAction $createClient): void
    {
        $this->form = $this->normalizeForm($this->form);

        $validated = $this->validate()['form'];

        $createClient->handle($validated);

        $this->closeCreateModal();
        $this->modal('create-client')->close();

        Flux::toast(variant: 'success', text: 'Cliente creado correctamente.');
    }

    public function showClient(int $clientId): void
    {
        $this->selectedClientId = $clientId;

        $this->modal('show-client')->show();
    }

    public function closeShowClientModal(): void
    {
        $this->selectedClientId = null;
    }

    public function confirmDelete(int $clientId): void
    {
        $this->clientIdPendingDeletion = $clientId;

        $this->modal('delete-client')->show();
    }

    public function closeDeleteModal(): void
    {
        $this->clientIdPendingDeletion = null;
    }

    public function delete(DeleteClientAction $deleteClient): void
    {
        $client = Client::findOrFail($this->clientIdPendingDeletion);

        $deleteClient->handle($client);

        $this->closeDeleteModal();
        $this->modal('delete-client')->close();

        if ($this->selectedClientId === $client->id) {
            $this->selectedClientId = null;
        }

        Flux::toast(variant: 'success', text: 'Cliente eliminado correctamente.');

        if ($this->clients()->isEmpty() && $this->getPage() > 1) {
            $this->previousPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search']);
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function notifyPendingFeature(string $feature): void
    {
        Flux::toast(variant: 'success', text: "{$feature} estará disponible pronto.");
    }

    public function render(): View
    {
        $clients = $this->clients();

        return view('livewire.clients.index', [
            'clients' => $clients,
            'selectedClient' => $this->selectedClientId ? Client::find($this->selectedClientId) : null,
            'clientPendingDeletion' => $this->clientIdPendingDeletion ? Client::find($this->clientIdPendingDeletion) : null,
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'form.first_name' => ['required', 'string', 'max:255'],
            'form.last_name' => ['required', 'string', 'max:255'],
            'form.birth_date' => ['nullable', 'date'],
            'form.age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'form.dni' => ['nullable', 'string', 'max:50', 'unique:clients,dni'],
            'form.gender' => ['nullable', 'string', 'max:50'],
            'form.client_number' => ['nullable', 'string', 'max:50', 'unique:clients,client_number'],
            'form.email' => ['nullable', 'email', 'max:255', 'unique:clients,email'],
            'form.phone' => ['nullable', 'string', 'max:50'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.district' => ['nullable', 'string', 'max:255'],
            'form.city' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone',
            'dni',
            'created_at',
        ];
    }

    private function resetForm(): void
    {
        $this->form = [
            'first_name' => '',
            'last_name' => '',
            'birth_date' => '',
            'age' => '',
            'dni' => '',
            'gender' => '',
            'client_number' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'district' => '',
            'city' => '',
        ];
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array<string, string|null>
     */
    private function normalizeForm(array $data): array
    {
        return collect($data)
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();
    }

    /** @return LengthAwarePaginator<int, Client> */
    private function clients(): LengthAwarePaginator
    {
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return Client::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = "%{$this->search}%";

                $query->where(function (Builder $searchQuery) use ($term): void {
                    $searchQuery
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('dni', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->orderBy($this->sortBy, $sortDirection)
            ->paginate($this->perPage);
    }
}
