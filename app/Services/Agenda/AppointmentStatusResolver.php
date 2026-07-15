<?php

namespace App\Services\Agenda;

use App\Models\AppointmentStatus;
use InvalidArgumentException;
use RuntimeException;

final class AppointmentStatusResolver
{
    public function ensureAll(): void
    {
        foreach (AppointmentStatusCatalog::definitions() as $definition) {
            AppointmentStatus::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'color' => $definition['color'],
                    'sort_order' => $definition['sort_order'],
                    'is_terminal' => $definition['is_terminal'],
                ],
            );
        }
    }

    public function resolveId(string $slug): int
    {
        $definition = collect(AppointmentStatusCatalog::definitions())->firstWhere('slug', $slug);

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Estado de cita no válido: {$slug}.");
        }

        $status = AppointmentStatus::query()->updateOrCreate(
            ['slug' => $definition['slug']],
            [
                'name' => $definition['name'],
                'color' => $definition['color'],
                'sort_order' => $definition['sort_order'],
                'is_terminal' => $definition['is_terminal'],
            ],
        );

        if (! is_numeric($status->getKey())) {
            throw new RuntimeException('No se pudo resolver el estado de la cita.');
        }

        return (int) $status->getKey();
    }
}
