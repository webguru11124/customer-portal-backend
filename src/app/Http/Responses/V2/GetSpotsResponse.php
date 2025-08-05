<?php

declare(strict_types=1);

namespace App\Http\Responses\V2;

use App\DTO\FlexIVR\Spot\Spot;
use App\Enums\Resources;
use Aptive\Component\JsonApi\CollectionDocument;
use Aptive\Component\JsonApi\Document;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Aptive\Illuminate\Http\JsonApi\QueryResponse;
use Illuminate\Http\Request;

final class GetSpotsResponse extends QueryResponse
{
    /**
     * @param Request $request
     * @param Spot[] $result
     *
     * @return Document
     *
     * @throws ValidationException
     */
    protected function toDocument(Request $request, mixed $result): Document
    {
        $collectionDocument  = new CollectionDocument();

        foreach ($result as $spot) {
            $collectionDocument->addResource($this->spotToResource($spot));
        }

        return $collectionDocument;
    }

    /**
     * @param Spot $spot
     *
     * @return ResourceObject
     *
     * @throws ValidationException
     */
    private function spotToResource($spot): ResourceObject
    {
        return ResourceObject::make(Resources::SPOT->value, $spot->id, [
            'date' => $spot->date,
            'window' => $spot->window,
            'is_aro_spot' => $spot->isAroSpot,
        ]);
    }
}
