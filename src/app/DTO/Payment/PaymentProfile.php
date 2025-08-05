<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class PaymentProfile extends Data
{
    public function __construct(
        #[MapOutputName('payment_method_id')]
        public string $paymentMethodId,
        #[MapOutputName('message')]
        public string $message,
    ) {
    }

    /**
     * @param object{
     *      _metadata: object{success: bool, links: object{self: string}},
     *      result: object{message: string, payment_method_id: string},
     *  } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            paymentMethodId: $data->result->payment_method_id,
            message: $data->result->message,
        );
    }
}
