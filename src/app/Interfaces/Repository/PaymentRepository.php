<?php

namespace App\Interfaces\Repository;

use App\DTO\AddPaymentDTO;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Models\External\PaymentModel;
use Illuminate\Support\Collection;

/**
 * Handles payment related features.
 *
 * @extends ExternalRepository<PaymentModel>
 */
interface PaymentRepository extends ExternalRepository
{
    /**
     * Add a new payment.
     *
     * @param AddPaymentDTO $dto
     *
     * @return int
     *
     * @throws PaymentNotCreatedException
     * @throws CreditCardAuthorizationException
     */
    public function addPayment(AddPaymentDTO $dto): int;

    /**
     * Get payments for customer.
     *
     * @param int[] $customerIds
     *
     * @return Collection<int, PaymentModel>
     */
    public function searchByCustomerId(array $customerIds): Collection;
}
