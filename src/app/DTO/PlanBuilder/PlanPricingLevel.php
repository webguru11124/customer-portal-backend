<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class PlanPricingLevel extends Data
{
    /**
     * @param int $id
     * @param string $name
     * @param int $order
     * @param string $createdAt
     * @param string $updatedAt
     */
    private function __construct(
        public int $id,
        public string $name,
        public int $order,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     name: string,
     *     order: int,
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
            name: $data->name,
            order: $data->order,
            createdAt: $data->created_at,
            updatedAt: $data->updated_at,
        );
    }
}
