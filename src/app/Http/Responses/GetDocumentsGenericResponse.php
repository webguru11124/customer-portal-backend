<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use Aptive\Component\JsonApi\CollectionDocument;
use Aptive\Component\JsonApi\Document;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Aptive\PestRoutesSDK\Resources\Documents\Document as PestRoutesDocument;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use Illuminate\Http\Request;

final class GetDocumentsGenericResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return PestRoutesDocument::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::DOCUMENT;
    }

    protected function toDocument(Request $request, mixed $result): Document
    {
        $collectionDocument  = new CollectionDocument();

        foreach ($result as $item) {
            $collectionDocument->addResource($this->documentToResource($item));
        }

        return $collectionDocument->setSelfLink($request->getRequestUri());
    }

    /**
     * @param Contract|Form|PestRoutesDocument $item
     *
     * @return ResourceObject
     * @throws \Aptive\Component\JsonApi\Exceptions\ValidationException
     */
    private function documentToResource(mixed $item): ResourceObject
    {
        $resource = match (get_class($item)) {
            Form::class => Resources::FORM,
            Contract::class => Resources::CONTRACT,
            default => Resources::DOCUMENT,
        };

        return $this->objectToResource($item, $resource, $item->id);
    }
}
