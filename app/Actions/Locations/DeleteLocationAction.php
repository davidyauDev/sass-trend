<?php

namespace App\Actions\Locations;

use App\Models\Location;
use Illuminate\Support\Facades\Storage;

final class DeleteLocationAction
{
    public function handle(Location $location): void
    {
        foreach ([$location->logo_path, $location->hero_image_path, $location->image_path] as $path) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }
        }

        $location->delete();
    }
}
