<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth\Claims;

use App\FusionAuth\Claims\Audience;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AudienceTest extends TestCase
{
    protected const FUSIONAUTH_CLIENT_ID = 'b84ccaf9-c1c7-4ba8-82c9-e263bf9b152a';

    public function test_it_validates_audience(): void
    {
        Config::set('auth.fusionauth.client_id', self::FUSIONAUTH_CLIENT_ID);

        $validator = new Audience(self::FUSIONAUTH_CLIENT_ID);
        $this->assertTrue($validator->validatePayload());
    }

    public function test_it_should_throw_an_exception_when_passing_invalid_audience(): void
    {
        Config::set('auth.fusionauth.client_id', '');

        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Audience (aud) invalid');

        $validator = new Audience(self::FUSIONAUTH_CLIENT_ID);
        $validator->validatePayload();
    }

    public function test_it_should_throw_an_exception_when_passing_empty_audience(): void
    {
        Config::set('auth.fusionauth.client_id', self::FUSIONAUTH_CLIENT_ID);

        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Audience (aud) invalid');

        $validator = new Audience('');
        $validator->validatePayload();
    }
}
