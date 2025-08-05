<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Entity\ExternalModelCanNotBeSearchedByAttribute;
use App\Models\External\AbstractExternalModel;
use App\Repositories\RepositoryContext;
use Illuminate\Support\Collection;

/**
 * @template T of AbstractExternalModel
 */
interface ExternalRepository
{
    public function setContext(RepositoryContext $context): static;

    public function getContext(): RepositoryContext;

    public function office(int $officeId): static;

    public function paginate(int $page, int $pageSize): static;

    /**
     * @param string[] $relations
     */
    public function withRelated(array $relations): static;

    /**
     * @param int $id
     *
     * @return T
     *
     * @throws EntityNotFoundException
     */
    public function find(int $id): AbstractExternalModel;

    /**
     * @return Collection<int, T>
     */
    public function findMany(int ...$id): Collection;

    /**
     * @return Collection<int, T>
     */
    public function search(mixed $searchDto): Collection;

    /**
     * @param string $attribute
     * @param array<int, mixed> $values
     *
     * @return Collection<int, T>
     *
     * @throws ExternalModelCanNotBeSearchedByAttribute
     */
    public function searchBy(string $attribute, array $values): Collection;

    /**
     * @param Collection<int, T>|T $searchResult
     *
     * @return Collection<int, AbstractExternalModel>|AbstractExternalModel
     */
    public function loadAllRelations(
        Collection|AbstractExternalModel $searchResult
    ): Collection|AbstractExternalModel;

    public function isLazyLoadDenied(): bool;
}
