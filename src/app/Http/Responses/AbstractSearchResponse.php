<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use App\Interfaces\DTO\SearchResultDto;
use App\Traits\Http\Responses\QueryResponseRelations;
use App\Traits\ObjectToResource;
use App\Traits\ValidateObjectClass;
use Aptive\Component\JsonApi\CollectionDocument;
use Aptive\Component\JsonApi\Document;
use Aptive\Illuminate\Http\JsonApi\QueryResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @method blackListOfResourceAttributes()
 * @method whiteListOfResourceAttributes()
 */
abstract class AbstractSearchResponse extends QueryResponse
{
    use ObjectToResource;
    use ValidateObjectClass;
    use QueryResponseRelations;

    protected function toDocument(Request $request, mixed $result): Document
    {
        $document = new CollectionDocument();

        $objectsCollection = $result instanceof SearchResultDto
            ? $result->getObjectsCollection()
            : $result;

        if (!is_array($objectsCollection) && !$objectsCollection instanceof Collection) {
            throw new InvalidArgumentException(sprintf(
                '%s class expected but %s was given.',
                Collection::class,
                $result::class
            ));
        }

        foreach ($objectsCollection as $item) {
            $this->validateObjectClass($item, $this->getExpectedEntityClass());

            $resource = $this->objectToResource(
                $item,
                $this->getExpectedResourceType(),
                $item->{$this->getIdAttribute()}
            );

            $this->withRelated($resource, $item);

            $document->addResource($resource);
        }

        return $document->setSelfLink($request->getRequestUri());
    }

    protected function getIdAttribute(): string
    {
        return 'id';
    }

    /**
     * @return class-string
     */
    abstract protected function getExpectedEntityClass(): string;

    abstract protected function getExpectedResourceType(): Resources;
}
