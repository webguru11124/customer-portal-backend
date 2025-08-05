<?php

declare(strict_types=1);

namespace App\DTO\PlanBuilder;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class Product extends Data
{
    /**
     * @param int $id
     * @param int $productSubCategoryId
     * @param int $extReferenceId
     * @param string $name
     * @param int $order
     * @param string $image
     * @param bool $isRecurring
     * @param float $initialMin
     * @param float $initialMax
     * @param float $recurringMin
     * @param float $recurringMax
     * @param string $createdAt
     * @param string $updatedAt
     * @param bool $needsCustomerSupport
     * @param string|null $description
     * @param string $imageName
     */
    private function __construct(
        public int $id,
        public int $productSubCategoryId,
        public int $extReferenceId,
        public string $name,
        public int $order,
        public string $image,
        public bool $isRecurring,
        public float $initialMin,
        public float $initialMax,
        public float $recurringMin,
        public float $recurringMax,
        public string $createdAt,
        public string $updatedAt,
        public bool $needsCustomerSupport,
        public string|null $description,
        public string $imageName,
    ) {
    }

    /**
     * @param object{
     *     id: int,
     *     product_sub_category_id: int,
     *     ext_reference_id: string,
     *     name: string,
     *     order: int,
     *     image: string,
     *     is_recurring: bool,
     *     initial_min: float,
     *     initial_max: float,
     *     recurring_min: float,
     *     recurring_max: float,
     *     created_at: string,
     *     updated_at: string,
     *     needs_customer_support: bool,
     *     description: string|null,
     *     image_name: string
     * } $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: $data->id,
            productSubCategoryId: $data->product_sub_category_id,
            extReferenceId: (int) $data->ext_reference_id,
            name: $data->name,
            order: $data->order,
            image: $data->image,
            isRecurring: $data->is_recurring,
            initialMin: $data->initial_min,
            initialMax: $data->initial_max,
            recurringMin: $data->recurring_min,
            recurringMax: $data->recurring_max,
            createdAt: $data->created_at,
            updatedAt: $data->created_at,
            needsCustomerSupport: $data->needs_customer_support,
            description: $data->description,
            imageName: $data->image_name,
        );
    }
}
