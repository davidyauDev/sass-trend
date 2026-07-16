<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class TenantAsset
{
    public static function url(string $path, ?string $tenantSlug = null): string
    {
        $tenantSlug ??= tenant('slug');

        if (! is_string($tenantSlug) || $tenantSlug === '') {
            return Storage::disk('public')->url($path);
        }

        return route('perfil.archivo', [
            'tenant' => $tenantSlug,
            'path' => $path,
        ]);
    }
}
