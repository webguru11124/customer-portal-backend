<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\PlanBuilder\Addon;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\Product;
use App\Helpers\SubscriptionAddonsConfigHelper;
use App\Models\External\SubscriptionModel;
use App\Repositories\PlanBuilder\PlanBuilderRepository;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddon;

class SubscriptionUpgradeService
{
    use LoggerAwareTrait;

    private const LOG_PREFIX = 'SUBSCRIPTION_UPGRADE_CHECK.';

    public function __construct(
        private readonly PlanBuilderRepository $planBuilderRepository,
        private readonly PlanBuilderService $planBuilderService,
    ) {
    }

    public function isUpgradeAvailable(
        SubscriptionModel $subscription
    ): bool {
        $this->getLogger()?->info(sprintf(
            '%s Start subscription %d upgrade available for customer %d check.',
            self::LOG_PREFIX,
            $subscription->id,
            $subscription->customerId,
        ));

        if (null === $subscription->recurringTicket) {
            $this->getLogger()?->info(sprintf(
                '%s. Subscription %d upgrade available due to: empty recurring ticket.',
                self::LOG_PREFIX,
                $subscription->id,
            ));

            return true;
        }

        try {
            $plan = $this->planBuilderService->getServicePlan(
                serviceId: $subscription->serviceId,
                officeId: $subscription->officeId
            );
        } catch (\Throwable $exception) {
            $this->getLogger()?->error(sprintf('%s %s', self::LOG_PREFIX, $exception->getMessage()));

            return false;
        }

        return $this->isUpgradeAvailableBasedOnPlan($plan) ||
            $this->isUpgradeAvailableBasedOnAddons(
                $plan->defaultAreaPlan?->addons,
                $subscription->recurringTicket->items,
                $subscription->officeId
            );
    }

    /**
     * Specialty Pests = products (addons) that are not included in the current plan.
     *
     * @param SubscriptionModel $subscription
     *
     * @return Product[]
     */
    public function getPlanBuilderPlanSpecialtyPestsProducts(
        SubscriptionModel $subscription
    ): array {
        try {
            $plan = $this->planBuilderService->getServicePlan(
                serviceId: $subscription->serviceId,
                officeId: $subscription->officeId
            );
        } catch (\Throwable $exception) {
            $this->getLogger()?->error(sprintf('%s %s', self::LOG_PREFIX, $exception->getMessage()));

            return [];
        }

        $productIds = array_map(
            static fn (Addon $addon) => $addon->productId,
            $plan->defaultAreaPlan?->addons ?? []
        );

        return array_filter(
            $this->planBuilderService->getProducts($subscription->officeId),
            static fn (Product $product) => $product->isRecurring &&
                in_array($product->id, $productIds)
        );
    }

    /**
     * @param Plan $servicePlan
     *
     * @return bool
     */
    private function isUpgradeAvailableBasedOnPlan(Plan $servicePlan): bool
    {
        try {
            $planUpgradePaths = $this->planBuilderRepository->getPlanUpgradePaths();
        } catch (\Throwable $exception) {
            $this->getLogger()?->error(sprintf('%s %s', self::LOG_PREFIX, $exception->getMessage()));

            return false;
        }

        foreach ($planUpgradePaths as $planUpgrade) {
            if ($planUpgrade->upgradeFromPlanId === $servicePlan->id) {
                $this->getLogger()?->info(sprintf(
                    '%s Upgrade for service plan %d is available. From service %d to %d',
                    self::LOG_PREFIX,
                    $planUpgrade->upgradeFromPlanId,
                    $planUpgrade->upgradeFromPlanId,
                    $planUpgrade->upgradeToPlanId,
                ));

                return true;
            }
        }

        $this->getLogger()?->info(sprintf(
            '%s. Service plan %d upgrade is not available.',
            self::LOG_PREFIX,
            $servicePlan->id,
        ));

        return false;
    }

    /**
     * @param array<int, Addon> $planBuilderAddons
     * @param array<int, TicketAddon> $currentSubscriptionAddons
     *
     * @return bool
     */
    private function isUpgradeAvailableBasedOnAddons(
        array|null $planBuilderAddons,
        array $currentSubscriptionAddons,
        int $officeId
    ): bool {
        $products = $this->planBuilderService->getProducts($officeId);

        if (null === $planBuilderAddons || 0 === count($products)) {
            $this->getLogger()?->info(sprintf(
                '%s. Upgrade not available due to empty additional addons list. Everything is included into the Plan.',
                self::LOG_PREFIX,
            ));

            return false;
        }

        $productIds = array_map(
            static fn (Addon $addon) => $addon->productId,
            $planBuilderAddons
        );

        $disallowedAddonsPests = SubscriptionAddonsConfigHelper::getDisallowedAddonsPests();

        $recurringAddons = array_filter(
            $products,
            static fn (Product $product) => $product->isRecurring &&
                !in_array($product->name, $disallowedAddonsPests) &&
                in_array($product->id, $productIds)
        );

        $recurringAddonsProductIds = array_map(
            static fn (Product $product) => $product->extReferenceId,
            $recurringAddons
        );

        $currentSubscriptionAddons = array_filter(
            $currentSubscriptionAddons,
            static fn (TicketAddon $ticketAddon) => !in_array($ticketAddon->description, $disallowedAddonsPests)
        );

        $currentSubscriptionAddonsIds = array_map(
            static fn (TicketAddon $ticketAddon) => $ticketAddon->productId,
            $currentSubscriptionAddons
        );

        $this->getLogger()?->info(sprintf(
            '%s. Service plan products ids %s. Current subscription addons ids %s.',
            self::LOG_PREFIX,
            implode(',', $recurringAddonsProductIds),
            implode(',', $currentSubscriptionAddonsIds),
        ));

        return 0 !== count(array_diff($recurringAddonsProductIds, $currentSubscriptionAddonsIds));
    }
}
