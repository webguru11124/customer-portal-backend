<?php

declare(strict_types=1);

namespace App\DTO\MagicLink;

class ValidationErrorDTO
{
    public const INVALID_TOKEN_CODE = 460;
    public const EXPIRED_TOKEN_CODE = 461;

    public const EXPIRED_TOKEN_MESSAGE = 'Token expired';
    public const INVALID_TOKEN_MESSAGE = 'Invalid payload';

    public function __construct(
        public readonly int $code = self::INVALID_TOKEN_CODE,
        public readonly string $message = self::INVALID_TOKEN_MESSAGE,
    ) {
    }
}
