<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;
use Spatie\LaravelData\Attributes\MapOutputName;

final class AuthAndCapture extends BaseDTO
{
    public function __construct(
        #[MapOutputName('message')]
        public string $message,
        #[MapOutputName('status')]
        public string $status,
        #[MapOutputName('payment_id')]
        public string $paymentId,
        #[MapOutputName('transaction_id')]
        public string $transactionId,
    ) {
    }

    /**
     * @param object{
     *      _metadata: object{success: bool},
     *      result: object{message: string, status: string, payment_id: string, transaction_id: string},
     *  } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            message: $data->result->message,
            status: $data->result->status,
            paymentId: $data->result->payment_id,
            transactionId: $data->result->transaction_id,
        );
    }
}
