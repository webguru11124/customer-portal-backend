<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\DTO\Payment\AutoPayStatusRequestDTO;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\Events\PaymentMethod\AchAdded;
use App\Exceptions\Account\CleoCrmAccountNotFoundException;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @final
 */
class CreateAchPaymentProfileActionV2
{
    public function __construct(
        protected readonly AptivePaymentRepository $aptivePaymentRepository,
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException|\Throwable
     */
    public function __invoke(CreatePaymentProfileRequestDTO $dto): string
    {
        $paymentProfile = $this
            ->aptivePaymentRepository
            ->createPaymentProfile($dto);

        if ($dto->isAutoPay) {
            $this->aptivePaymentRepository->updateAutoPayStatus(
                new AutoPayStatusRequestDTO($dto->customerId, $paymentProfile->paymentMethodId)
            );
        }

        AchAdded::dispatch($dto->customerId);

        return $paymentProfile->paymentMethodId;
    }
}
