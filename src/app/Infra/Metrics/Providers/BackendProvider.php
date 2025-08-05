<?php

declare(strict_types=1);

namespace App\Infra\Metrics\Providers;

use App\Infra\Metrics\Backend;
use App\Infra\Metrics\Backends\Http;
use App\Infra\Metrics\Backends\Log;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use InfluxDB2\Client;

final class BackendProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app->make(Repository::class);

        $this->app->bind(Backend::class, function () use ($config): Backend {
            return match ($config->get('metrics.default')) {
                'http' => new Http(
                    $this->app->make(Client::class),
                    ...$config->get('metrics.backends.http')
                ),
                default => new Log(),
            };
        });
    }
}
