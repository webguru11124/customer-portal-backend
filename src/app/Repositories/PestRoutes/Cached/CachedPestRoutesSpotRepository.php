<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\SpotRepository;
use App\Models\External\SpotModel;
use App\Repositories\PestRoutes\PestRoutesSpotRepository;
use Illuminate\Support\Collection;

/**
 * @extends AbstractCachedExternalRepositoryWrapper<SpotModel>
 */
class CachedPestRoutesSpotRepository extends AbstractCachedExternalRepositoryWrapper implements SpotRepository
{
    public const TAG_SEARCH_SPOTS = 'SearchSpots_';

    public function __construct(PestRoutesSpotRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getSpotRepositoryCacheTtl()
        };
    }

    public static function buildSearchTag(float $latitude, float $longitude): string
    {
        return self::TAG_SEARCH_SPOTS . $latitude . '_' . $longitude;
    }

    /**
     * @inheritDoc
     */
    public function search(mixed $searchDto): Collection
    {
        return $this
            ->tags([self::buildSearchTag((float) $searchDto->latitude, (float) $searchDto->longitude)])
            ->cached(__FUNCTION__, $searchDto);
    }
}
