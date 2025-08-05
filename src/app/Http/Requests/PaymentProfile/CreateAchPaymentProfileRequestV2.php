<?php

declare(strict_types=1);

namespace App\Http\Requests\PaymentProfile;

use App\Enums\PaymentService\PaymentProfile\AccountType;
use App\Http\Requests\TransactionSetupCreateAchRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

final class CreateAchPaymentProfileRequestV2 extends TransactionSetupCreateAchRequest
{
    /**
     * @inheritdoc
     * @return array<string, string|array<int, string|In|Rule>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['account_type'] = ['nullable', Rule::in([
            AccountType::PERSONAL_CHECKING->value,
            AccountType::PERSONAL_SAVINGS->value,
            AccountType::BUSINESS_CHECKING->value,
            AccountType::BUSINESS_SAVINGS->value
        ])];

        unset($rules['customer_id']);

        return $rules;
    }
}
