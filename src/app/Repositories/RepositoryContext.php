<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\PestRoutesRepository\OfficeNotSetException;

class RepositoryContext
{
    private const DEFAULT_PAGE_SIZE = 20;

    private int $pageSize;
    private int $page;
    private int $officeId;

    /** @var string[] */
    private array $relations = [];

    public function office(int $officeId): self
    {
        $this->officeId = $officeId;

        return $this;
    }

    public function paginate(int $page, int $pageSize = self::DEFAULT_PAGE_SIZE): self
    {
        $this->page = $page;
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * @param array<int, string> $relations
     * expected array of relations that supposed to be loaded
     * array example for CustomerEntity: ['appointments', 'subscriptions.serviceType']
     *
     * @return $this
     */
    public function withRelated(array $relations): self
    {
        $this->relations = $relations;

        return $this;
    }

    public function isOfficeSet(): bool
    {
        return isset($this->officeId);
    }

    public function isPaginationSet(): bool
    {
        return isset($this->page, $this->pageSize);
    }

    public function areRelationsSet(): bool
    {
        return !empty($this->relations);
    }

    public function getOfficeId(): int
    {
        if (!isset($this->officeId)) {
            throw new OfficeNotSetException();
        }

        return $this->officeId;
    }

    public function getPage(): int|null
    {
        return $this->page;
    }

    public function getPageSize(): int|null
    {
        return $this->pageSize;
    }

    /**
     * @return string[]
     */
    public function getRelations(): array
    {
        return $this->relations;
    }
}
