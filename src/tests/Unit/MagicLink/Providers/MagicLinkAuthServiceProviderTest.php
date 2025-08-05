<?php

declare(strict_types=1);

namespace Tests\Unit\MagicLink\Providers;

use App\MagicLink\Guards\MagicLinkGuard;
use App\MagicLink\Providers\MagicLinkAuthServiceProvider;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MagicLinkAuthServiceProviderTest extends TestCase
{
    public function test_it_boots_valid_guard(): void
    {
        $provider = new MagicLinkAuthServiceProvider(app());
        $provider->boot();
        $this->assertInstanceOf(MagicLinkGuard::class, Auth::guard('magiclinkguard'));
    }
}
