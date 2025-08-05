<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\GetPaymentProfilesRequest;
use Tests\TestCase;

class GetPaymentProfilesRequestTest extends TestCase
{
    public $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new GetPaymentProfilesRequest();
    }

    public function tearDown(): void
    {
        $this->request = null;
        parent::tearDown();
    }

    public function test_it_authorized_the_request()
    {
        $this->assertTrue($this->request->authorize());
    }
}
