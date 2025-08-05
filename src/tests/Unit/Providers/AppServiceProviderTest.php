<?php

declare(strict_types=1);

namespace Tests\Unit\MagicLink\Providers;

use App\Logging\CPLogManager;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Twilio\Rest\Client;

class AppServiceProviderTest extends TestCase
{
    public function test_it_registers_valid_classes(): void
    {
        $provider = new AppServiceProvider(app());
        $provider->register();
        $this->assertInstanceOf(CPLogManager::class, app(LoggerInterface::class));
        $this->assertInstanceOf(Client::class, app(Client::class));
    }

    public function test_it_boots_valid_classes(): void
    {
        DB::shouldReceive("listen")->once();
        $provider = new AppServiceProvider(app());
        $provider->boot();
    }
}
