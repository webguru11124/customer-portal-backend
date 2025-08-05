<?php

declare(strict_types=1);

namespace App\Repositories\FlexIVR;

use App\DTO\FlexIVR\Spot\SearchSpots;
use App\DTO\FlexIVR\Spot\Spot;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

/**
 * @final
 */
class SpotRepository extends WrappedBaseRepository
{
    /**
     * @param SearchSpots $dto
     *
     * @return Spot[]
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getSpots(SearchSpots $dto): array
    {
        /** @var object{meta: object, spots: array<int, object{spotID: string, date: string, window: string, isAroSpot: bool}>} $responseData */
        $responseData = $this->sendGetRequest('availableSpotV2', $dto->toArray());

        return array_map(static fn (object $spot) => Spot::fromApiResponse($spot), $responseData->spots);
    }
}
