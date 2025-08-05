<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Models\External\AbstractExternalModel;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\ParametersFactories\PestRoutesHttpParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Entity;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;

/**
 * @template T of AbstractExternalModel
 * @template E of Entity
 *
 * @template-extends AbstractExternalRepository<T, E>
 */
abstract class AbstractPestRoutesRepository extends AbstractExternalRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;

    public const REQUEST_TIMEOUT = 10;

    protected function getOfficeId(): int
    {
        if (!$this->getContext()->isOfficeSet()) {
            throw new OfficeNotSetException();
        }

        return $this->getContext()->getOfficeId();
    }

    /**
     * @param int $id
     *
     * @return E|null
     */
    protected function findNative(int $id): Entity|null
    {
        try {
            $officeResource = $this->getPestRoutesClient()->office($this->getOfficeId());

            $searchedResource = $this->getSearchedResource($officeResource);

            if (!method_exists($searchedResource, 'find')) {
                // @codeCoverageIgnoreStart
                throw new InvalidSearchedResourceException(
                    'Method "find" does not implemented in ' . $searchedResource::class
                );
                // @codeCoverageIgnoreEnd
            }

            /** @var E $result */
            $result = $searchedResource->find($id);
        } catch (ResourceNotFoundException) {
            return null;
        }

        return $result;
    }

    /**
     * @param mixed $searchDto
     *
     * @return Collection<int, E>
     *
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotSetException
     * @throws InvalidSearchedResourceException
     */
    protected function searchNative(mixed $searchDto): Collection
    {
        $timeStart = microtime(true);

        $officeResource = $this->getPestRoutesClient()->office($this->getOfficeId());

        $searchedResource = $this->getSearchedResource($officeResource);

        if (!method_exists($searchedResource, 'search')) {
            // @codeCoverageIgnoreStart
            throw new InvalidSearchedResourceException();
            // @codeCoverageIgnoreEnd
        }

        // @phpstan-ignore-next-line
        $searchedResource = $searchedResource
            ->includeData()
            ->search($this->getHttpParametersFactory()->createSearch($searchDto));

        if ($this->getContext()->isPaginationSet()) {
            return new Collection($searchedResource->paginate(
                $this->getContext()->getPage(),
                $this->getContext()->getPageSize()
            )->items);
        }

        $searchedResourceCollection = new Collection($searchedResource->all()->items);

        $timeEnd = microtime(true);

        $this->getLogger()?->info(sprintf(
            'Execution time %s: %f',
            get_class($searchedResource),
            ($timeEnd - $timeStart)
        ));

        return $searchedResourceCollection;
    }

    protected function getEntityName(): string
    {
        /** @var string $name */
        $name = preg_replace('/.*\\\\/', '', static::class);
        $name = str_replace(['PestRoutes', 'Repository'], '', $name);

        return $name;
    }

    abstract protected function getHttpParametersFactory(): PestRoutesHttpParametersFactory;

    abstract protected function getSearchedResource(OfficesResource $officesResource): Resource;
}
