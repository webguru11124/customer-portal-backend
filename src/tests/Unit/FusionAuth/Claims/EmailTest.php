<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth\Claims;

use App\FusionAuth\Claims\Email;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class EmailTest extends TestCase
{
    protected const EMAIL = 'test@test.com';

    public function test_it_validates_audience(): void
    {
        $validator = new Email(self::EMAIL);
        $this->assertTrue($validator->validatePayload());
    }

    public function test_it_should_throw_an_exception_when_passing_invalid_audience(): void
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Email is missing from the token');

        $validator = new Email('');
        $validator->validatePayload();
    }
}
