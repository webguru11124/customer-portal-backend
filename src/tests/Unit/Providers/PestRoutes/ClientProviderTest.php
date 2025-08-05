<?php

declare(strict_types=1);

namespace Tests\Unit\Providers\PestRoutes;

use App\Providers\PestRoutes\ClientProvider;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use Aptive\PestRoutesSDK\Client;
use Aptive\PestRoutesSDK\CredentialsRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ClientProviderTest extends TestCase
{
    public function test_it_provides_pestroutes_client(): void
    {
        $appMock = $this->createMock(Application::class);

        $provider = new ClientProvider($appMock);

        $this->assertSame(
            [Client::class],
            $provider->provides()
        );
    }

    public function test_it_builds_pestroutes_client(): void
    {
        $configMock = $this->createMock(Repository::class);
        $configMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['pestroutes.url', null],
                ['pestroutes.timeout', AbstractPestRoutesRepository::REQUEST_TIMEOUT],
            )
            ->willReturnOnConsecutiveCalls(
                'https://example.com/',
                10,
            );

        $appMock = $this->createMock(Application::class);
        $appMock
            ->expects(self::exactly(3))
            ->method('make')
            ->withConsecutive(
                [Repository::class],
                [CredentialsRepository::class],
                [LoggerInterface::class],
            )
            ->willReturnOnConsecutiveCalls(
                $configMock,
                $this->createMock(CredentialsRepository::class),
                $this->createMock(LoggerInterface::class),
            );

        $appMock
            ->expects(self::once())
            ->method('bind')
            ->with(
                Client::class,
                self::callback(fn ($callback) => $callback() instanceof Client)
            );

        $provider = new ClientProvider($appMock);
        $provider->register();
    }
}
