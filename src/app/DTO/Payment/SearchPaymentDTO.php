<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentStatus;
use Illuminate\Validation\Rules\Enum;

class SearchPaymentDTO extends BaseDTO
{
    /**
     * @param int $officeId
     * @param int[] $accountNumber
     * @param array<int>|null $ids
     * @param PaymentStatus|null $status
     */
    public function __construct(
        public readonly int $officeId,
        public readonly array $accountNumber = [],
        public readonly array|null $ids = null,
        public readonly PaymentStatus|null $status = null,
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'officeId' => 'gt:0',
            'accountNumber.*' => 'int',
            'ids' => 'nullable|array',
            'ids.*' => 'int',
            'status' => ['nullable', new Enum(PaymentStatus::class)],
        ];
    }
}
