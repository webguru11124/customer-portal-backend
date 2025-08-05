<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\PaymentProfileModel;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfile;

/**
 * @implements ExternalModelMapper<PaymentProfile, PaymentProfileModel>
 */
class PestRoutesPaymentProfileToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param PaymentProfile $source
     *
     * @return PaymentProfileModel
     */
    public function map(object $source): PaymentProfileModel
    {
        return PaymentProfileModel::from((array) $source);
    }
}
