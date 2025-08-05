<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class Payment extends Data
{
    public function __construct(
        #[MapOutputName('payment_id')]
        public string $paymentId,
        #[MapOutputName('status')]
        public string $status,
        #[MapOutputName('amount')]
        public float $amount,
        #[MapOutputName('created_at')]
        public string $created_at,
    ) {
    }

    /**
     * @param object{
     *     payment_id: string,
     *     status: string,
     *     amount: float,
     *     created_at: string,
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            paymentId: $data->payment_id,
            status: $data->status,
            amount: $data->amount,
            created_at: $data->created_at,
        );
    }
}
