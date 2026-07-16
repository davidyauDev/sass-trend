<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $connection = (string) $this->app['config']->get('database.default');
        $database = (string) $this->app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(
                "Pruebas bloqueadas: la conexion activa es [{$connection}] y la base es [{$database}]. "
                .'Las pruebas solo pueden ejecutarse con SQLite en memoria. '
                .'Ejecuta `php artisan config:clear` y vuelve a intentarlo.'
            );
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
