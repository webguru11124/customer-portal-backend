<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\DTO\Subscriptions\CreateSubscriptionRequestDTO;
use App\DTO\Subscriptions\CreateSubscriptionResponseDTO;
use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Helpers\SubscriptionAddonsConfigHelper;
use App\Helpers\SubscriptionConfigHelper;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;

class CreateFrozenSubscriptionAction
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotSetException
     */
    public function __invoke(
        Account $account,
        CreateSubscriptionRequest $createSubscriptionRequest
    ): CreateSubscriptionResponseDTO {
        return $this->subscriptionRepository
            ->office($account->office_id)
            ->createSubscription(new CreateSubscriptionRequestDTO(
                serviceId: $createSubscriptionRequest->plan_id,
                customerId: $account->account_number,
                followupDelay: SubscriptionConfigHelper::getFrozenSubscriptionFollowupDelay(),
                agreementLength: $createSubscriptionRequest->agreement_length,
                serviceCharge: (int) $createSubscriptionRequest->plan_price_per_treatment,
                initialCharge: (int) $createSubscriptionRequest->plan_price_initial,
                isActive: SubscriptionConfigHelper::isFrozenSubscriptionActive(),
                addOns: $this->prepareAddons($createSubscriptionRequest->recurring_addons ?? []),
                initialAddons: $this->prepareAddons($createSubscriptionRequest->initial_addons ?? []),
                officeId: $account->office_id,
                flag: SubscriptionConfigHelper::getFrozenSubscriptionFlag(),
            ));
    }

    /**
     * @param array<string, mixed> $requestAddons
     *
     * @return array<string, mixed>
     */
    private function prepareAddons(array $requestAddons): array
    {
        return array_map(
            static fn (array $addon): SubscriptionAddonRequestDTO => new SubscriptionAddonRequestDTO(
                productId: $addon['product_id'],
                amount: $addon['amount'] ?? SubscriptionAddonsConfigHelper::getAddonDefaultAmount(),
                description: $addon['name'] ?? SubscriptionAddonsConfigHelper::getAddonDefaultName(),
                quantity: $addon['quantity'] ?? SubscriptionAddonsConfigHelper::getAddonDefaultQuantity(),
                taxable: $addon['taxable'] ?? SubscriptionAddonsConfigHelper::getAddonDefaultTaxable(),
            ),
            $requestAddons
        );
    }
}
