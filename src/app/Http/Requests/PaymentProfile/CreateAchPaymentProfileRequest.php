<?php

declare(strict_types=1);

namespace App\Http\Requests\PaymentProfile;

use App\Http\Requests\TransactionSetupCreateAchRequest;

final class CreateAchPaymentProfileRequest extends TransactionSetupCreateAchRequest
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['customer_id']);

        return $rules;
    }
}
