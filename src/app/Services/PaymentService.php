<?php

namespace App\Services;

use App\DTO\AddPaymentDTO;
use App\Events\Payment\PaymentMade;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Interfaces\Repository\PaymentRepository;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentModel;

class PaymentService
{
    public function __construct(
        protected PaymentRepository $paymentRepository
    ) {
    }

    /**
     * @param CustomerModel $customer
     * @param AddPaymentDTO $dto
     *
     * @return PaymentModel
     *
     * @throws CreditCardAuthorizationException
     * @throws PaymentNotCreatedException
     * @throws EntityNotFoundException
     */
    public function addPayment(CustomerModel $customer, AddPaymentDTO $dto): PaymentModel
    {
        $paymentId = $this
            ->paymentRepository
            ->office($customer->officeId)
            ->addPayment($dto);

        /** @var PaymentModel $payment */
        $payment = $this
            ->paymentRepository
            ->office($customer->officeId)
            ->find($paymentId);

        PaymentMade::dispatch($customer->id, (int) round($dto->amountCents / 100));

        return $payment;
    }

    /**
     * @param CustomerModel $customer
     *
     * @return int[]
     */
    public function getPaymentIds(CustomerModel $customer): array
    {
        return $this
            ->paymentRepository
            ->office($customer->officeId)
            ->searchByCustomerId([$customer->id])
            ->map(fn (PaymentModel $payment) => $payment->id)
            ->toArray();
    }

    /**
     * @param CustomerModel $customer
     * @param int $id
     *
     * @return PaymentModel
     *
     * @throws UnauthorizedException
     * @throws EntityNotFoundException
     */
    public function getPayment(CustomerModel $customer, int $id): PaymentModel
    {
        /** @var PaymentModel $payment */
        $payment = $this
            ->paymentRepository
            ->office($customer->officeId)
            ->find($id);

        if ($payment->customerId !== $customer->id) {
            throw new UnauthorizedException();
        }

        return $payment;
    }
}
