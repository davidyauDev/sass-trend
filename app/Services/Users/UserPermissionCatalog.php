<?php

namespace App\Services\Users;

final class UserPermissionCatalog
{
    /**
     * @return list<array{name:string,slug:string,group:string,description:?string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Ver clientes', 'slug' => 'clients.view', 'group' => 'Clientes', 'description' => null],
            ['name' => 'Crear clientes', 'slug' => 'clients.create', 'group' => 'Clientes', 'description' => null],
            ['name' => 'Editar clientes', 'slug' => 'clients.update', 'group' => 'Clientes', 'description' => null],
            ['name' => 'Eliminar clientes', 'slug' => 'clients.delete', 'group' => 'Clientes', 'description' => null],
            ['name' => 'Ver fichas', 'slug' => 'records.view', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Crear fichas', 'slug' => 'records.create', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Editar fichas', 'slug' => 'records.update', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Eliminar fichas', 'slug' => 'records.delete', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Ver campos personalizados', 'slug' => 'custom_fields.view', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Crear campos personalizados', 'slug' => 'custom_fields.create', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Editar campos personalizados', 'slug' => 'custom_fields.update', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Eliminar campos personalizados', 'slug' => 'custom_fields.delete', 'group' => 'Fichas y campos', 'description' => null],
            ['name' => 'Ver reservas', 'slug' => 'bookings.view', 'group' => 'Reservas y agenda', 'description' => null],
            ['name' => 'Crear reservas', 'slug' => 'bookings.create', 'group' => 'Reservas y agenda', 'description' => null],
            ['name' => 'Editar reservas', 'slug' => 'bookings.update', 'group' => 'Reservas y agenda', 'description' => null],
            ['name' => 'Eliminar reservas', 'slug' => 'bookings.delete', 'group' => 'Reservas y agenda', 'description' => null],
            ['name' => 'Crear bloqueos de agenda', 'slug' => 'schedule_blocks.create', 'group' => 'Reservas y agenda', 'description' => null],
            ['name' => 'Editar bloqueos de agenda', 'slug' => 'schedule_blocks.update', 'group' => 'Reservas y agenda', 'description' => null],
            ['name' => 'Eliminar bloqueos de agenda', 'slug' => 'schedule_blocks.delete', 'group' => 'Reservas y agenda', 'description' => null],
            ['name' => 'Ver agenda', 'slug' => 'appointments.view', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Crear citas', 'slug' => 'appointments.create', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Editar citas', 'slug' => 'appointments.update', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Eliminar citas', 'slug' => 'appointments.delete', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Reprogramar citas', 'slug' => 'appointments.reschedule', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Cancelar citas', 'slug' => 'appointments.cancel', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Marcar no show', 'slug' => 'appointments.no_show', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Completar citas', 'slug' => 'appointments.complete', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Cambiar estado de cita', 'slug' => 'appointments.change_status', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Ver recursos', 'slug' => 'resources.view', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Crear recursos', 'slug' => 'resources.create', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Editar recursos', 'slug' => 'resources.update', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Eliminar recursos', 'slug' => 'resources.delete', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Ver sedes de agenda', 'slug' => 'branches.view', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Gestionar sedes de agenda', 'slug' => 'branches.manage', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Ver bloqueos de agenda', 'slug' => 'schedule_blocks.view', 'group' => 'Agenda', 'description' => null],
            ['name' => 'Ver ventas', 'slug' => 'sales.view', 'group' => 'Ventas y pagos', 'description' => null],
            ['name' => 'Crear ventas', 'slug' => 'sales.create', 'group' => 'Ventas y pagos', 'description' => null],
            ['name' => 'Ver pagos', 'slug' => 'payments.view', 'group' => 'Ventas y pagos', 'description' => null],
            ['name' => 'Ver reporte de pagos', 'slug' => 'reports.payments.view', 'group' => 'Reportes', 'description' => null],
            ['name' => 'Ver reporte de comisiones', 'slug' => 'reports.commissions.view', 'group' => 'Reportes', 'description' => null],
            ['name' => 'Ver comisiones', 'slug' => 'commissions.view', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Crear comisiones', 'slug' => 'commissions.create', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Editar comisiones', 'slug' => 'commissions.edit', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Eliminar comisiones', 'slug' => 'commissions.delete', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Aprobar comisiones', 'slug' => 'commissions.approve', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Rechazar comisiones', 'slug' => 'commissions.reject', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Pagar comisiones', 'slug' => 'commissions.pay', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Exportar comisiones', 'slug' => 'commissions.export', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Ver reportes de comisiones', 'slug' => 'commissions.view_reports', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Gestionar reglas de comisiones', 'slug' => 'commissions.manage_rules', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Ver liquidaciones de comisiones', 'slug' => 'settlements.view', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Crear liquidaciones de comisiones', 'slug' => 'settlements.create', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Aprobar liquidaciones de comisiones', 'slug' => 'settlements.approve', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Pagar liquidaciones de comisiones', 'slug' => 'settlements.pay', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Ver auditoría de comisiones', 'slug' => 'commissions.audit.view', 'group' => 'Comisiones', 'description' => null],
            ['name' => 'Ver email marketing', 'slug' => 'email_marketing.view', 'group' => 'Marketing', 'description' => null],
            ['name' => 'Ver usuarios', 'slug' => 'users.view', 'group' => 'Usuarios y roles', 'description' => null],
            ['name' => 'Crear usuarios', 'slug' => 'users.create', 'group' => 'Usuarios y roles', 'description' => null],
            ['name' => 'Editar usuarios', 'slug' => 'users.update', 'group' => 'Usuarios y roles', 'description' => null],
            ['name' => 'Eliminar usuarios', 'slug' => 'users.delete', 'group' => 'Usuarios y roles', 'description' => null],
            ['name' => 'Actualizar roles', 'slug' => 'roles.update', 'group' => 'Usuarios y roles', 'description' => null],
            ['name' => 'Ver locales', 'slug' => 'locations.view', 'group' => 'Locales', 'description' => null],
            ['name' => 'Crear locales', 'slug' => 'locations.create', 'group' => 'Locales', 'description' => null],
            ['name' => 'Editar locales', 'slug' => 'locations.update', 'group' => 'Locales', 'description' => null],
            ['name' => 'Eliminar locales', 'slug' => 'locations.delete', 'group' => 'Locales', 'description' => null],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(collect(self::definitions())
            ->pluck('slug')
            ->values()
            ->all());
    }

    /**
     * @return array<string, list<array{name:string,slug:string,group:string,description:?string}>>
     */
    public static function groupedDefinitions(): array
    {
        return collect(self::definitions())
            ->groupBy('group')
            ->map(fn ($items): array => array_values($items->values()->all()))
            ->all();
    }
}
