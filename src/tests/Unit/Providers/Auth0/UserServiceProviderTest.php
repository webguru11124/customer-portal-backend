<?php

namespace Tests\Unit\Providers\Auth0;

use App\Interfaces\Auth0\UserService;
use App\Providers\Auth0\UserServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Tests\TestCase;

class UserServiceProviderTest extends TestCase
{
    public function test_register_configures_auth0_user_service(): void
    {
        $configMock = $this->createMock(Repository::class);
        $configMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['auth0.management', null],
                ['auth0.management.timeout', UserServiceProvider::REQUEST_TIMEOUT],
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'strategy' => 'management',
                    'scope' => ['read:users'],
                    'clientId' => 'aaa',
                    'clientSecret' => 'bbb',
                    'audience' => ['https://dev-xxx.us.auth0.com/api/v2/'],
                    'domain' => 'dev-xxx.us.auth0.com',
                    'timeout' => 10,
                ],
                UserServiceProvider::REQUEST_TIMEOUT
            );

        $appMock = $this->createMock(Application::class);
        $appMock
            ->expects(self::exactly(3))
            ->method('make')
            ->withConsecutive(
                [Repository::class, []],
                [Client::class, [['timeout' => UserServiceProvider::REQUEST_TIMEOUT]]],
                [ApcuAdapter::class, []],
            )
            ->willReturnOnConsecutiveCalls(
                $configMock,
                $this->createMock(Client::class),
                $this->createMock(ApcuAdapter::class)
            );
        $appMock
            ->expects(self::once())
            ->method('bind')
            ->with(
                UserService::class,
                self::callback(static function (callable $fn) use ($appMock): bool {
                    return $fn($appMock) instanceof UserService;
                })
            );

        $provider = new UserServiceProvider($appMock);
        $provider->register();

        $this->assertSame(
            [UserService::class],
            $provider->provides()
        );
    }
}
