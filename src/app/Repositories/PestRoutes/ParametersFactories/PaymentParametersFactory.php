<?php

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Payment\SearchPaymentDTO;
use Aptive\PestRoutesSDK\Resources\Payments\Params\SearchPaymentsParams;

class PaymentParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchPaymentDTO $searchDto
     *
     * @return SearchPaymentsParams
     */
    public function createSearch(mixed $searchDto): SearchPaymentsParams
    {
        $this->validateInput(SearchPaymentDTO::class, $searchDto);

        return new SearchPaymentsParams(
            paymentIds: $searchDto->ids ?: null,
            officeIds: $searchDto->officeId ? [$searchDto->officeId] : null,
            customerIds: empty($searchDto->accountNumber) ? null : $searchDto->accountNumber,
            status: $searchDto->status ?: null,
        );
    }
}
