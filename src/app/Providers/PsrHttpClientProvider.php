<?php

declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface as PSRClientInterface;

final class PsrHttpClientProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PSRClientInterface::class => Client::class,
    ];

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            PSRClientInterface::class,
        ];
    }
}
