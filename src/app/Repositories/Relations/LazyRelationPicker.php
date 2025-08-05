<?php

declare(strict_types=1);

namespace App\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;

class LazyRelationPicker
{
    /** @var array<int, mixed> */
    private array $values = [];

    public function __construct(
        private readonly ExternalRepository $repository,
        private readonly string $foreignKey
    ) {
    }

    public function pick(mixed $value): self
    {
        if ($value === null) {
            return $this;
        }

        if (in_array($value, $this->values)) {
            return $this;
        }

        $this->values[] = $value;

        return $this;
    }

    public function getRepository(): ExternalRepository
    {
        return $this->repository;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * @return mixed[]
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
