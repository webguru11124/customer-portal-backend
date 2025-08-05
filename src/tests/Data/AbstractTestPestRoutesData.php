<?php

namespace Tests\Data;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use Aptive\PestRoutesSDK\Entity;
use Illuminate\Support\Collection;

/**
 * @template PestRoutesEntity of Entity
 * @template ExternalModel of AbstractExternalModel
 */
abstract class AbstractTestPestRoutesData
{
    abstract protected static function getSignature(): array;

    /**
     * @return class-string<PestRoutesEntity>
     */
    abstract protected static function getRequiredEntityClass(): string;

    /**
     * @return class-string<ExternalModelMapper>
     */
    abstract protected static function getMapperClass(): string;

    /**
     * @param int $objectsQuantity
     * @param array<string, mixed> ...$substitutions
     *
     * @return Collection<int, PestRoutesEntity>
     */
    public static function getTestData(int $objectsQuantity = 1, array ...$substitutions): Collection
    {
        $collection = self::getRawTestData($objectsQuantity, ...$substitutions);
        $entityClass = static::getRequiredEntityClass();

        return $collection->map(fn ($data) => $entityClass::fromApiObject((object) $data));
    }

    /**
     * @param int $objectsQuantity
     * @param array<string, mixed> ...$substitutions
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function getRawTestData(int $objectsQuantity = 1, array ...$substitutions): Collection
    {
        $collection = new Collection();

        for ($i = 0; $i < $objectsQuantity; $i++) {
            $substitution = $substitutions[$i] ?? [];
            $data = array_merge(static::getSignature(), $substitution);

            $collection->add($data);
        }

        return $collection;
    }

    /**
     * @param int $objectsQuantity
     * @param array<string, mixed> ...$substitutions
     *
     * @return Collection<int, ExternalModel>
     */
    public static function getTestEntityData(int $objectsQuantity = 1, array ...$substitutions): Collection
    {
        /** @var ExternalModelMapper $mapper */
        $mapper = new (static::getMapperClass());

        $pestRoutesTestData = self::getTestData($objectsQuantity, ...$substitutions);

        return $pestRoutesTestData->map(fn (Entity $pestRoutesEntity) => $mapper->map($pestRoutesEntity));
    }
}
