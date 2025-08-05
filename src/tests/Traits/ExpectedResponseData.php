<?php

namespace Tests\Traits;

trait ExpectedResponseData
{
    abstract private function getLinkPrefix(): string;

    public function getExpectedSearchResponse(string $link, array $dataArray): array
    {
        return [
            'links' => [
                'self' => $this->getLinkPrefix() . $link,
            ],
            'data' => $dataArray,
        ];
    }

    public function getExpectedSearchWithRelatedResponse(string $link, array $dataArray, array $includedArray): array
    {
        return [
            'links' => [
                'self' => $this->getLinkPrefix() . $link,
            ],
            'data' => $dataArray,
            'included' => $includedArray,
        ];
    }

    public function getResourceCreatedExpectedResponse(string $resourceType, int $resourceId): array
    {
        return [
            'data' => [
                'type' => $resourceType,
                'id' => (string) $resourceId,
            ],
        ];
    }

    public function getResourceUpdatedExpectedResponse(string $link, string $resourceType, int $resourceId): array
    {
        return [
            'links' => [
                'self' => $this->getLinkPrefix() . $link,
            ],
            'data' => [
                'type' => $resourceType,
                'id' => (string) $resourceId,
            ],
        ];
    }

    public function getExpectedValidationErrorResponseStructure(array $invalidFields): array
    {
        return ['message', 'errors' => $invalidFields];
    }
}
