<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class PlanUpgradePaths extends Data
{
    /**
     * @param int $id
     * @param int $upgradeFromPlanId
     * @param int $upgradeToPlanId
     * @param int $priceDiscount
     * @param bool $useToPlanPrice
     * @param string $createdAt
     * @param string $updatedAt
     */
    private function __construct(
        public int $id,
        public int $upgradeFromPlanId,
        public int $upgradeToPlanId,
        public int $priceDiscount,
        public bool $useToPlanPrice,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     upgrade_from_plan_id: int,
     *     upgrade_to_plan_id: int,
     *     price_discount: int,
     *     use_to_plan_price: bool,
     *     created_at: string,
     *     updated_at: string
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id,
            upgradeFromPlanId: $data->upgrade_from_plan_id,
            upgradeToPlanId: $data->upgrade_to_plan_id,
            priceDiscount: $data->price_discount,
            useToPlanPrice: $data->use_to_plan_price,
            createdAt: $data->created_at,
            updatedAt: $data->updated_at,
        );
    }
}
