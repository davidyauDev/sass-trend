# Tenancy Architecture

`sass-trend` now uses `single-database tenancy` with `stancl/tenancy`.

## Overview

- The internal application uses one shared domain for everyone.
- Tenant context is identified from the authenticated user's `tenant_id`.
- Public booking uses a slug in the URL, for example `/negocios/trend-belleza/reservas`.
- All tenants share the same physical database.
- Tenant data is isolated with `tenant_id` and Eloquent global scopes.
- Super admins can operate without tenant context.

## Central Tables

These tables belong to the SaaS layer and are not tenant-scoped:

- `tenants`
- `cache`
- `jobs`
- `sessions`
- `password_reset_tokens`

## Tenant-Scoped Tables

These tables carry `tenant_id` and are automatically filtered by the active tenant context:

- `users`
- `user_permissions`
- `clients`
- `branches`
- `locations`
- `location_schedules`
- `service_categories`
- `services`
- `service_schedules`
- `appointment_statuses`
- `resources`
- `appointments`
- `appointment_notes`
- `appointment_payments`
- `appointment_histories`
- `schedule_blocks`
- `commission_types`
- `commission_rules`
- `commission_formulas`
- `commission_settlements`
- `professional_commissions`
- `service_commissions`
- `product_commissions`
- `membership_commissions`
- `commission_transactions`
- `commission_calculations`
- `commission_approvals`
- `commission_payments`
- `commission_audit_logs`
- `website_settings`

## Authentication Model

- Shared app domain: used for login, dashboard and internal modules.
- Tenant users belong to a specific `tenant_id` and only see their own data.
- General admins can manage the SaaS layer and tenants.

## Tenant Creation

Creating a tenant now does **not** create a new database.

It creates:

- one row in `tenants`
- one owner user scoped to that tenant

## Important Files

- [config/tenancy.php](C:/sass-trend/config/tenancy.php)
- [app/Providers/TenancyServiceProvider.php](C:/sass-trend/app/Providers/TenancyServiceProvider.php)
- [app/Models/Concerns/TenantOwned.php](C:/sass-trend/app/Models/Concerns/TenantOwned.php)
- [app/Http/Middleware/InitializeTenancyFromAuthenticatedUser.php](C:/sass-trend/app/Http/Middleware/InitializeTenancyFromAuthenticatedUser.php)
- [app/Http/Middleware/InitializeTenancyFromRouteTenant.php](C:/sass-trend/app/Http/Middleware/InitializeTenancyFromRouteTenant.php)
- [database/migrations/2026_06_16_120000_add_tenant_id_to_single_database_tables.php](C:/sass-trend/database/migrations/2026_06_16_120000_add_tenant_id_to_single_database_tables.php)
