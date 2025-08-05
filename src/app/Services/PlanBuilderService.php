<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\PlanBuilder\AreaPlan;
use App\DTO\PlanBuilder\AreaPlanPricing;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\PlanServiceFrequency;
use App\DTO\PlanBuilder\Product;
use App\DTO\PlanBuilder\SearchPlansDTO;
use App\Exceptions\PlanBuilder\FieldNotFound;
use App\Helpers\ConfigHelper;
use App\Repositories\PlanBuilder\CachedPlanBuilderRepository;

class PlanBuilderService
{
    /** @var Plan[] */
    public $plans = [];
    /**
     * data format: <planId, <areaPlanId, AreaPlan>
     *
     * @var array <int, array<int, AreaPlan>>
     */
    public $areaPlans = [];
    /** @var Product[] */
    public $products = [];
    /** @var array<string, array<string, mixed>> */
    public $settings = [];
    /** @var array<int, int> */
    public $planMapping = [];


    protected CachedPlanBuilderRepository $planBuilderRepository;
    protected int $lowPricingLevelId;
    protected int $activeStatusId;
    protected bool $dataLoaded = false;

    /**
     * @param CachedPlanBuilderRepository $planBuilderRepository
     * @throws FieldNotFound
     */
    public function __construct(
        CachedPlanBuilderRepository $planBuilderRepository,
    ) {
        $this->planBuilderRepository = $planBuilderRepository;
        $this->settings = $this->planBuilderRepository->getSettings();
    }

    protected function loadData(int $officeId): void
    {
        if (!$this->dataLoaded) {
            $this->loadPlansAreaPlansAndProducts($officeId);
            $this->dataLoaded = true;
        }
    }

    /**
     * @return Product[]
     */
    public function getProducts(int $officeId): array
    {
        $this->loadData($officeId);

        return $this->products;
    }

    /**
     * @param int $serviceId
     * @param int $officeId
     * @return Plan
     * @throws FieldNotFound
     */
    public function getServicePlan(int $serviceId, int $officeId): Plan
    {
        $this->loadData($officeId);
        if (! array_key_exists($serviceId, $this->plans)) {
            throw new FieldNotFound();
        }
        $currentPlan = $this->plans[$serviceId];

        $areaPlanId = $currentPlan->areaPlanPricings[$this->getLowPricingLevelId()]->areaPlanId;
        $areaPlan = $this->getAreaPlanForPlan($serviceId, $areaPlanId);
        $currentPlan->defaultAreaPlan = $areaPlan;

        return $currentPlan;
    }

    /**
     * @param int $serviceId
     * @param int $officeId
     * @return Plan[]
     * @throws FieldNotFound
     */
    public function getUpgradesForServicePlan(int $serviceId, int $officeId): array
    {
        $this->loadData($officeId);
        if (!isset($this->plans[$serviceId])) {
            throw new FieldNotFound();
        }

        $planIds = $this->getUpgradePathsForPlan($this->plans[$serviceId]->id);

        $planData = [];
        foreach ($planIds as $planId) {
            $extReferenceId = $this->planMapping[$planId] ?? null;
            if (empty($extReferenceId)) {
                continue;
            }
            $plan = $this->plans[$extReferenceId];
            $planKey = $plan->order . '-' . $planId;
            $planData[$planKey] = $plan;
            $areaPlanId = $plan->areaPlanPricings[$this->getLowPricingLevelId()]->areaPlanId;
            $areaPlan = $this->getAreaPlanForPlan($extReferenceId, $areaPlanId);
            $planData[$planKey]->defaultAreaPlan = $areaPlan;
        }
        ksort($planData);
        return $planData;
    }

    /**
     * @return PlanServiceFrequency[]
     */
    public function getServiceFrequencies(): array
    {
        $orderedServiceFrequencies = [];
        foreach ($this->settings["planServiceFrequencies"] as $serviceFrequency) {
            $orderedServiceFrequencies[$serviceFrequency->id] = $serviceFrequency;
        }

        return $orderedServiceFrequencies;
    }

    /**
     * @param int $planId
     * @param int $areaPlanId
     * @return AreaPlan
     * @throws FieldNotFound
     */
    private function getAreaPlanForPlan(int $planId, int $areaPlanId): AreaPlan
    {
        $lowPricingId = $this->getLowPricingLevelId();

        $areaPlan = $this->areaPlans[$planId][$areaPlanId] ?? $this->areaPlans[$planId][0];

        $pricing = $this->getValidPricing($areaPlan->areaPlanPricings, $lowPricingId);
        $areaPlan->areaPlanPricings = [$pricing];
        $this->removeNotRecurringAddons($areaPlan);
        return $areaPlan;
    }

    /**
     * @param AreaPlan $areaPlan
     * @return void
     */
    private function removeNotRecurringAddons(AreaPlan $areaPlan): void
    {
        $addons = array_filter(
            $areaPlan->addons,
            fn ($addon, $key) => $addon->isRecurring,
            ARRAY_FILTER_USE_BOTH
        );

        $areaPlan->addons = $addons;
    }

    /**
     * @param AreaPlanPricing[] $areaPlanPricings
     * @param int $lowPricingId
     * @return AreaPlanPricing
     * @throws FieldNotFound
     */
    private function getValidPricing(array $areaPlanPricings, int $lowPricingId): AreaPlanPricing
    {
        foreach ($areaPlanPricings as $pricing) {
            if ($pricing->planPricingLevelId === $lowPricingId) {
                return $pricing;
            }
        }
        throw new FieldNotFound();
    }

    /**
     * @return int
     * @throws FieldNotFound
     */
    private function getCPCategoryId(): int
    {
        $categoryName = ConfigHelper::getPlanBuilderCategoryName();
        foreach ($this->settings['planCategories'] as $category) {
            if ($category->name === $categoryName) {
                return $category->id;
            }
        }
        throw new FieldNotFound();
    }

    /**
     * @return int
     * @throws FieldNotFound
     */
    private function getLowPricingLevelId(): int
    {
        if (empty($this->lowPricingLevelId)) {
            $this->setLowPricingLevelId();
        }

        return $this->lowPricingLevelId;
    }

    /**
     * @return void
     * @throws FieldNotFound
     */
    private function setLowPricingLevelId(): void
    {
        $lowPricingName = ConfigHelper::getPlanBuilderLowPricingLevelName();
        foreach ($this->settings["planPricingLevels"] as $planPricingLevel) {
            if ($planPricingLevel->name === $lowPricingName) {
                $this->lowPricingLevelId = $planPricingLevel->id;
                return;
            }
        }
        throw new FieldNotFound();
    }

    /**
     * @return int
     * @throws FieldNotFound
     */
    private function getActiveStatusId(): int
    {
        if (empty($this->activeStatusId)) {
            $this->setActiveStatusId();
        }

        return $this->activeStatusId;
    }

    /**
     * @return void
     * @throws FieldNotFound
     */
    private function setActiveStatusId(): void
    {
        $activeName = ConfigHelper::getPlanBuilderActiveStatusName();
        foreach ($this->settings["planStatuses"] as $planStatus) {
            if ($planStatus->name === $activeName) {
                $this->activeStatusId = $planStatus->id;
                return;
            }
        }
        throw new FieldNotFound();
    }

    /**
     * @param int $id
     * @return int[]
     */
    private function getUpgradePathsForPlan(int $id): array
    {
        $planUpgrades = $this->planBuilderRepository->getPlanUpgradePaths();
        $upgrades = [];
        foreach ($planUpgrades as $upgrade) {
            if ($upgrade->upgradeFromPlanId === $id) {
                $upgrades[] = $upgrade->upgradeToPlanId;
            }
        }

        return $upgrades;
    }

    /**
     * @param int $officeId
     * @return void
     * @throws FieldNotFound
     */
    private function loadPlansAreaPlansAndProducts(int $officeId): void
    {
        $rawPlans = $this->planBuilderRepository->searchPlans(
            SearchPlansDTO::from([
                'planCategoryId' => $this->getCPCategoryId(),
                'planStatusId' => $this->getActiveStatusId(),
            ])
        );
        $cpPlanNames = ConfigHelper::getCPPlans();
        $this->planMapping = [];

        foreach ($rawPlans as $rawPlan) {
            if (array_key_exists($rawPlan->name, $cpPlanNames)) {
                $planWithProducts = $this->planBuilderRepository->getPlanWithProducts($rawPlan->id);
                // It is the only way to get areaPlanPricings or areaPlanId by officeId.
                $planWithPricings = $this->planBuilderRepository->searchPlansWithProducts(
                    SearchPlansDTO::from([
                    'planCategoryId' => $this->getCPCategoryId(),
                    'planStatusId' => $this->getActiveStatusId(),
                    'officeId' => $officeId,
                    'extReferenceId' => $rawPlan->extReferenceId,
                    'plan_pricing_level_id' => $this->getLowPricingLevelId(),
                ])
                );
                $plan = $planWithProducts["plan"];

                /** @var AreaPlan $defaultAreaPlan */
                $defaultAreaPlan = $plan->defaultAreaPlan;
                $this->areaPlans[(int) $plan->extReferenceId][0] = $defaultAreaPlan;
                $plan->defaultAreaPlan = null;

                foreach ($plan->areaPlans as $areaPlan) {
                    $this->areaPlans[(int) $plan->extReferenceId][(int) $areaPlan->id] = $areaPlan;
                }
                ksort($this->areaPlans[$plan->extReferenceId]);
                $plan->areaPlans = [];
                $areaPlanPricings = [];
                foreach ($planWithPricings["plans"][0]->areaPlanPricings as $pricing) {
                    if ($pricing->planPricingLevelId == $this->getLowPricingLevelId()) {
                        $areaPlanPricings[$pricing->planPricingLevelId] = $pricing;
                    }
                }
                $plan->areaPlanPricings = $areaPlanPricings;
                $this->planMapping[(int) $plan->id] = $plan->extReferenceId;
                $this->plans[$plan->extReferenceId] = $plan;
                foreach ($planWithProducts['products'] as $product) {
                    $this->products[$product->id] = $product;
                }
            }
        }
        ksort($this->products);
    }
}
