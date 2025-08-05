<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth\Claims;

use App\FusionAuth\Claims\Issuer;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class IssuerTest extends TestCase
{
    protected const FUSIONAUTH_URL = 'acme.com';

    public function test_it_validates_audience()
    {
        Config::set('auth.fusionauth.url', self::FUSIONAUTH_URL);

        $validator = new Issuer(self::FUSIONAUTH_URL);
        $this->assertTrue($validator->validatePayload());
    }

    public function test_it_should_throw_an_exception_when_passing_invalid_issuer(): void
    {
        Config::set('auth.fusionauth.url', 'suspicious.com');

        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Issuer (iss) invalid');

        $validator = new Issuer(self::FUSIONAUTH_URL);
        $validator->validatePayload();
    }

    public function test_it_should_throw_an_exception_when_passing_empty_issuer(): void
    {
        Config::set('auth.fusionauth.url', self::FUSIONAUTH_URL);

        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Issuer (iss) invalid');

        $validator = new Issuer('');
        $validator->validatePayload();
    }

    public function test_it_should_throw_an_exception_when_passing_empty_valid_issuer(): void
    {
        Config::set('auth.fusionauth.url', '');

        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Issuer (iss) invalid');

        $validator = new Issuer(self::FUSIONAUTH_URL);
        $validator->validatePayload();
    }
}
