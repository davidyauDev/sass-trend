<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant && ! $tenant->isActive()) {
            abort(423, 'Este tenant no está activo.');
        }

        return $next($request);
    }
}
