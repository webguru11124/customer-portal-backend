<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class AreaPlanPricing extends Data
{
    /**
     * @param int $id
     * @param int $planPricingLevelId
     * @param int $areaPlanId
     * @param float $initialMin
     * @param float $initialMax
     * @param float $recurringMin
     * @param float $recurringMax
     * @param string|null $createdAt
     * @param string|null $updatedAt
     */
    private function __construct(
        public int $id,
        public int $planPricingLevelId,
        public int $areaPlanId,
        public float $initialMin,
        public float $initialMax,
        public float $recurringMin,
        public float $recurringMax,
        public string|null $createdAt,
        public string|null $updatedAt,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     plan_pricing_level_id: int,
     *     area_plan_id: int,
     *     initial_min: float,
     *     initial_max: float,
     *     recurring_min: float,
     *     recurring_max: float,
     *     created_at: string,
     *     updated_at: string,
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id,
            planPricingLevelId: $data->plan_pricing_level_id,
            areaPlanId: $data->area_plan_id,
            initialMin: $data->initial_min,
            initialMax: $data->initial_max,
            recurringMin: $data->recurring_min,
            recurringMax: $data->recurring_max,
            createdAt: $data->created_at ?? null,
            updatedAt: $data->updated_at ?? null,
        );
    }
}
