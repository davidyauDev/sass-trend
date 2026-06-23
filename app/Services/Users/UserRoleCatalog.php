<?php

namespace App\Services\Users;

final class UserRoleCatalog
{
    public const GENERAL_ADMIN = 'general_admin';

    public const LOCATION_ADMIN = 'location_admin';

    public const RECEPTIONIST_EDITOR = 'receptionist_editor';

    public const RECEPTIONIST_VIEWER = 'receptionist_viewer';

    public const STAFF_EDITOR = 'staff_editor';

    public const STAFF_VIEWER = 'staff_viewer';

    public const SUPERVISOR = 'supervisor';

    public const PROFESSIONAL = 'professional';

    public const ACCOUNTING = 'accounting';

    /**
     * @return list<array{slug:string,name:string,description:?string,is_system:bool}>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Administrador General',
                'slug' => self::GENERAL_ADMIN,
                'description' => 'Acceso total a la plataforma, usuarios, roles, locales y configuración.',
                'is_system' => true,
            ],
            [
                'name' => 'Administrador de Local',
                'slug' => self::LOCATION_ADMIN,
                'description' => 'Gestiona operación y agenda de los locales asignados.',
                'is_system' => true,
            ],
            [
                'name' => 'Recepcionista con edición',
                'slug' => self::RECEPTIONIST_EDITOR,
                'description' => 'Gestiona reservas y agenda con capacidad de edición.',
                'is_system' => true,
            ],
            [
                'name' => 'Recepcionista sin edición',
                'slug' => self::RECEPTIONIST_VIEWER,
                'description' => 'Visualiza agenda y resumen sin editar información.',
                'is_system' => true,
            ],
            [
                'name' => 'Staff con edición',
                'slug' => self::STAFF_EDITOR,
                'description' => 'Gestiona su agenda y datos operativos según permisos.',
                'is_system' => true,
            ],
            [
                'name' => 'Staff sin edición',
                'slug' => self::STAFF_VIEWER,
                'description' => 'Visualiza la agenda asignada sin capacidad de edición.',
                'is_system' => true,
            ],
            [
                'name' => 'Supervisor',
                'slug' => self::SUPERVISOR,
                'description' => 'Aprueba comisiones, revisa desempeño y supervisa liquidaciones.',
                'is_system' => true,
            ],
            [
                'name' => 'Profesional',
                'slug' => self::PROFESSIONAL,
                'description' => 'Consulta sus comisiones, metas y rendimiento histórico.',
                'is_system' => true,
            ],
            [
                'name' => 'Contabilidad',
                'slug' => self::ACCOUNTING,
                'description' => 'Gestiona liquidaciones, pagos, reportes y conciliaciones.',
                'is_system' => true,
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function basePermissionSlugs(): array
    {
        $all = UserPermissionCatalog::all();

        return [
            self::GENERAL_ADMIN => $all,
            self::LOCATION_ADMIN => [
                'clients.view',
                'clients.create',
                'clients.update',
                'records.view',
                'records.create',
                'records.update',
                'custom_fields.view',
                'custom_fields.create',
                'custom_fields.update',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.delete',
                'appointments.view',
                'appointments.create',
                'appointments.update',
                'appointments.delete',
                'appointments.reschedule',
                'appointments.cancel',
                'appointments.no_show',
                'appointments.complete',
                'appointments.change_status',
                'resources.view',
                'resources.create',
                'resources.update',
                'resources.delete',
                'branches.view',
                'branches.manage',
                'schedule_blocks.view',
                'schedule_blocks.create',
                'schedule_blocks.update',
                'schedule_blocks.delete',
                'sales.view',
                'sales.create',
                'payments.view',
                'reports.payments.view',
                'reports.commissions.view',
                'email_marketing.view',
                'locations.view',
                'locations.update',
            ],
            self::RECEPTIONIST_EDITOR => [
                'clients.view',
                'clients.create',
                'clients.update',
                'records.view',
                'records.create',
                'records.update',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'appointments.view',
                'appointments.create',
                'appointments.update',
                'appointments.reschedule',
                'appointments.cancel',
                'appointments.no_show',
                'appointments.complete',
                'appointments.change_status',
                'resources.view',
                'branches.view',
                'schedule_blocks.view',
                'schedule_blocks.create',
                'schedule_blocks.update',
                'sales.view',
                'sales.create',
                'payments.view',
            ],
            self::RECEPTIONIST_VIEWER => [
                'clients.view',
                'records.view',
                'bookings.view',
                'appointments.view',
                'resources.view',
                'branches.view',
                'schedule_blocks.view',
                'payments.view',
            ],
            self::STAFF_EDITOR => [
                'clients.view',
                'records.view',
                'records.update',
                'bookings.view',
                'bookings.update',
                'appointments.view',
                'appointments.update',
                'appointments.reschedule',
                'appointments.change_status',
                'resources.view',
                'branches.view',
                'schedule_blocks.view',
                'schedule_blocks.create',
                'schedule_blocks.update',
            ],
            self::STAFF_VIEWER => [
                'clients.view',
                'records.view',
                'bookings.view',
                'appointments.view',
                'resources.view',
                'branches.view',
            ],
            self::SUPERVISOR => [
                'commissions.view',
                'commissions.approve',
                'commissions.reject',
                'commissions.view_reports',
                'commissions.audit.view',
                'settlements.view',
                'settlements.approve',
            ],
            self::PROFESSIONAL => [
                'commissions.view',
                'commissions.view_reports',
            ],
            self::ACCOUNTING => [
                'commissions.view',
                'commissions.approve',
                'commissions.reject',
                'commissions.pay',
                'commissions.export',
                'commissions.view_reports',
                'settlements.view',
                'settlements.create',
                'settlements.approve',
                'settlements.pay',
                'commissions.audit.view',
            ],
        ];
    }
}
