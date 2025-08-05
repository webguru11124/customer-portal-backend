<?php

namespace App\Services;

use App\DTO\PaymentProfile\SearchPaymentProfilesDTO;
use App\DTO\UpdatePaymentProfileDTO;
use App\Exceptions\PaymentProfile\PaymentProfileNotUpdatedException;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use App\Models\External\PaymentProfileModel;

/**
 * @final
 */
class PaymentProfileService
{
    public function __construct(
        protected PaymentProfileRepository $paymentProfileRepository,
    ) {
    }

    /**
     * @param UpdatePaymentProfileDTO $dto
     *
     * @throws PaymentProfileNotUpdatedException
     */
    public function updatePaymentProfile(UpdatePaymentProfileDTO $dto): void
    {
        $this->paymentProfileRepository->updatePaymentProfile($dto);
    }

    /**
     * @param Account $account
     * @param string $merchantId
     *
     * @return PaymentProfileModel|null
     */
    public function getPaymentProfileByMerchantId(Account $account, string $merchantId): PaymentProfileModel|null
    {
        $dto = new SearchPaymentProfilesDTO(
            officeId: $account->office_id,
            accountNumbers: [$account->account_number]
        );

        $paymentProfiles = $this->paymentProfileRepository
            ->office($account->office_id)
            ->search($dto);

        /** @var PaymentProfileModel $paymentProfile */
        foreach ($paymentProfiles as $paymentProfile) {
            if ($paymentProfile->merchantId === $merchantId) {
                return $paymentProfile;
            }
        }

        return null;
    }
}
