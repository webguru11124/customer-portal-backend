<?php

declare(strict_types=1);

namespace App\Http\Responses\PaymentProfile;

use Aptive\Component\Http\HttpStatus;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;

final class InitializeCreditCardPaymentProfileResponse extends JsonApiResponse
{
    public static function make(string $redirectUri): static
    {
        return (new static())
            ->setContent(['uri' => $redirectUri])
            ->setStatusCode(HttpStatus::CREATED);
    }
}
