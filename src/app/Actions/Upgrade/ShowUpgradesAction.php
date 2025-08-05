<?php

declare(strict_types=1);

namespace App\Actions\Upgrade;

use App\DTO\PlanBuilder\Addon;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\Product;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Exceptions\Subscription\SubscriptionNotFound;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use App\Services\AccountService;
use App\Services\PlanBuilderService;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddon;

class ShowUpgradesAction
{
    /**
     * @param AccountService $accountService
     * @param CustomerRepository $customerRepository
     * @param PlanBuilderService $planBuilderService
     */
    public function __construct(
        private readonly AccountService $accountService,
        public CustomerRepository $customerRepository,
        private readonly PlanBuilderService $planBuilderService,
    ) {
    }

    /**
     * @param int $accountNumber
     * @return array<string, mixed>
     * @throws AccountNotFoundException
     * @throws EntityNotFoundException
     * @throws SubscriptionNotFound
     * @throws FieldNotFound
     */
    public function __invoke(int $accountNumber): array
    {
        $account = $this->accountService->getAccountByAccountNumber($accountNumber);
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->withRelated(['subscriptions'])
            ->find($account->account_number);
        /** @var SubscriptionModel $subscription */
        $subscription = $this->getSubscriptionToUpgrade($customer);

        $currentPlan = $this->planBuilderService->getServicePlan(
            $subscription->serviceId,
            $customer->officeId
        );

        $products = $this->planBuilderService->getProducts($customer->officeId);
        $purchasedAddons = [];
        if (!empty($currentPlan->defaultAreaPlan?->addons)) {
            $currentPlanAddonProductIds = $this->getProductIdsFromCurrentPlan($currentPlan);
            /** @var Product[] $products */
            $products = array_filter(
                $products,
                fn (Product $product) => in_array($product->id, $currentPlanAddonProductIds)
            );

            $pestRoutesProductIds = $this->getPestRoutesProductIdsFromSubscription($subscription);

            foreach ($products as $product) {
                if (in_array($product->extReferenceId, $pestRoutesProductIds)) {
                    $purchasedAddons[$product->id] = $product->extReferenceId;
                }
            }
        }

        $upgrades = $this->getUpgradePlansForCustomer($customer);
        $serviceFrequencies = $this->planBuilderService->getServiceFrequencies();

        return [
            'current' => $currentPlan,
            'purchasedAddons' => $purchasedAddons,
            'upgrades' => $upgrades,
            'serviceFrequencies' => $serviceFrequencies,
            'subscription' => $subscription,
            'hasDiscountToPro' => $currentPlan->name === 'Basic' &&
                !empty(array_filter($upgrades, fn (Plan $plan) => ($plan->name === 'Pro +'))),
        ];
    }

    /**
     * @param CustomerModel $customer
     * @return Plan[]
     * @throws SubscriptionNotFound
     */
    private function getUpgradePlansForCustomer(CustomerModel $customer): array
    {
        $subscription = $this->getSubscriptionToUpgrade($customer);

        return $this->planBuilderService->getUpgradesForServicePlan(
            $subscription->serviceId,
            $customer->officeId
        );
    }

    /**
     * @param CustomerModel $customer
     * @return SubscriptionModel
     * @throws SubscriptionNotFound
     */
    private function getSubscriptionToUpgrade(CustomerModel $customer): SubscriptionModel
    {
        $activeSubscriptions = $customer->subscriptions->filter(
            fn (SubscriptionModel $subscription) => $subscription->isActive
        );

        $subscription = $activeSubscriptions->first();
        if (empty($subscription)) {
            throw new SubscriptionNotFound();
        }
        return $subscription;
    }

    /**
     * @param Plan $currentPlan
     * @return array|int[]
     */
    private function getProductIdsFromCurrentPlan(Plan $currentPlan): array
    {
        return array_map(
            fn (Addon $addon) => $addon->productId,
            $currentPlan->defaultAreaPlan?->addons ?? []
        );
    }

    /**
     * @param SubscriptionModel $subscription
     * @return int[]
     */
    private function getPestRoutesProductIdsFromSubscription(SubscriptionModel $subscription): array
    {
        return array_map(
            fn (TicketAddon $item) => $item->productId,
            $subscription->recurringTicket?->items ?? []
        );
    }

}
