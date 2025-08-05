<?php

declare(strict_types=1);

namespace App\FusionAuth\Claims;

use Tymon\JWTAuth\Claims\Claim;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class EmailVerified extends Claim
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'email_verified';

    /**
     * @throws \Tymon\JWTAuth\Exceptions\TokenInvalidException
     */
    public function validatePayload(): bool
    {
        if (!$this->validate()) {
            throw new TokenInvalidException('Email is not verified');
        }

        return true;
    }

    private function validate(): bool
    {
        return $this->getValue();
    }
}
