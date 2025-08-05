<?php

namespace Tests\Unit\Traits;

use App\Enums\Resources;
use App\Traits\ObjectToResource;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Tests\TestCase;

class ObjectToResourceTraitTest extends TestCase
{
    private const RESOURCE_ID = 123;
    public const ADDITIONAL_ATTRIBUTE_VALUE = 'Additional Attribute Value';
    public const ADDITIONAL_ATTRIBUTE_CLOSURE_VALUE = 'Additional Attribute Closure Value';

    public $testedClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->testedClass = new class () {
            use ObjectToResource;

            public function testFunction(object $object, Resources $resourceType, int $resourceId): ResourceObject
            {
                return $this->objectToResource($object, $resourceType, $resourceId);
            }

            protected function blackListOfResourceAttributes(): ?array
            {
                return ['attribute1', 'attribute2'];
            }

            protected function whiteListOfResourceAttributes(): ?array
            {
                return ['attribute2', 'attribute3', 'type', 'additional_attribute', 'additional_attribute_closure'];
            }

            protected function additionalAttributes(): array
            {
                return [
                    'additional_attribute' => ObjectToResourceTraitTest::ADDITIONAL_ATTRIBUTE_VALUE,
                    'additional_attribute_closure' => fn (object $object) => ObjectToResourceTraitTest::ADDITIONAL_ATTRIBUTE_CLOSURE_VALUE,
                ];
            }
        };
    }

    public function test_it_creates_filtered_resource()
    {
        $object = (object) [
            'attribute1' => 'value1',
            'attribute2' => 'value2',
            'attribute3' => 'value3',
            'attribute4' => 'value4',
            'attribute5' => 'value5',
        ];

        $expected = [
            'attribute3' => 'value3',
            'additional_attribute' => self::ADDITIONAL_ATTRIBUTE_VALUE,
            'additional_attribute_closure' => self::ADDITIONAL_ATTRIBUTE_CLOSURE_VALUE,
        ];

        $result = $this->testedClass->testFunction($object, Resources::APPOINTMENT, self::RESOURCE_ID);

        self::assertInstanceOf(ResourceObject::class, $result);
        self::assertEquals($expected, $result->getAttributes());

        return $result;
    }

    public function test_it_throws_validation_exception()
    {
        $object = (object) [
            'type' => 'type1',
        ];

        $this->expectException(ValidationException::class);

        $result = $this->testedClass->testFunction($object, Resources::APPOINTMENT, self::RESOURCE_ID);

        unset($result);
    }
}
