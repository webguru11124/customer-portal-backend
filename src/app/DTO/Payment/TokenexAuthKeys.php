<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class TokenexAuthKeys extends Data
{
    public function __construct(
        #[MapOutputName('message')]
        public string $message,
        #[MapOutputName('authentication_key')]
        public string $authenticationKey,
    ) {
    }

    /**
     * @param object{
     *      _metadata: object{success: bool, links: object{self: string}},
     *      result: object{message: string, authentication_key: string},
     *  } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            message: $data->result->message,
            authenticationKey: $data->result->authentication_key,
        );
    }
}
