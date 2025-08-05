<?php

declare(strict_types=1);

namespace Tests\Unit\Infra\Metrics\Providers;

use App\Infra\Metrics\Backend;
use App\Infra\Metrics\Backends\Http;
use App\Infra\Metrics\Backends\Log;
use App\Infra\Metrics\Providers\BackendProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use InfluxDB2\Client;
use Tests\CreatesApplication;

final class BackendProviderTest extends TestCase
{
    use CreatesApplication;

    public function test_it_registers_http_backend(): void
    {
        $config = $this->createMock(Repository::class);
        $influxDBClientMock = $this->createMock(Client::class);
        $config->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['metrics.default', null], ['metrics.backends.http', null])
            ->willReturnOnConsecutiveCalls(
                'http',
                ['url' => 'https://example.com', 'token' => 'xxx']
            );

        $appMock = $this->createMock(Application::class);
        $appMock->expects(self::exactly(2))
            ->method('make')
            ->withConsecutive(
                [Repository::class, []],
                [Client::class, []]
            )->willReturnOnConsecutiveCalls(
                $config,
                $influxDBClientMock
            );
        $appMock->expects(self::once())
            ->method('bind')
            ->with(
                Backend::class,
                self::callback(fn (callable $callback) => $callback() instanceof Http)
            );

        $provider = new BackendProvider($appMock);
        $provider->register();
    }

    public function test_it_registers_noop_backend(): void
    {
        $config = $this->createMock(Repository::class);
        $config->expects(self::once())
               ->method('get')
               ->with('metrics.default', null)
               ->willReturn('log');

        $app = $this->createMock(Application::class);
        $app->expects(self::once())->method('make')->with(Repository::class)->willReturn($config);
        $app->expects(self::once())
            ->method('bind')
            ->with(
                Backend::class,
                self::callback(fn (callable $callback) => $callback() instanceof Log)
            );

        $provider = new BackendProvider($app);
        $provider->register();
    }
}
