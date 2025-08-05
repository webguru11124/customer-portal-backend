<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Http\Middleware\HandleCustomerSession;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class ApiTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        $this->withoutMiddleware(HandleCustomerSession::class);
    }
}
