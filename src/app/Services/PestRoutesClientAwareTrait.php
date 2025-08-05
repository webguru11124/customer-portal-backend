<?php

namespace App\Services;

use Aptive\PestRoutesSDK\Client;

trait PestRoutesClientAwareTrait
{
    private Client|null $pestRoutesClient = null;

    public function setPestRoutesClient(Client $pestRoutesClient): void
    {
        $this->pestRoutesClient = $pestRoutesClient;
    }

    /**
     * @return Client
     */
    public function getPestRoutesClient(): Client
    {
        if ($this->pestRoutesClient !== null) {
            return $this->pestRoutesClient;
        }

        return app(Client::class);
    }
}
