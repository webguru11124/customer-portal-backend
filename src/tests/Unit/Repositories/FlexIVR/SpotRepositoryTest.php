<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\FlexIVR;

use App\DTO\FlexIVR\Spot\SearchSpots;
use App\Logging\ApiLogger;
use App\Repositories\FlexIVR\SpotRepository;

final class SpotRepositoryTest extends WrappedBaseRepositoryTest
{
    public function test_get_spots_returns_spots(): void
    {
        $dto = new SearchSpots(
            officeId: $this->getTestOfficeId(),
            customerId: $this->getTestAccountNumber(),
            lat: 0.1,
            lng: -0.1,
            state: 'GA',
            isInitial: false,
        );

        $clientMock = $this->mockGetHttpRequest(
            'https://example.com/availableSpotV2',
            $dto->toArray(),
            sprintf(
                '{"meta":{},"spots":[{"spotID":"%d","date":"2021-01-01","window":"AM","isAroSpot":true}]}',
                $this->getTestSpotId(),
            )
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new SpotRepository($clientMock, $configMock, $loggerMock);

        $spots = $repository->getSpots($dto);

        $this->assertCount(1, $spots);
        $this->assertSame($this->getTestSpotId(), $spots[0]->id);
    }
}
