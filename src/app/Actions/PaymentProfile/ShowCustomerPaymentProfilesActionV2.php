<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\DTO\Payment\BasePaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\Enums\Models\PaymentProfile\StatusType;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use GuzzleHttp\Exception\GuzzleException;

class ShowCustomerPaymentProfilesActionV2
{
    public function __construct(
        private readonly AptivePaymentRepository $paymentProfileRepository
    ) {
    }

    /**
     * @param int $accountNumber
     * @param StatusType[] $statuses
     *
     * @return BasePaymentMethod[]
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function __invoke(
        int $accountNumber,
        array $statuses,
    ): array {
        return $this->paymentProfileRepository->getPaymentMethodsList(new PaymentMethodsListRequestDTO(
            customerId: $accountNumber,
        ));
    }
}
