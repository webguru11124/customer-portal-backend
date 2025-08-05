<?php

declare(strict_types=1);

namespace Tests\Unit\MagicLink;

use App\DTO\MagicLink\ValidationErrorDTO;
use App\MagicLink\MagicLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    protected const SALT = 'e263bf9b152b84ccaf9c1c74e263bf92b84ccaf9c1cb152ba882c9a';
    protected const TTL = 24;

    public $email = 'test@test.com';

    public function test_it_encodes_and_decodes_token(): void
    {
        Config::set('magiclink.secret', self::SALT);
        Config::set('magiclink.ttl', self::TTL);

        $expiresAt = time() + self::TTL * 3600;

        $coder = new MagicLink();
        $token = $coder->encode($this->email, self::TTL);
        $this->assertNotEmpty($token);
        $payload = $coder->decode($token);
        $this->assertEquals($this->email, $payload['e']);
        $this->assertEquals($expiresAt, $payload['x']);
        $this->assertNull($coder->getValidationError());
    }

    public function test_it_returns_validation_error_on_no_payload(): void
    {
        $coder = new MagicLink();
        $validationError = $coder->getValidationError();
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_MESSAGE, $validationError->message);
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_CODE, $validationError->code);
    }

    public function test_it_returns_validation_error_on_invalid_token(): void
    {
        $coder = new MagicLink();
        $payload = $coder->decode('invalid_token');

        $this->assertEmpty($payload);
        $validationError = $coder->getValidationError();
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_MESSAGE, $validationError->message);
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_CODE, $validationError->code);
    }

    public function test_it_returns_validation_error_on_invalid_salt(): void
    {
        Config::set('magiclink.secret', self::SALT);
        Config::set('magiclink.ttl', self::TTL);

        $encoder = new MagicLink();
        $token = $encoder->encode($this->email, self::TTL);
        $this->assertNotEmpty($token);

        Config::set('magiclink.secret', self::SALT. '_changed');
        $decoder = new MagicLink();

        $this->assertEmpty($decoder->decode($token));
        $validationError = $decoder->getValidationError();
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_MESSAGE, $validationError->message);
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_CODE, $validationError->code);
    }

    public function test_it_returns_validation_error_on_expired_token(): void
    {
        Config::set('magiclink.secret', self::SALT);
        Config::set('magiclink.ttl', 0);

        $encoder = new MagicLink();
        $token = $encoder->encode($this->email, self::TTL);
        $this->assertNotEmpty($token);

        $future = Carbon::now()->addDays(7);
        Carbon::setTestNow($future);
        $decoder = new MagicLink();

        $this->assertEmpty($decoder->decode($token));
        $validationError = $decoder->getValidationError();
        $this->assertEquals(ValidationErrorDTO::EXPIRED_TOKEN_MESSAGE, $validationError->message);
        $this->assertEquals(ValidationErrorDTO::EXPIRED_TOKEN_CODE, $validationError->code);
    }
}
