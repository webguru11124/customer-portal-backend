<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use App\Traits\Http\Responses\QueryResponseRelations;
use App\Traits\ObjectToResource;
use App\Traits\ValidateObjectClass;
use Aptive\Component\JsonApi\Document;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Component\JsonApi\ResourceDocument;
use Aptive\Illuminate\Http\JsonApi\QueryResponse;
use Illuminate\Http\Request;

/**
 * @method blackListOfResourceAttributes()
 * @method whiteListOfResourceAttributes()
 */
abstract class AbstractFindResponse extends QueryResponse
{
    use ObjectToResource;
    use ValidateObjectClass;
    use QueryResponseRelations;

    /**
     * @throws ValidationException
     */
    protected function toDocument(Request $request, mixed $result): Document
    {
        $this->validateObjectClass($result, $this->getExpectedEntityClass());

        $resource = $this->objectToResource(
            $result,
            $this->getExpectedResourceType(),
            $result->{$this->getIdAttribute()}
        );
        $this->withRelated($resource, $result);

        return (new ResourceDocument())
            ->setResource($resource)
            ->setSelfLink($request->getRequestUri());
    }

    protected function getIdAttribute(): string
    {
        return 'id';
    }

    abstract protected function getExpectedEntityClass(): string;

    abstract protected function getExpectedResourceType(): Resources;
}
