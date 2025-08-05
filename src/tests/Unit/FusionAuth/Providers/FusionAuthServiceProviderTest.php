<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth\Providers;

use App\FusionAuth\FusionAuthJwtGuard;
use App\FusionAuth\Providers\FusionAuthServiceProvider;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FusionAuthServiceProviderTest extends TestCase
{
    public function test_it_boots_valid_guard(): void
    {
        $provider = new FusionAuthServiceProvider(app());
        $provider->boot();
        $this->assertInstanceOf(FusionAuthJwtGuard::class, Auth::guard('fusion'));
    }
}
