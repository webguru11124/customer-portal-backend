<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class Addon extends Data
{
    /**
     * @param int $id
     * @param int $areaPlanId
     * @param int $productId
     * @param bool $isRecurring
     * @param float $initialMin
     * @param float $initialMax
     * @param float $recurringMin
     * @param float $recurringMax
     * @param string|null $createdAt
     * @param string|null $updatedAt
     */
    private function __construct(
        public int $id,
        public int $areaPlanId,
        public int $productId,
        public bool $isRecurring,
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
     *     area_plan_id: int,
     *     product_id: int,
     *     is_recurring: bool,
     *     initial_min: float,
     *     initial_max: float,
     *     recurring_min: float,
     *     recurring_max: float,
     *     created_at: string|null,
     *     updated_at: string|null,
     * } $data
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id,
            areaPlanId: $data->area_plan_id,
            productId: $data->product_id,
            isRecurring: $data->is_recurring,
            initialMin: $data->initial_min,
            initialMax: $data->initial_min,
            recurringMin: $data->recurring_min,
            recurringMax: $data->recurring_max,
            createdAt: $data->created_at,
            updatedAt: $data->created_at,
        );
    }
}
