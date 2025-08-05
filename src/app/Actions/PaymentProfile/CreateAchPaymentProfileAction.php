<?php

declare(strict_types=1);

namespace App\Actions\PaymentProfile;

use App\DTO\CreatePaymentProfileDTO;
use App\Events\PaymentMethod\AchAdded;
use App\Services\CreditCardService;

/**
 * @final
 */
class CreateAchPaymentProfileAction
{
    public function __construct(
        private readonly CreditCardService $creditCardService
    ) {
    }

    public function __invoke(CreatePaymentProfileDTO $dto): int
    {
        $paymentProfileId = $this
            ->creditCardService
            ->createPaymentProfile($dto);

        AchAdded::dispatch($dto->customerId);

        return $paymentProfileId;
    }
}
