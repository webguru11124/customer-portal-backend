<?php

declare(strict_types=1);

namespace App\DTO;

final class Check
{
    private function __construct(
        private readonly bool $result,
        private readonly string|null $message = null
    ) {
    }

    public static function true(): self
    {
        return new self(true);
    }

    public static function false(string $message): self
    {
        return new self(false, $message);
    }

    public function isTrue(): bool
    {
        return $this->result === true;
    }

    public function isFalse(): bool
    {
        return $this->result === false;
    }

    public function getMessage(): string|null
    {
        return $this->message;
    }
}
