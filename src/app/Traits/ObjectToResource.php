<?php

namespace App\Traits;

use App\Enums\Resources;
use App\Models\External\AbstractExternalModel;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Closure;

trait ObjectToResource
{
    /**
     * Transforms given object to JsonApi resource.
     * @throws ValidationException
     */
    protected function objectToResource(object $object, Resources $resourceType, int $resourceId): ResourceObject
    {
        $array = $object instanceof AbstractExternalModel
            ? $object->toArray()
            : (array) $object;

        $array = array_merge($array, $this->handleAdditionalAttributes($object));

        $blackList = $this->blackListOfResourceAttributes();

        if (is_array($blackList)) {
            $array = array_filter($array, fn ($key) => !in_array($key, $blackList), ARRAY_FILTER_USE_KEY);
        }

        $whiteList = $this->whiteListOfResourceAttributes();

        if (is_array($whiteList)) {
            $array = array_filter($array, fn ($key) => in_array($key, $whiteList), ARRAY_FILTER_USE_KEY);
        }

        if (isset($array['id'])) {
            unset($array['id']);
        }

        return ResourceObject::make($resourceType->value, $resourceId, $array);
    }

    /**
     * Expects array of attributes that should be included to resource.
     * Black list has higher priority than white list.
     * It means that if the same attribute included both into white and black lists it will be considered as unwanted.
     *
     * @return string[]|null
     */
    protected function blackListOfResourceAttributes(): array|null
    {
        return null;
    }

    /**
     * Expects array of attributes that should be included to resource
     * null - disables filter.
     *
     * @return string[]|null
     */
    protected function whiteListOfResourceAttributes(): array|null
    {
        return null;
    }

    /**
     * @param object $object
     *
     * @return array<string, scalar>
     */
    private function handleAdditionalAttributes(object $object): array
    {
        $additionalAttributesArray = [];

        foreach ($this->additionalAttributes() as $attribute => $value) {
            if (is_callable($value)) {
                $additionalAttributesArray[$attribute] = $value($object);

                continue;
            }

            $additionalAttributesArray[$attribute] = $value;
        }

        return $additionalAttributesArray;
    }

    /**
     * Should return an array of additional attributes with values
     * where array keys are attribute names and array values are attribute values.
     * Values of type Closure are also allowed with a single argument of type object, which is handled in the parent class.
     * e.g.
     * [
     *     'attribute1' => 'Attribute 1 Value',
     *     'attribute2' => fn (Spot $spot) => $spot->start->format('A'),
     * ].
     *
     * @return array<string, scalar|Closure>
     */
    protected function additionalAttributes(): array
    {
        return [];
    }
}
