<?php

namespace App\Services\Services;

use App\Models\ServiceCategory;
use Illuminate\Support\Str;

final class ServiceCategoryManager
{
    public function resolve(?int $categoryId, ?string $newCategoryName): ServiceCategory
    {
        $name = trim((string) $newCategoryName);

        if ($name !== '') {
            $slugBase = Str::slug($name);
            $slug = $slugBase;
            $suffix = 2;

            while (ServiceCategory::query()->where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$suffix}";
                $suffix++;
            }

            return ServiceCategory::query()->create([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
            ]);
        }

        return ServiceCategory::query()->findOrFail($categoryId);
    }
}
