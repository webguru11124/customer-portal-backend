<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentProfileModel;

/**
 * @final
 */
class DeletePaymentProfileAction
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly PaymentProfileRepository $paymentProfileRepository
    ) {
    }

    /**
     * @throws PaymentProfileNotDeletedException
     * @throws UnauthorizedException
     * @throws PaymentProfileNotFoundException
     */
    public function __invoke(Account $account, int $paymentProfileId): void
    {
        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = $this->paymentProfileRepository
            ->office($account->office_id)
            ->find($paymentProfileId);

        if ($account->account_number !== $paymentProfile->customerId) {
            $message = sprintf(
                'Payment profile ID=%d does not belong to customer ID=%d',
                $paymentProfileId,
                $account->account_number
            );

            throw new UnauthorizedException($message);
        }

        if ($this->isPaymentProfileSetAsAutopay($paymentProfile)) {
            throw new PaymentProfileNotDeletedException(
                sprintf('Can not delete a payment profile ID=%d because it is set for autopay.', $paymentProfile->id),
                PaymentProfileNotDeletedException::STATUS_LOCKED
            );
        }

        $this->paymentProfileRepository->deletePaymentProfile($account->office_id, $paymentProfileId);
    }

    private function isPaymentProfileSetAsAutopay(PaymentProfileModel $paymentProfile): bool
    {
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($paymentProfile->officeId)
            ->find($paymentProfile->customerId);

        if ($customer->autoPayPaymentProfileId === $paymentProfile->id) {
            return true;
        }

        return false;
    }
}
