<?php

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\PaymentModel;
use Aptive\PestRoutesSDK\Resources\Payments\Payment;

/**
 * @implements ExternalModelMapper<Payment, PaymentModel>
 */
class PestRoutesPaymentToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Payment $source
     *
     * @return PaymentModel
     */
    public function map(object $source): PaymentModel
    {
        return PaymentModel::from((array) $source);
    }
}
