<?php

declare(strict_types=1);

namespace App\MagicLink;

use App\DTO\MagicLink\ValidationErrorDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class MagicLink
{
    public const LINK_EXPIRED = 'Token expired';
    public const LINK_INVALID = 'Invalid payload';

    private readonly string $salt;
    private readonly int $expireHours;
    /**
     * @var array<string, mixed>|null
     */
    private array|null $payload = null;
    private ValidationErrorDTO|null $validationError = null;
    private bool|null $isValid = null;

    public function __construct()
    {
        $this->salt = Config::get('magiclink.secret');
        $this->expireHours = Config::get('magiclink.ttl');
    }

    /**
     * @param string $email
     * @param int|null $hours
     * @return string
     */
    public function encode(string $email, int|null $hours = null): string
    {
        $hours = $hours ?? $this->expireHours;
        $expiresAt = (int)(Carbon::now()->timestamp) + ($hours * 3600);

        return rtrim(strtr(base64_encode((string)@json_encode([
            'e' => $email,
            'x' => $expiresAt,
            's' => $this->getS($email, $expiresAt)])), '+/', '-_'), '=');
    }

    /**
     * @param string $encodedString
     * @return mixed
     */
    public function decode(string $encodedString): mixed
    {
        $pad = (($rem = (strlen($encodedString) % 4)) ? str_repeat('=', 4 - $rem) : '');
        $decoded = @json_decode(base64_decode(strtr($encodedString . $pad, '-_', '+/')), true);
        $this->payload = $decoded;
        $this->validatePayload();

        return $this->isValid ? $this->payload : null;
    }

    /**
     * @return void
     */
    protected function validatePayload(): void
    {
        if ($this->payload !== null
            && isset($this->payload['e'], $this->payload['x'], $this->payload['s']) &&
            is_string($this->payload['e']) && is_int($this->payload['x']) && is_string($this->payload['s'])
            && !strcmp($this->payload['s'], $this->getS($this->payload['e'], $this->payload['x']))) {

            if ($this->payload['x'] >= Carbon::now()->timestamp) {
                $this->isValid = true;
            } else {
                $this->validationError = new ValidationErrorDTO(
                    ValidationErrorDTO::EXPIRED_TOKEN_CODE,
                    ValidationErrorDTO::EXPIRED_TOKEN_MESSAGE
                );
                $this->isValid = false;
            }
        } else {
            $this->validationError = new ValidationErrorDTO(
                ValidationErrorDTO::INVALID_TOKEN_CODE,
                ValidationErrorDTO::INVALID_TOKEN_MESSAGE
            );
            $this->isValid = false;
        }
    }

    /**
     * @return ValidationErrorDTO|null
     */
    public function getValidationError(): ValidationErrorDTO|null
    {
        if (is_null($this->isValid)) {
            $this->validatePayload();
        }
        return $this->validationError;
    }

    /**
     * @param string $email
     * @param int $expiresAt
     * @return string
     */
    protected function getS(string $email, int $expiresAt): string
    {
        return md5($email . '.' . $expiresAt . '.' . $this->salt);
    }
}
