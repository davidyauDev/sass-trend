<?php

namespace App\Actions\Clients;

use App\Models\Client;

final class DeleteClientAction
{
    public function handle(Client $client): void
    {
        $client->delete();
    }
}
