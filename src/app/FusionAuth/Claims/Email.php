<?php

declare(strict_types=1);

namespace App\FusionAuth\Claims;

use Tymon\JWTAuth\Claims\Claim;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Email extends Claim
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'email';

    /**
     * @throws \Tymon\JWTAuth\Exceptions\TokenInvalidException
     */
    public function validatePayload(): bool
    {
        if (!$this->validate()) {
            throw new TokenInvalidException('Email is missing from the token');
        }

        return true;
    }

    private function validate(): bool
    {
        return !empty($this->getValue());
    }
}
