<?php

declare(strict_types=1);

namespace Tests\Unit\Providers\Worldpay;

use App\Providers\Worldpay\CredentialsRepositoryProvider;
use Aptive\Worldpay\CredentialsRepository\CredentialsRepository;
use Aptive\Worldpay\CredentialsRepository\DynamoDbCredentialsRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class CredentialsRepositoryProviderTest extends TestCase
{
    public function test_provider_binds_credentials_repository_implementation(): void
    {
        Config::expects('get')
            ->once()
            ->withArgs(['worldpay.auth.dynamodb.table'])
            ->andReturn('table');
        Config::expects('get')
            ->once()
            ->withArgs(['worldpay.auth.dynamodb.region'])
            ->andReturn('table');

        $applicationMock = $this->createMock(Application::class);
        $applicationMock
            ->expects(self::once())
            ->method('bind')
            ->with(
                CredentialsRepository::class,
                self::callback(static fn ($callback) => $callback() instanceof DynamoDbCredentialsRepository)
            );

        $provider = new CredentialsRepositoryProvider($applicationMock);
        $provider->register();
    }
}
