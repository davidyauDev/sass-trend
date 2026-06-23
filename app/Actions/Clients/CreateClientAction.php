<?php

namespace App\Actions\Clients;

use App\Models\Client;

final class CreateClientAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Client
    {
        return Client::create(
            collect($data)
                ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
                ->all(),
        );
    }
}
