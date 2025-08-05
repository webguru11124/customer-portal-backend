<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Exceptions\Entity\RelationNotFoundException;
use App\Exceptions\Entity\RelationNotLoadedException;
use App\Interfaces\Repository\ExternalRepository;
use App\Repositories\Relations\ExternalModelRelation;
use PHPStan\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Spatie\LaravelData\Data;

abstract class AbstractExternalModel extends Data
{
    /**
     * @var array<string, object|null>
     *  We need public $relatedObjects to cache it properly
     */
    public array $relatedObjects = [];

    /**
     * @return array<string, ExternalModelRelation>
     */
    public function getRelations(): array
    {
        return [];
    }

    public function __get(string $name): mixed
    {
        if ($this->doesRelationExist($name)) {
            return $this->getRelated($name);
        }

        if ($propertyGetter = $this->getPropertyGetter($name)) {
            return $this->$propertyGetter($name);
        }

        throw PropertyDoesNotExist::fromName($name);
    }

    public function getRelated(string $relationName): object|null
    {
        if (!$this->isRelationLoaded($relationName)) {
            throw new RelationNotLoadedException($this, $relationName);
        }

        return $this->relatedObjects[$relationName];
    }

    public function doesRelationExist(string $relationName): bool
    {
        $relations = $this->getRelations();

        return isset($relations[$relationName]);
    }

    public function setRelated(string $relationName, object|null $relatedObject): self
    {
        if (!$this->doesRelationExist($relationName)) {
            throw new RelationNotFoundException($this, $relationName);
        }

        $this->relatedObjects[$relationName] = $relatedObject;

        return $this;
    }

    public function isRelationLoaded(string $relationName): bool
    {
        if (!$this->doesRelationExist($relationName)) {
            throw new RelationNotFoundException($this, $relationName);
        }

        return array_key_exists($relationName, $this->relatedObjects);
    }

    private function getPropertyGetter(string $propertyName): string|null
    {
        $getterName = 'get' . ucfirst($propertyName);

        if (!method_exists($this, $getterName)) {
            return null;
        }

        return $getterName;
    }

    public static function getPrimaryKey(): string
    {
        return 'id';
    }

    /**
     * @return class-string<ExternalRepository<static>>
     */
    abstract public static function getRepositoryClass(): string;
}
