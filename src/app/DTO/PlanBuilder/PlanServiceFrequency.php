<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class PlanServiceFrequency extends Data
{
    /**
     * @param int $id
     * @param int $frequency
     * @param int $order
     * @param string|null $createdAt
     * @param string|null $updatedAt
     * @param string $frequencyDisplay
     */
    private function __construct(
        public int $id,
        public int $frequency,
        public int $order,
        public string|null $createdAt,
        public string|null $updatedAt,
        public string $frequencyDisplay,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     frequency: int,
     *     order: int,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     frequency_display: string
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id,
            frequency: $data->frequency,
            order: $data->order,
            createdAt: $data->created_at,
            updatedAt: $data->updated_at,
            frequencyDisplay: $data->frequency_display,
        );
    }
}
