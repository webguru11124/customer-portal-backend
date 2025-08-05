<?php

declare(strict_types=1);

namespace App\DTO\Customer;

use App\Helpers\DateTimeHelper;
use App\Models\External\SubscriptionModel;
use App\Services\SubscriptionUpgradeService;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddon;

final class ShowCustomerSubscriptionResultDTO
{
    public int $id;
    public string|null $serviceType;
    public float $pricePerTreatment;
    public string|null $agreementDate;
    public int $agreementLength;
    /** @var array<int, string>|null */
    public array|null $specialtyPests = null;
    public bool $isUpgradeAvailable = true;

    public function __construct(
        SubscriptionModel $subscription,
        SubscriptionUpgradeService $subscriptionUpgradeService
    ) {
        $this->id = $subscription->id;
        $this->serviceType = $subscription->serviceType->description;
        $this->pricePerTreatment = $subscription->recurringCharge;
        $this->agreementDate = $subscription->contractAdded?->format(DateTimeHelper::defaultDateFormat());
        $this->agreementLength = $subscription->agreementLength;
        $this->isUpgradeAvailable = $subscriptionUpgradeService->isUpgradeAvailable($subscription);
        $planBuilderProducts = $subscriptionUpgradeService->getPlanBuilderPlanSpecialtyPestsProducts($subscription);
        $pestRoutesAddonIds = array_map(
            static fn (TicketAddon $ticketAddon) => $ticketAddon->productId,
            $subscription->recurringTicket?->items ?? []
        );

        foreach ($planBuilderProducts as $planBuilderProduct) {
            if (in_array($planBuilderProduct->extReferenceId, $pestRoutesAddonIds)) {
                $this->specialtyPests[$planBuilderProduct->extReferenceId] = $planBuilderProduct->name;
            }
        }
    }
}
