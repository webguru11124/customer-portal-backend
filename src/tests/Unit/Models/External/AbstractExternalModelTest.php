<?php

namespace Tests\Unit\Models\External;

use App\Exceptions\Entity\RelationNotFoundException;
use App\Exceptions\Entity\RelationNotLoadedException;
use App\Models\External\AbstractExternalModel;
use App\Repositories\Relations\BelongsTo;
use Mockery;
use Mockery\MockInterface;
use PHPStan\BetterReflection\Reflection\Exception\PropertyDoesNotExist;
use Tests\TestCase;

class AbstractExternalModelTest extends TestCase
{
    private const TEST_RELATION_NAME = 'testRelation';
    private const DEFAULT_PRIMARY_KEY = 'id';

    protected MockInterface|AbstractExternalModel $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = Mockery::mock(AbstractExternalModel::class)->makePartial();
    }

    public function test_can_set_and_get_related_objects(): void
    {
        $relations = [self::TEST_RELATION_NAME => new BelongsTo('relatedClass', 'foreignKey')];
        $this->subject
            ->shouldReceive('getRelations')
            ->andReturn($relations);

        $relatedObject = Mockery::mock(AbstractExternalModel::class);
        $this->subject->setRelated(self::TEST_RELATION_NAME, $relatedObject);

        $result = $this->subject->getRelated(self::TEST_RELATION_NAME);

        self::assertSame($relatedObject, $result);
    }

    public function test_set_throws_exception_if_relation_not_exists(): void
    {
        $this->expectException(RelationNotFoundException::class);

        $this->subject->setRelated(self::TEST_RELATION_NAME, Mockery::mock(AbstractExternalModel::class));
    }

    public function test_get_throws_exception_if_relation_not_exists(): void
    {
        $this->expectException(RelationNotFoundException::class);

        $this->subject->getRelated(self::TEST_RELATION_NAME);
    }

    public function test_get_throws_exception_if_related_object_not_loaded(): void
    {
        $relations = [self::TEST_RELATION_NAME => new BelongsTo('relatedClass', 'foreignKey')];
        $this->subject
            ->shouldReceive('getRelations')
            ->andReturn($relations);

        $this->expectException(RelationNotLoadedException::class);

        $this->subject->getRelated(self::TEST_RELATION_NAME);
    }

    public function test_default_primary_key_is_proper(): void
    {
        self::assertEquals(self::DEFAULT_PRIMARY_KEY, $this->subject->getPrimaryKey());
    }

    public function test_related_object_can_be_called_like_property(): void
    {
        $relations = [self::TEST_RELATION_NAME => new BelongsTo('relatedClass', 'foreignKey')];
        $this->subject
            ->shouldReceive('getRelations')
            ->andReturn($relations);

        $relatedObject = Mockery::mock(AbstractExternalModel::class);
        $this->subject->setRelated(self::TEST_RELATION_NAME, $relatedObject);

        $propertyName = self::TEST_RELATION_NAME;

        $result = $this->subject->$propertyName;

        self::assertSame($relatedObject, $result);
    }

    public function test_it_throws_exception_if_trying_to_call_not_defined_property(): void
    {
        $this->expectException(PropertyDoesNotExist::class);

        $this->subject->notDefinedProperty;
    }
}
