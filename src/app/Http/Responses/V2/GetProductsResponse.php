<?php

declare(strict_types=1);

namespace App\Http\Responses\V2;

use App\DTO\PlanBuilder\Product;
use App\Enums\Resources;
use Aptive\Component\JsonApi\CollectionDocument;
use Aptive\Component\JsonApi\Document;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Aptive\Illuminate\Http\JsonApi\QueryResponse;
use Illuminate\Http\Request;

class GetProductsResponse extends QueryResponse
{
    /**
     * @param Request $request
     * @param Product[] $result
     *
     * @return Document
     *
     * @throws ValidationException
     */
    protected function toDocument(Request $request, mixed $result): Document
    {
        $collectionDocument  = new CollectionDocument();

        foreach ($result as $spot) {
            $collectionDocument->addResource($this->productToResource($spot));
        }

        return $collectionDocument;
    }

    /**
     * @param Product $product
     *
     * @return ResourceObject
     *
     * @throws ValidationException
     */
    private function productToResource(Product $product): ResourceObject
    {
        return ResourceObject::make(Resources::PRODUCT->value, $product->id, [
            'name' => $product->name,
            'image' => $product->image,
            'is_recurring' => $product->isRecurring,
            'initial_price' => $product->initialMin,
            'recurring_price' => $product->recurringMin,
            'pest_routes_id' => $product->extReferenceId,
        ]);
    }
}
