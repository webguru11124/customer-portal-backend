<?php

declare(strict_types=1);

namespace App\DTO\FlexIVR\Spot;

use App\Enums\FlexIVR\Window;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class Spot extends Data
{
    private function __construct(
        public int $id,
        public string $date,
        public Window $window,
        public bool $isAroSpot,
    ) {
    }

    /**
     * @param object{spotID: string, date: string, window: string, isAroSpot: bool} $data
     *
     * @return self
     */
    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: (int) $data->spotID,
            date: $data->date,
            window: Window::from($data->window),
            isAroSpot: (bool) $data->isAroSpot
        );
    }
}
