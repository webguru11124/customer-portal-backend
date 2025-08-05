<?php

declare(strict_types=1);

namespace App\Repositories\PlanBuilder;

use App\Cache\AbstractCachedWrapper;
use App\DTO\PlanBuilder\Plan;
use App\DTO\PlanBuilder\SearchPlansDTO;
use App\Helpers\ConfigHelper;

/**
 * @method array getPlans()
 * @method array searchPlans(SearchPlansDTO $dto)
 * @method array searchPlansWithProducts(SearchPlansDTO $dto)
 * @method Plan getPlan(int $planId)
 * @method array getPlanWithProducts(int $planId)
 * @method array|object getPlanCategories()
 * @method array|object getAreaPlans()
 * @method array getTargetContractValues()
 * @method array getPlanPricingLevels()
 * @method array getPlanUpgradePaths()
 * @method array getAreaPlanPricings()
 * @method array getSettings()
 */
class CachedPlanBuilderRepository extends AbstractCachedWrapper
{
    /**
     * @param \App\Repositories\PlanBuilder\PlanBuilderRepository $wrapped
     */
    public function __construct(PlanBuilderRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @param string $methodName
     * @return int
     */
    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getPlanBuilderRepositoryCacheTtl()
        };
    }

    /**
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call(string $name, mixed $arguments): mixed
    {
        return $this->cached($name, ...$arguments);
    }
}
