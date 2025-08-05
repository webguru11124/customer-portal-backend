<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class TargetContractValue extends Data
{
    /**
     * @param int $id
     * @param int $areaId
     * @param float $value
     * @param string $createdAt
     * @param string $updatedAt
     */
    private function __construct(
        public int $id,
        public int $areaId,
        public float $value,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     area_id: int,
     *     value: float,
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
            areaId: $data->area_id,
            value: $data->value,
            createdAt: $data->created_at,
            updatedAt: $data->updated_at,
        );
    }
}
