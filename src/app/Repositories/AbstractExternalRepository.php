<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Entity\ExternalModelCanNotBeSearchedByAttribute;
use App\Exceptions\Entity\InvalidRelationName;
use App\Exceptions\Entity\RelationNotFoundException;
use App\Interfaces\ExternalModelMapper;
use App\Interfaces\Repository\ExternalRepository;
use App\Models\External\AbstractExternalModel;
use App\Repositories\Relations\ExternalModelRelation;
use App\Repositories\Relations\LazyRelationLoader;
use App\Repositories\Relations\LazyRelationPicker;
use Aptive\PestRoutesSDK\Entity;
use Illuminate\Support\Collection;

/**
 * @template T of AbstractExternalModel
 * @template E of Entity
 *
 * @template-implements ExternalRepository<T>
 */
abstract class AbstractExternalRepository implements ExternalRepository
{
    protected RepositoryContext $context;

    protected bool $denyLazyLoad = false;

    public function __construct()
    {
        $this->context = new RepositoryContext();
    }

    public function setContext(RepositoryContext $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): RepositoryContext
    {
        return $this->context;
    }

    public function office(int $officeId): static
    {
        $this->context->office($officeId);

        return $this;
    }

    public function paginate(int $page, int $pageSize): static
    {
        $this->context->paginate($page, $pageSize);

        return $this;
    }

    /**
     * @param string[] $relations
     * expected array of relations that supposed to be loaded
     * array example for CustomerEntity: ['appointments', 'subscriptions.serviceType']
     *
     * @throws InvalidRelationName
     */
    public function withRelated(array $relations): static
    {
        foreach ($relations as $relationName) {
            $this->validateRelationName($relationName);
        }

        $this->context->withRelated($relations);

        return $this;
    }

    public function isLazyLoadDenied(): bool
    {
        return $this->denyLazyLoad;
    }

    /**
     * @return T
     *
     * @throws EntityNotFoundException
     * @throws RelationNotFoundException
     */
    public function find(int $id): AbstractExternalModel
    {
        $native = $this->findNative($id);

        if ($native === null) {
            throw new EntityNotFoundException(__(
                'exceptions.entity_not_found',
                [
                    'entity' => $this->getEntityName(),
                    'id' => $id,
                ]
            ));
        }

        /** @var T $result */
        $result = $this->getEntityMapper()->map($native);

        /** @var T $result */
        $result = $this->loadAllRelations($result);

        return $result;
    }

    /**
     * @param int ...$id
     *
     * @return Collection<int, T>
     *
     * @throws RelationNotFoundException
     */
    public function findMany(int ...$id): Collection
    {
        $result = $this
            ->findManyNative(...$id)
            ->map(fn (object $item) => $this->getEntityMapper()->map($item));

        /** @var Collection<int, T> $result */
        $result = $this->loadAllRelations($result);

        return $result;
    }

    /**
     * @param mixed $searchDto
     *
     * @return Collection<int, T>
     *
     * @throws RelationNotFoundException
     */
    public function search(mixed $searchDto): Collection
    {
        $result = $this
            ->searchNative($searchDto)
            ->map(fn (object $item) => $this->getEntityMapper()->map($item));

        /** @var Collection<int, T> $result */
        $result = $this->loadAllRelations($result);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function searchBy(string $attribute, array $values): Collection
    {
        $method = 'searchBy' . ucfirst($attribute);

        if (!method_exists($this, $method)) {
            throw new ExternalModelCanNotBeSearchedByAttribute(static::class, $attribute);
        }

        return $this->$method($values);
    }

    /**
     * @param Collection<int, T>|T $searchResult
     *
     * @return Collection<int, T>|T
     *
     * @throws RelationNotFoundException
     */
    public function loadAllRelations(Collection|AbstractExternalModel $searchResult): Collection|AbstractExternalModel
    {
        if (!$this->getContext()->areRelationsSet()) {
            return $searchResult;
        }

        foreach ($this->getContext()->getRelations() as $relationName) {
            $searchResult = $this->loadRelation($searchResult, $relationName);
        }

        return $searchResult;
    }

    /**
     * @param Collection<int, T>|T $findResult
     * @param string $relationName
     *
     * @return Collection<int, T>|T
     *
     * @throws RelationNotFoundException
     */
    private function loadRelation(
        Collection|AbstractExternalModel $findResult,
        string $relationName
    ): Collection|AbstractExternalModel {
        if ($findResult instanceof Collection && $findResult->isEmpty()) {
            return $findResult;
        }

        preg_match('/^(\w+)\.([A-Za-z0-9_.]+)$/', $relationName, $matches);

        if (!empty($matches)) {
            [1 => $relationName, 2 => $nestedRelation] = $matches;
        }

        /** @var T $entity */
        $entity = $findResult instanceof AbstractExternalModel
            ? $findResult
            : $findResult->first();

        if (!$entity->doesRelationExist($relationName)) {
            throw new RelationNotFoundException($entity, $relationName);
        }

        /** @var ExternalModelRelation $relation */
        $relation = $entity->getRelations()[$relationName];

        /** @var AbstractExternalModel $relatedEntityClass */
        $relatedEntityClass = $relation->getRelatedEntityClass();
        $relatedRepositoryClass = $relatedEntityClass::getRepositoryClass();

        /** @var ExternalRepository $relatedRepository */
        $relatedRepository = app($relatedRepositoryClass);
        if ($officeId = $this->getContext()->getOfficeId()) {
            $relatedRepository->office($officeId);
        }

        if (!empty($nestedRelation)) {
            $relatedRepository->withRelated([$nestedRelation]);
        }

        if ($findResult instanceof AbstractExternalModel) {
            $findResult->setRelated(
                $relationName,
                $relation->getRelated($relatedRepository, $findResult)
            );

            return $findResult;
        }

        if ($relatedRepository->isLazyLoadDenied()) {
            $mapFunction = fn (AbstractExternalModel $entity) => $entity->setRelated(
                $relationName,
                $relation->getRelated($relatedRepository, $entity)
            );

            return $findResult->map($mapFunction);
        }

        $lazyPicker = new LazyRelationPicker(
            $relatedRepository,
            $relation->getForeignKey()
        );

        /** @var AbstractExternalModel $entity */
        foreach ($findResult as $entity) {
            $lazyPicker = $relation->getRelatedLazy($lazyPicker, $entity);
        }

        $lazyRelationLoader = new LazyRelationLoader($relation->getLazyRelationStrategy());

        return $lazyRelationLoader->loadRelated($relationName, $findResult, $lazyPicker);
    }

    /**
     * Expected relation name templates:
     *  RelationName
     *  Relation.NestedRelation
     *  Relation.NestedRelation.NextNestedRelation.
     */
    private function validateRelationName(string $relationName): void
    {
        preg_match('/^\w+(\.\w+)*$/', $relationName, $matches);

        if (empty($matches)) {
            throw new InvalidRelationName($relationName);
        }
    }

    /**
     * @param int $id
     *
     * @return E|null
     */
    abstract protected function findNative(int $id): mixed;

    /**
     * @param int ...$id
     *
     * @return Collection<int, E>
     */
    abstract protected function findManyNative(int ...$id): Collection;

    /**
     * @param mixed $searchDto
     *
     * @return Collection<int, E>
     */
    abstract protected function searchNative(mixed $searchDto): Collection;

    /**
     * @return ExternalModelMapper<E, T>
     */
    abstract protected function getEntityMapper(): ExternalModelMapper;

    abstract protected function getEntityName(): string;
}
