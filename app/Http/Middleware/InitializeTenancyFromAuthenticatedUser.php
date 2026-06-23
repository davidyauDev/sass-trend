<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyFromAuthenticatedUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->tenant_id === null) {
            return $next($request);
        }

        $tenant = Tenant::query()->find($user->tenant_id);

        abort_unless($tenant instanceof Tenant, 403, 'El tenant asociado al usuario no existe.');

        tenancy()->initialize($tenant);

        return $next($request);
    }
}
