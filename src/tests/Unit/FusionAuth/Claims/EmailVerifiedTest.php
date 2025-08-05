<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth\Claims;

use App\FusionAuth\Claims\EmailVerified;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class EmailVerifiedTest extends TestCase
{
    public function test_it_validates_audience(): void
    {
        $validator = new EmailVerified(true);
        $this->assertTrue($validator->validatePayload());
    }

    public function test_it_should_throw_an_exception_when_passing_invalid_audience(): void
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Email is not verified');

        $validator = new EmailVerified(false);
        $validator->validatePayload();
    }
}
