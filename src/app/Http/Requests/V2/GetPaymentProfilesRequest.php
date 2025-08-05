<?php

declare(strict_types=1);

namespace App\Http\Requests\V2;

use App\Enums\Models\PaymentProfile\StatusType;
use Illuminate\Validation\Rules\Enum;

class GetPaymentProfilesRequest extends \App\Http\Requests\GetPaymentProfilesRequest
{
    /**
     * @return array<string, array<int, Enum|string>|string>
     */
    public function rules(): array
    {
        return [
            'statuses' => 'nullable|array',
            'statuses.*' => ['nullable', new Enum(StatusType::class), 'distinct'],
        ];
    }
}
