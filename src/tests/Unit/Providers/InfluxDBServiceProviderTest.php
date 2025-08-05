<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Providers\InfluxDBServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use InfluxDB2\Client;
use Tests\CreatesApplication;

final class InfluxDBServiceProviderTest extends TestCase
{
    use CreatesApplication;

    public function test_it_registers_influx_db_client(): void
    {
        $config = $this->createMock(Repository::class);

        $config->expects(self::exactly(5))
            ->method('get')
            ->withConsecutive(
                ['influxdb.connection.host', null],
                ['influxdb.connection.token', null],
                ['influxdb.connection.bucket', null],
                ['influxdb.connection.organization', null],
                ['app.env', null]
            )
            ->willReturnOnConsecutiveCalls(
                'example.com',
                '1234567asdf',
                'testBucket',
                'aptive',
                'test'
            );

        $appMock = $this->createMock(Application::class);

        $appMock->expects(self::exactly(1))
            ->method('make')
            ->withConsecutive(
                [Repository::class, []]
            )->willReturnOnConsecutiveCalls(
                $config
            );
        $appMock->expects(self::once())
            ->method('bind')
            ->with(
                Client::class,
                self::callback(fn (callable $callback) => $callback() instanceof Client)
            );

        $provider = new InfluxDBServiceProvider($appMock);
        $provider->register();
    }
}
