<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\DTO\Payment\BasePaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Repositories\AptivePayment\AptivePaymentRepository;

class DeletePaymentProfileActionV2
{
    public function __construct(
        private readonly AptivePaymentRepository $paymentRepository
    ) {
    }

    /**
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws PaymentProfileNotDeletedException
     * @throws PaymentProfileNotFoundException
     */
    public function __invoke(int $accountNumber, string $paymentProfileId): bool
    {
        $paymentMethodsList = $this->paymentRepository->getPaymentMethodsList(new PaymentMethodsListRequestDTO(
            customerId: $accountNumber
        ));

        if (0 === count($paymentMethodsList)) {
            throw new PaymentProfileNotFoundException();
        }

        $customerPaymentMethodsList = array_filter(
            $paymentMethodsList,
            static function (BasePaymentMethod $paymentMethod) use ($paymentProfileId) {
                return $paymentMethod->paymentMethodId === $paymentProfileId;
            }
        );

        if (0 === count($customerPaymentMethodsList)) {
            $message = sprintf(
                'Payment profile ID=%s does not belong to customer ID=%d',
                $paymentProfileId,
                $accountNumber
            );

            throw new UnauthorizedException($message);
        }

        /** @var BasePaymentMethod $paymentMethod */
        $paymentMethod = current($customerPaymentMethodsList);

        if ($paymentMethod->isAutoPay || $paymentMethod->isPrimary) {
            throw new PaymentProfileNotDeletedException(
                sprintf('Can not delete a payment profile ID=%s because it is set for autopay or it is primary.', $paymentProfileId),
                PaymentProfileNotDeletedException::STATUS_LOCKED
            );
        }

        return $this->paymentRepository->deletePaymentMethod($paymentProfileId);
    }
}
