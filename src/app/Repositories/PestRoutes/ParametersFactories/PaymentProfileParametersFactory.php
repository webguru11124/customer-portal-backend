<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\PaymentProfile\SearchPaymentProfilesDTO;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\Params\SearchPaymentProfilesParams;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;

class PaymentProfileParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchPaymentProfilesDTO $searchDto
     *
     * @return SearchPaymentProfilesParams
     */
    public function createSearch(mixed $searchDto): SearchPaymentProfilesParams
    {
        $this->validateInput(SearchPaymentProfilesDTO::class, $searchDto);

        return new SearchPaymentProfilesParams(
            paymentProfileIds: $searchDto->ids,
            customerIds: $searchDto->accountNumbers,
            officeIds: [$searchDto->officeId],
            status: array_map(
                fn (StatusType $statusType) => $this->matchStatus($statusType),
                (array) $searchDto->statuses
            ),
            paymentMethod: array_map(
                fn (PaymentMethod $paymentMethod) => $this->matchPaymentMethod($paymentMethod),
                (array) $searchDto->paymentMethods
            )
        );
    }

    private function matchStatus(StatusType $statusType): PaymentProfileStatus
    {
        return match ($statusType) {
            StatusType::DELETED => PaymentProfileStatus::SoftDeleted,
            StatusType::EMPTY => PaymentProfileStatus::Empty,
            StatusType::VALID => PaymentProfileStatus::Valid,
            StatusType::INVALID => PaymentProfileStatus::Invalid,
            StatusType::EXPIRED => PaymentProfileStatus::Expired,
            StatusType::FAILED => PaymentProfileStatus::LastTransactionFailed,
        };
    }

    private function matchPaymentMethod(PaymentMethod $paymentMethod): PaymentProfilePaymentMethod
    {
        return match ($paymentMethod) {
            PaymentMethod::CREDIT_CARD => PaymentProfilePaymentMethod::AutoPayCC,
            PaymentMethod::ACH => PaymentProfilePaymentMethod::AutoPayACH,
        };
    }
}
