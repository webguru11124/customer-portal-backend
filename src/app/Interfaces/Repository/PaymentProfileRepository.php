<?php

namespace App\Interfaces\Repository;

use App\DTO\CreatePaymentProfileDTO;
use App\DTO\UpdatePaymentProfileDTO;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotUpdatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Models\External\PaymentProfileModel;
use Illuminate\Support\Collection;

/**
 * Handles payment profile related features.
 *
 * @extends ExternalRepository<PaymentProfileModel>
 */
interface PaymentProfileRepository extends ExternalRepository
{
    /**
     * Add a new payment profile using the payment provider token.
     *
     * @param int $officeId
     * @param CreatePaymentProfileDTO $dto
     *
     *  @throws CreditCardAuthorizationException
     * @return int
     */
    public function addPaymentProfile(int $officeId, CreatePaymentProfileDTO $dto): int;

    /**
     * @param UpdatePaymentProfileDTO $dto
     *
     * @return void
     *
     * @throws PaymentProfileNotUpdatedException
     */
    public function updatePaymentProfile(UpdatePaymentProfileDTO $dto): void;

    /**
     * @param int $officeId
     * @param int $paymentProfileId
     *
     * @throws PaymentProfileNotDeletedException
     */
    public function deletePaymentProfile(int $officeId, int $paymentProfileId): void;

    /**
     * @param int[] $customerIds
     *
     * @return Collection<int, PaymentProfileModel>
     */
    public function searchByCustomerId(array $customerIds): Collection;
}
