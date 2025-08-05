<?php

declare(strict_types=1);

namespace App\Cache;

use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;
use App\Repositories\RepositoryContext;
use Illuminate\Support\Collection;

/**
 * @template T of AbstractExternalModel
 * @implements ExternalRepository<T>
 */
abstract class AbstractCachedExternalRepositoryWrapper extends AbstractCachedWrapper implements ExternalRepository
{
    /** @var ExternalRepository<T> */
    protected mixed $wrapped;

    public function setContext(RepositoryContext $context): static
    {
        $this->wrapped->setContext($context);

        return $this;
    }

    public function getContext(): RepositoryContext
    {
        return $this->wrapped->getContext();
    }

    public function office(int $officeId): static
    {
        $this->wrapped->office($officeId);

        return $this;
    }

    public function paginate(int $page, int $pageSize): static
    {
        $this->wrapped->paginate($page, $pageSize);

        return $this;
    }

    public function withRelated(array $relations): static
    {
        $this->wrapped->withRelated($relations);

        return $this;
    }

    /**
     * @return T
     */
    public function find(int $id): AbstractExternalModel
    {
        $relations = $this->holdRelations();

        /** @var T $cachedResult */
        $cachedResult = $this->cached(__FUNCTION__, $id);

        /** @var T $result */
        $result = $this->withRelated($relations)->loadAllRelations($cachedResult);

        return $result;
    }

    /**
     * @return Collection<int, T>
     */
    public function findMany(int ...$id): Collection
    {
        $relations = $this->holdRelations();

        /** @var Collection<int, T> $cachedResult */
        $cachedResult = $this->cached(__FUNCTION__, ...$id);

        /** @var Collection<int, T> $result */
        $result = $this->withRelated($relations)->loadAllRelations($cachedResult);

        return $result;
    }

    /**
     * @return Collection<int, T>
     */
    public function search(mixed $searchDto): Collection
    {
        $relations = $this->holdRelations();

        /** @var Collection<int, T> $cachedResult */
        $cachedResult = $this->cached(__FUNCTION__, $searchDto);

        /** @var Collection<int, T> $result */
        $result = $this->withRelated($relations)->loadAllRelations($cachedResult);

        return $result;
    }

    /**
     * @return Collection<int, T>
     */
    public function searchBy(string $attribute, mixed $values): Collection
    {
        $relations = $this->holdRelations();

        /** @var Collection<int, T> $cachedResult */
        $cachedResult = $this->cached(__FUNCTION__, $attribute, $values);

        /** @var Collection<int, T> $result */
        $result = $this->withRelated($relations)->loadAllRelations($cachedResult);

        return $result;
    }

    /**
     * @return ExternalRepository<T>
     */
    protected function getWrapped(): ExternalRepository
    {
        return parent::getWrapped();
    }

    /**
     * @inheritDoc
     */
    public function loadAllRelations(
        Collection|AbstractExternalModel $searchResult
    ): Collection|AbstractExternalModel {
        return $this->wrapped->loadAllRelations($searchResult);
    }

    /**
     * @return string[]
     */
    private function holdRelations(): array
    {
        $context = $this->getContext();
        $relations = $context->getRelations();
        $context->withRelated([]);

        return $relations;
    }

    public function isLazyLoadDenied(): bool
    {
        return $this->wrapped->isLazyLoadDenied();
    }
}
