<?php

declare(strict_types=1);

namespace App\Actions\Spot;

use App\DTO\Route\SearchRoutesDTO;
use App\DTO\Spot\SearchSpotsDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\RouteRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Models\External\CustomerModel;
use App\Models\External\RouteModel;
use App\Models\External\SpotModel;
use App\Services\LoggerAwareTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ShowAvailableSpotsAction
{
    use LoggerAwareTrait;

    private const INITIAL_ROUTE_SUBSTRING = 'initial';

    public function __construct(
        private readonly SpotRepository $spotRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly RouteRepository $routeRepository
    ) {
    }

    /**
     * Searches appointment filtered by given params for given account.
     *
     * @return Collection<int, SpotModel>
     *
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function __invoke(
        int $officeId,
        int $accountNumber,
        string|null $dateStart,
        string|null $dateEnd,
    ): Collection {
        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($officeId)
            ->find($accountNumber);

        $searchRoutesDto = new SearchRoutesDTO(
            officeId: $customer->officeId,
            latitude: $customer->latitude,
            longitude: $customer->longitude,
            maxDistance: ConfigHelper::getSpotsMaxDistance(),
            dateStart: $dateStart,
            dateEnd: $dateEnd
        );

        /** @var Collection<int, RouteModel> $routesCollection */
        $routesCollection = $this->routeRepository
            ->office($searchRoutesDto->officeId)
            ->search($searchRoutesDto);

        $routesCollection = $this->filterRegularRoutes($routesCollection);
        $routeIds = $routesCollection->map(fn (RouteModel $route) => $route->id)->toArray();

        if (empty($routeIds)) {
            return new Collection([]);
        }

        $searchSpotDTO = new SearchSpotsDTO(
            officeId: $customer->officeId,
            latitude: $customer->latitude,
            longitude: $customer->longitude,
            maxDistance: ConfigHelper::getSpotsMaxDistance(),
            dateStart: $dateStart,
            dateEnd: $dateEnd,
            routeIds: $routeIds
        );

        return $this->getSpots($searchSpotDTO, $officeId);
    }

    /**
     * @param SearchSpotsDTO $searchSpotDTO
     * @param int $officeId
     * @return Collection<int, SpotModel>
     */
    protected function getSpots(SearchSpotsDTO $searchSpotDTO, int $officeId): Collection
    {
        /** @var Collection<int, SpotModel> $spotsCollection */
        $spotsCollection = $this->spotRepository
            ->office($officeId)
            ->search($searchSpotDTO)
            ->sortBy(['start']);

        $filterData = [];

        foreach ($spotsCollection as $key => $spot) {
            $date = Carbon::instance($spot->start)->format('Y-m-d A');

            if (in_array($date, $filterData)) {
                $spotsCollection->offsetUnset($key);

                continue;
            }
            $filterData[] = $date;
        }
        unset($filterData);

        return $spotsCollection->values();
    }

    /**
     * @param Collection<int, RouteModel> $routesCollection
     *
     * @return Collection<int, RouteModel>
     */
    private function filterRegularRoutes(Collection $routesCollection): Collection
    {
        $filter = function (RouteModel $route) {
            if (stripos($route->groupTitle, self::INITIAL_ROUTE_SUBSTRING) !== false) {
                return false;
            }

            if (stripos($route->title, self::INITIAL_ROUTE_SUBSTRING) !== false) {
                return false;
            }

            return true;
        };

        return $routesCollection->filter($filter);
    }
}
