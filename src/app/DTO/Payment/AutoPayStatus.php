<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class AutoPayStatus extends Data
{
    public function __construct(
        #[MapOutputName('success')]
        public bool $success,
        #[MapOutputName('message')]
        public string $message,
    ) {
    }

    /**
     * @param object{
     *      _metadata: object{success: bool},
     *      result: object{message: string},
     *  } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            success: $data->_metadata->success,
            message: $data->result->message,
        );
    }
}
