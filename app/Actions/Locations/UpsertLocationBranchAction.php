<?php

namespace App\Actions\Locations;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UpsertLocationBranchAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Branch $branch = null): Branch
    {
        return DB::transaction(function () use ($data, $branch): Branch {
            $name = trim((string) ($data['branch_name'] ?? ''));
            $address = trim((string) ($data['branch_address'] ?? ''));

            $payload = [
                'name' => $name !== '' ? $name : (string) $data['name'],
                'slug' => $branch?->slug ?? $this->uniqueSlug($name !== '' ? $name : (string) $data['name']),
                'address' => $address !== '' ? $address : (string) $data['address'],
                'phone' => $this->nullableString($data['branch_phone'] ?? null, $data['phone'] ?? null),
                'email' => $this->nullableString($data['branch_email'] ?? null, $data['email'] ?? null),
                'timezone' => $this->nullableString($data['branch_timezone'] ?? null, $data['timezone'] ?? null) ?? config('app.timezone'),
                'color' => $this->nullableString($data['branch_color'] ?? null),
                'is_active' => (bool) ($data['branch_is_active'] ?? true),
            ];

            if ($branch instanceof Branch) {
                $branch->update($payload);

                return $branch->refresh();
            }

            return Branch::query()->create($payload);
        });
    }

    private function uniqueSlug(string $name): string
    {
        $slugBase = Str::slug($name) ?: 'branch';
        $slug = $slugBase;
        $suffix = 2;

        while (Branch::query()->withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$slugBase}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function nullableString(mixed $value, mixed $fallback = null): ?string
    {
        $candidate = trim((string) ($value ?? ''));

        if ($candidate !== '') {
            return $candidate;
        }

        $fallbackValue = trim((string) ($fallback ?? ''));

        return $fallbackValue !== '' ? $fallbackValue : null;
    }
}
