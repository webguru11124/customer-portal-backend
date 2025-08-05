<?php

declare(strict_types=1);

namespace App\Providers\PestRoutes;

use App\Helpers\UrlHelper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use Aptive\PestRoutesSDK\Client as PestRoutesClient;
use Aptive\PestRoutesSDK\CredentialsRepository;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class ClientProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(
            PestRoutesClient::class,
            fn () => $this->configureClient()
        );
    }

    private function configureClient(): PestRoutesClient
    {
        $config = $this->app->make(Repository::class);

        return new PestRoutesClient(
            $this->getApiBaseUrl($config),
            $this->app->make(CredentialsRepository::class),
            $this->app->make(LoggerInterface::class),
            $this->getGuzzleClient($config),
        );
    }

    private function getApiBaseUrl(Repository $config): string
    {
        return UrlHelper::ensureUrlEndsWithSlash(
            $config->get('pestroutes.url')
        );
    }

    private function getGuzzleClient(Repository $config): GuzzleClient
    {
        return new GuzzleClient([
            'timeout' => $config->get('pestroutes.timeout', AbstractPestRoutesRepository::REQUEST_TIMEOUT),
        ]);
    }

    /**
     * @return class-string[]
     */
    public function provides(): array
    {
        return [
            PestRoutesClient::class,
        ];
    }
}
