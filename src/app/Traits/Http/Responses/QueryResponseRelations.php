<?php

declare(strict_types=1);

namespace App\Traits\Http\Responses;

use App\Enums\Resources;
use App\Models\External\AbstractExternalModel;
use Aptive\Component\JsonApi\Objects\RelationshipObject;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Closure;
use TypeError;

trait QueryResponseRelations
{
    protected function withRelated(ResourceObject $resource, mixed $searchResultItem): ResourceObject
    {
        $relationships = $this->getRelationships();

        foreach ($relationships as $name => $relationship) {
            if (is_callable($relationship)) {
                $relationship = $relationship($searchResultItem);
            }

            if (!$relationship instanceof RelationshipObject) {
                throw new TypeError(sprintf(
                    'Relationship item must be an instance of %s or a Closure that returns %s',
                    RelationshipObject::class,
                    RelationshipObject::class
                ));
            }

            $resource->addRelationship($name, $relationship);
        }

        return $resource;
    }

    /**
     * @return array<string, Closure>
     */
    protected function getRelationships(): array
    {
        return [];
    }

    protected function hasMany(
        Closure $relatedFromSearchResultCallback,
        Resources $relatedResourceType,
        string $primaryKey = 'id'
    ): Closure {
        return function (mixed $searchResultItem) use (
            $relatedFromSearchResultCallback,
            $relatedResourceType,
            $primaryKey,
        ): RelationshipObject {
            $relationship = RelationshipObject::oneToMany();

            $relatedCollection = $relatedFromSearchResultCallback($searchResultItem);

            foreach ($relatedCollection as $relatedObject) {
                $relatedObjectId = $relatedObject->$primaryKey;
                $relatedObjectArray = $relatedObject instanceof AbstractExternalModel
                    ? $relatedObject->toArray()
                    // @codeCoverageIgnoreStart
                    : (array) $relatedObject;
                // @codeCoverageIgnoreEnd

                if (isset($relatedObjectArray['id'])) {
                    unset($relatedObjectArray['id']);
                }
                $relatedResource = ResourceObject::make(
                    $relatedResourceType->value,
                    $relatedObjectId,
                    $relatedObjectArray
                );
                $relationship->addResource($relatedResource);
            }

            return $relationship;
        };
    }
}
