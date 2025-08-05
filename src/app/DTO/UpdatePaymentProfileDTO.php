<?php

namespace App\DTO;

/**
 * DTO for Updating Payment Profile.
 */
class UpdatePaymentProfileDTO extends BaseDTO
{
    private const STR_128_VALIDATOR = ['nullable', 'max:128'];

    public function __construct(
        public int $officeId,
        public int $paymentProfileID,
        public string|null $billingFName = null,
        public string|null $billingLName = null,
        public string|null $billingAddressLine1 = null,
        public string|null $billingAddressLine2 = null,
        public string|null $billingCity = null,
        public string|null $billingState = null,
        public string|null $billingZip = null,
        public string|null $billingCountryId = null,
        public string|null $expMonth = null,
        public string|null $expYear = null,
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'officeId' => ['required', 'integer', 'gt:0'],
            'paymentProfileID' => ['required', 'integer', 'gt:0'],
            'billingFName' => self::STR_128_VALIDATOR,
            'billingLName' => self::STR_128_VALIDATOR,
            'billingAddressLine1' => self::STR_128_VALIDATOR,
            'billingAddressLine2' => self::STR_128_VALIDATOR,
            'billingCity' => ['nullable', 'max:64'],
            'billingState' => ['nullable', 'max:2|alpha'],
            'billingZip' => ['nullable', 'max:64'],
            'billingCountryId' => ['nullable', 'integer'],
            'expMonth' => ['nullable', 'integer', 'between:1,12'],
            'expYear' => ['nullable', 'integer', 'between:22,99'],
        ];
    }
}
