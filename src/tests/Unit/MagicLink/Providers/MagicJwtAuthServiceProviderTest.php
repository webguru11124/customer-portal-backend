<?php

declare(strict_types=1);

namespace Tests\Unit\MagicLink\Providers;

use App\MagicLink\Guards\MagicJwtAuthGuard;
use App\MagicLink\Providers\MagicJwtAuthServiceProvider;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MagicJwtAuthServiceProviderTest extends TestCase
{
    public function test_it_boots_valid_guard(): void
    {
        $provider = new MagicJwtAuthServiceProvider(app());
        $provider->boot();
        $this->assertInstanceOf(MagicJwtAuthGuard::class, Auth::guard('magicjwtguard'));
    }
}
