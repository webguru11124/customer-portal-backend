<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Providers\PsrHttpClientProvider;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PsrHttpClientProviderTest extends TestCase
{
    public function test_bindings(): void
    {
        $provider = new PsrHttpClientProvider($this->getApplicationMock());

        $this->assertSame(
            [
                'Psr\Http\Client\ClientInterface' => 'GuzzleHttp\Client',
            ],
            $provider->bindings,
        );
    }

    public function test_it_provides_correct_implementations(): void
    {
        $provider = new PsrHttpClientProvider($this->getApplicationMock());

        $this->assertSame(
            [
                'Psr\Http\Client\ClientInterface',
            ],
            $provider->provides(),
        );
    }

    private function getApplicationMock(): Application|MockObject
    {
        return $this->createMock(Application::class);
    }
}
