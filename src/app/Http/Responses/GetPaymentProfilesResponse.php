<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use App\Models\External\PaymentProfileModel;

final class GetPaymentProfilesResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return PaymentProfileModel::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::PAYMENT_PROFILE;
    }
}
