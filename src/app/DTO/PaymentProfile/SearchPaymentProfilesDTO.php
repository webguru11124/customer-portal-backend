<?php

namespace App\DTO\PaymentProfile;

use App\DTO\BaseDTO;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class SearchPaymentProfilesDTO extends BaseDTO
{
    /**
     * @param int $officeId
     * @param int[] $accountNumbers
     * @param StatusType[]|null $statuses
     * @param PaymentMethod[]|null $paymentMethods
     * @param int[] $ids
     *
     * @throws ValidationException
     */
    public function __construct(
        public readonly int $officeId,
        public readonly array $accountNumbers = [],
        public readonly array|null $statuses = null,
        public readonly array|null $paymentMethods = null,
        public readonly array $ids = [],
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'officeId' => 'required|int',
            'accountNumbers' => 'array',
            'accountNumbers.*' => 'int',
            'statuses' => 'nullable|array',
            'statuses.*' => new Enum(StatusType::class),
            'paymentMethods' => 'nullable|array',
            'paymentMethods.*' => new Enum(PaymentMethod::class),
            'ids' => 'nullable|array',
            'ids.*' => 'int',
        ];
    }
}
