<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\AppointmentNote;
use App\Models\AppointmentPayment;
use App\Models\AppointmentStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Resource;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\User;
use App\Services\Agenda\AppointmentStatusCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AgendaDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $statuses = $this->seedStatuses();
            $branches = $this->seedBranches();
            $resources = $this->seedResources($branches);
            $appointments = $this->seedAppointments($statuses, $branches, $resources);

            $this->seedNotes($appointments);
            $this->seedPayments($appointments);
            $this->seedBlocks($branches, $resources);
            $this->seedHistory($appointments);
        });
    }

    /**
     * @return array<string, AppointmentStatus>
     */
    private function seedStatuses(): array
    {
        $statuses = [];

        foreach (AppointmentStatusCatalog::definitions() as $definition) {
            $status = AppointmentStatus::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'color' => $definition['color'],
                    'sort_order' => $definition['sort_order'],
                    'is_terminal' => $definition['is_terminal'],
                ],
            );

            $statuses[$definition['slug']] = $status;
        }

        return $statuses;
    }

    /**
     * @return array<string, Branch>
     */
    private function seedBranches(): array
    {
        $definitions = [
            ['code' => 'miraflores', 'name' => 'Miraflores', 'slug' => 'miraflores', 'address' => 'Av. Larco 1234, Miraflores, Lima', 'phone' => '987654321', 'email' => 'miraflores@agenda.com', 'timezone' => 'America/Lima', 'color' => 'sky', 'is_active' => true],
            ['code' => 'san-isidro', 'name' => 'San Isidro', 'slug' => 'san-isidro', 'address' => 'Av. Jorge Basadre 428, San Isidro, Lima', 'phone' => '987654322', 'email' => 'sanisidro@agenda.com', 'timezone' => 'America/Lima', 'color' => 'emerald', 'is_active' => true],
            ['code' => 'surco', 'name' => 'Surco', 'slug' => 'surco', 'address' => 'Av. Caminos del Inca 345, Santiago de Surco, Lima', 'phone' => '987654323', 'email' => 'surco@agenda.com', 'timezone' => 'America/Lima', 'color' => 'violet', 'is_active' => true],
            ['code' => 'la-molina', 'name' => 'La Molina', 'slug' => 'la-molina', 'address' => 'Av. Raúl Ferrero 1025, La Molina, Lima', 'phone' => '987654324', 'email' => 'lamolina@agenda.com', 'timezone' => 'America/Lima', 'color' => 'amber', 'is_active' => true],
        ];

        $branches = [];

        foreach ($definitions as $definition) {
            $code = $definition['code'];
            unset($definition['code']);

            $branch = Branch::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition,
            );

            $branches[$code] = $branch;
        }

        return $branches;
    }

    /**
     * @param  array<string, Branch>  $branches
     * @return array<string, resource>
     */
    private function seedResources(array $branches): array
    {
        $users = User::query()->whereIn('email', [
            'amparo.berna@sasstrend.pe',
            'dorita.lopez@sasstrend.pe',
            'tatiana.bernal@sasstrend.pe',
            'brigitte.ramos@sasstrend.pe',
        ])->get()->keyBy('email');

        $definitions = [
            ['code' => 'amparo-berna', 'branch' => 'miraflores', 'user' => 'amparo.berna@sasstrend.pe', 'name' => 'Amparo Berna', 'slug' => 'amparo-berna', 'type' => 'professional', 'color' => 'sky', 'capacity' => 1, 'is_shared' => false, 'is_active' => true, 'notes' => 'Especialista en keratinas y balayage'],
            ['code' => 'dorita-lopez', 'branch' => 'san-isidro', 'user' => 'dorita.lopez@sasstrend.pe', 'name' => 'Dorita López', 'slug' => 'dorita-lopez', 'type' => 'professional', 'color' => 'violet', 'capacity' => 1, 'is_shared' => false, 'is_active' => true, 'notes' => 'Coloración, rayitos y mechas'],
            ['code' => 'tatiana-bernal', 'branch' => 'surco', 'user' => 'tatiana.bernal@sasstrend.pe', 'name' => 'Tatiana Bernal', 'slug' => 'tatiana-bernal', 'type' => 'professional', 'color' => 'emerald', 'capacity' => 1, 'is_shared' => false, 'is_active' => true, 'notes' => 'Pestañas, peinados y maquillaje'],
            ['code' => 'brigitte-ramos', 'branch' => 'la-molina', 'user' => 'brigitte.ramos@sasstrend.pe', 'name' => 'Brigitte Ramos', 'slug' => 'brigitte-ramos', 'type' => 'professional', 'color' => 'amber', 'capacity' => 1, 'is_shared' => false, 'is_active' => true, 'notes' => 'Cejas y depilación facial'],
            ['code' => 'cabina-1', 'branch' => 'miraflores', 'user' => null, 'name' => 'Cabina 1', 'slug' => 'cabina-1', 'type' => 'room', 'color' => 'zinc', 'capacity' => 1, 'is_shared' => false, 'is_active' => true, 'notes' => 'Cabina principal'],
            ['code' => 'cabina-2', 'branch' => 'san-isidro', 'user' => null, 'name' => 'Cabina 2', 'slug' => 'cabina-2', 'type' => 'room', 'color' => 'zinc', 'capacity' => 1, 'is_shared' => false, 'is_active' => true, 'notes' => 'Cabina ejecutiva'],
            ['code' => 'laser-diodo', 'branch' => 'surco', 'user' => null, 'name' => 'Láser Diodo', 'slug' => 'laser-diodo', 'type' => 'equipment', 'color' => 'rose', 'capacity' => 1, 'is_shared' => true, 'is_active' => true, 'notes' => 'Equipo compartido entre sedes'],
            ['code' => 'recepcion', 'branch' => null, 'user' => null, 'name' => 'Recepción General', 'slug' => 'recepcion-general', 'type' => 'shared', 'color' => 'sky', 'capacity' => 3, 'is_shared' => true, 'is_active' => true, 'notes' => 'Recurso compartido'],
        ];

        $resources = [];

        foreach ($definitions as $definition) {
            $code = $definition['code'];
            $branch = $definition['branch'] ? $branches[$definition['branch']] ?? null : null;
            $user = $definition['user'] ? $users->get($definition['user']) : null;

            unset($definition['code'], $definition['branch'], $definition['user']);

            $resource = Resource::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'branch_id' => $branch?->id,
                    'user_id' => $user?->id,
                    'name' => $definition['name'],
                    'slug' => $definition['slug'],
                    'type' => $definition['type'],
                    'color' => $definition['color'],
                    'capacity' => $definition['capacity'],
                    'is_shared' => $definition['is_shared'],
                    'is_active' => $definition['is_active'],
                    'notes' => $definition['notes'],
                ],
            );

            $resources[$code] = $resource;
        }

        return $resources;
    }

    /**
     * @param  array<string, AppointmentStatus>  $statuses
     * @param  array<string, Branch>  $branches
     * @param  array<string, resource>  $resources
     * @return array<string, Appointment>
     */
    private function seedAppointments(array $statuses, array $branches, array $resources): array
    {
        $clients = Client::query()->whereIn('client_number', [
            'CLI-1001',
            'CLI-1002',
            'CLI-1003',
            'CLI-1004',
            'CLI-1005',
            'CLI-1006',
        ])->get()->keyBy('client_number');

        $services = Service::query()->whereIn('name', [
            'Keratinas',
            'Balayage',
            'Pedicure',
            'Pestañas 1x1',
            'Depilación de cera - Cejas',
        ])->get()->keyBy('name');

        $users = User::query()->whereIn('email', [
            'amparo.berna@sasstrend.pe',
            'dorita.lopez@sasstrend.pe',
            'tatiana.bernal@sasstrend.pe',
            'brigitte.ramos@sasstrend.pe',
        ])->get()->keyBy('email');

        $base = CarbonImmutable::now()->startOfDay()->addHours(9);

        $definitions = [
            [
                'code' => 'apt-1001',
                'branch' => 'miraflores',
                'client' => 'CLI-1001',
                'service' => 'Keratinas',
                'resource' => 'cabina-1',
                'professional' => 'amparo.berna@sasstrend.pe',
                'status' => AppointmentStatusCatalog::CONFIRMED,
                'offset' => 0,
                'duration' => 75,
                'title' => 'Keratina de hidratación',
                'price' => 280,
                'notes' => 'Llegó 10 minutos antes. Confirmar protocolo térmico.',
            ],
            [
                'code' => 'apt-1002',
                'branch' => 'surco',
                'client' => 'CLI-1002',
                'service' => 'Balayage',
                'resource' => 'laser-diodo',
                'professional' => 'dorita.lopez@sasstrend.pe',
                'status' => AppointmentStatusCatalog::IN_PROGRESS,
                'offset' => 120,
                'duration' => 60,
                'title' => 'Balayage de mantenimiento',
                'price' => 320,
                'notes' => 'Clienta pidió mantener contraste suave.',
            ],
            [
                'code' => 'apt-1003',
                'branch' => 'san-isidro',
                'client' => 'CLI-1003',
                'service' => 'Pedicure',
                'resource' => 'cabina-2',
                'professional' => 'brigitte.ramos@sasstrend.pe',
                'status' => AppointmentStatusCatalog::COMPLETED,
                'offset' => -90,
                'duration' => 60,
                'title' => 'Pedicure de mantenimiento',
                'price' => 50,
                'notes' => 'Se solicitó esmaltado nude.',
            ],
            [
                'code' => 'apt-1004',
                'branch' => 'la-molina',
                'client' => 'CLI-1004',
                'service' => 'Pestañas 1x1',
                'resource' => 'recepcion',
                'professional' => 'tatiana.bernal@sasstrend.pe',
                'status' => AppointmentStatusCatalog::CANCELLED,
                'offset' => 180,
                'duration' => 50,
                'title' => 'Aplicación de pestañas 1x1',
                'price' => 180,
                'notes' => 'Cancelado por reprogramación del cliente.',
            ],
            [
                'code' => 'apt-1005',
                'branch' => 'miraflores',
                'client' => 'CLI-1005',
                'service' => 'Depilación de cera - Cejas',
                'resource' => 'cabina-1',
                'professional' => 'brigitte.ramos@sasstrend.pe',
                'status' => AppointmentStatusCatalog::NO_SHOW,
                'offset' => 240,
                'duration' => 90,
                'title' => 'Perfilado de cejas',
                'price' => 20,
                'notes' => 'Cliente no asistió.',
            ],
            [
                'code' => 'apt-1006',
                'branch' => 'san-isidro',
                'client' => 'CLI-1006',
                'service' => 'Pedicure',
                'resource' => 'cabina-2',
                'professional' => 'brigitte.ramos@sasstrend.pe',
                'status' => AppointmentStatusCatalog::RESCHEDULED,
                'offset' => 360,
                'duration' => 60,
                'title' => 'Control de pedicure',
                'price' => 50,
                'notes' => 'Reprogramado desde el sistema.',
            ],
        ];

        $appointments = [];

        foreach ($definitions as $definition) {
            $code = $definition['code'];
            $branch = $branches[$definition['branch']];
            $client = $clients[$definition['client']];
            $service = $services[$definition['service']];
            $resource = $resources[$definition['resource']];
            $professional = $users[$definition['professional']];
            $status = $statuses[$definition['status']];
            $startsAt = $base->addMinutes($definition['offset']);
            $endsAt = $startsAt->addMinutes($definition['duration']);

            $appointment = Appointment::query()->updateOrCreate(
                ['reference_code' => strtoupper($code)],
                [
                    'branch_id' => $branch->id,
                    'client_id' => $client->id,
                    'service_id' => $service->id,
                    'resource_id' => $resource->id,
                    'professional_id' => $professional->id,
                    'appointment_status_id' => $status->id,
                    'title' => $definition['title'],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'duration_minutes' => $definition['duration'],
                    'timezone' => 'America/Lima',
                    'price' => $definition['price'],
                    'currency' => 'PEN',
                    'notes' => $definition['notes'],
                    'created_by' => $professional->id,
                    'updated_by' => $professional->id,
                ],
            );

            $appointments[$code] = $appointment;
        }

        return $appointments;
    }

    /**
     * @param  array<string, Appointment>  $appointments
     */
    private function seedNotes(array $appointments): void
    {
        foreach ([
            'apt-1001' => ['Se recomendó protector solar post tratamiento.', true],
            'apt-1003' => ['Paciente comprometida con seguimiento quincenal.', false],
            'apt-1005' => ['Llamada sin respuesta 15 minutos antes.', true],
        ] as $code => [$note, $internal]) {
            AppointmentNote::query()->updateOrCreate(
                [
                    'appointment_id' => $appointments[$code]->id,
                    'note' => $note,
                ],
                [
                    'user_id' => null,
                    'is_internal' => $internal,
                ],
            );
        }
    }

    /**
     * @param  array<string, Appointment>  $appointments
     */
    private function seedPayments(array $appointments): void
    {
        AppointmentPayment::query()->updateOrCreate(
            ['appointment_id' => $appointments['apt-1003']->id, 'reference' => 'PAY-1003'],
            [
                'amount' => 200,
                'method' => 'card',
                'status' => 'paid',
                'paid_at' => Carbon::now()->subDay(),
                'notes' => 'Pagado en recepción.',
            ],
        );

        AppointmentPayment::query()->updateOrCreate(
            ['appointment_id' => $appointments['apt-1001']->id, 'reference' => 'PAY-1001'],
            [
                'amount' => 50,
                'method' => 'transfer',
                'status' => 'partial',
                'paid_at' => Carbon::now()->subHours(4),
                'notes' => 'Abono inicial.',
            ],
        );
    }

    /**
     * @param  array<string, Branch>  $branches
     * @param  array<string, resource>  $resources
     */
    private function seedBlocks(array $branches, array $resources): void
    {
        ScheduleBlock::query()->updateOrCreate(
            [
                'branch_id' => $branches['miraflores']->id,
                'block_type' => 'lunch_break',
                'starts_at' => Carbon::now()->setTime(13, 0),
            ],
            [
                'resource_id' => $resources['cabina-1']->id,
                'user_id' => null,
                'ends_at' => Carbon::now()->setTime(14, 0),
                'reason' => 'Pausa operativa',
                'is_all_day' => false,
                'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
                'created_by' => null,
                'updated_by' => null,
            ],
        );
    }

    /**
     * @param  array<string, Appointment>  $appointments
     */
    private function seedHistory(array $appointments): void
    {
        foreach ($appointments as $code => $appointment) {
            AppointmentHistory::query()->updateOrCreate(
                ['appointment_id' => $appointment->id, 'action' => 'seeded'],
                [
                    'user_id' => null,
                    'title' => 'Demo appointment seeded',
                    'description' => 'Loaded as part of the demo agenda.',
                    'payload' => ['code' => $code],
                ],
            );
        }
    }
}
