<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyFromRouteTenant
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        abort_unless($tenant instanceof Tenant, 404);

        tenancy()->initialize($tenant);

        return $next($request);
    }
}
