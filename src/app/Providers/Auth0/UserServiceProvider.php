<?php

declare(strict_types=1);

namespace App\Providers\Auth0;

use App\Interfaces\Auth0\UserService as Auth0UserService;
use App\Services\Auth0\UserService;
use Auth0\SDK\Auth0;
use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

final class UserServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public const REQUEST_TIMEOUT = 10;

    public function register(): void
    {
        $this->app->bind(Auth0UserService::class, function (Application $app) {
            $auth0 = new Auth0($this->getConfiguration());
            $auth0
                ->configuration()
                ->setManagementTokenCache(
                    $app->make(ApcuAdapter::class)
                );

            return new UserService($auth0);
        });
    }

    /**
     * @return array<string, string|string[]|null>
     */
    private function getConfiguration(): array
    {
        $config = $this->app->make(Repository::class);

        $auth0Configuration = $config->get('auth0.management');

        unset($auth0Configuration['timeout']);

        $auth0Configuration['httpClient'] = $this->app->make(
            Client::class,
            [['timeout' => $config->get('auth0.management.timeout', self::REQUEST_TIMEOUT)]]
        );

        return $auth0Configuration;
    }

    /**
     * @return class-string[]
     */
    public function provides(): array
    {
        return [
            Auth0UserService::class,
        ];
    }
}
