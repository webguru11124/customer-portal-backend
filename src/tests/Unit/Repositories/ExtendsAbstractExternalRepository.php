<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\Entity\ExternalModelCanNotBeSearchedByAttribute;
use App\Exceptions\Entity\InvalidRelationName;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\RepositoryContext;
use Tests\Traits\RandomIntTestData;

trait ExtendsAbstractExternalRepository
{
    use RandomIntTestData;

    public function test_is_sets_and_gets_context(): void
    {
        $oldContext = $this->getSubject()->getContext();
        $newContext = new RepositoryContext();

        $this->getSubject()->setContext($newContext);
        $result = $this->getSubject()->getContext();

        self::assertSame($newContext, $result);
        self::assertNotSame($oldContext, $result);
    }

    public function test_it_sets_office_in_context(): void
    {
        $context = $this->getSubject()->getContext();
        self::assertFalse($context->isOfficeSet());

        $officeId = $this->getTestOfficeId();
        $this->getSubject()->office($officeId);

        self::assertTrue($context->isOfficeSet());
        self::assertEquals($officeId, $context->getOfficeId());
    }

    public function test_it_sets_pagination_in_context(): void
    {
        $context = $this->getSubject()->getContext();
        self::assertFalse($context->isPaginationSet());

        $page = random_int(1, 10);
        $pageSize = random_int(10, 50);

        $this->getSubject()->paginate($page, $pageSize);

        self::assertTrue($context->isPaginationSet());
        self::assertEquals($page, $context->getPage());
        self::assertEquals($pageSize, $context->getPageSize());
    }

    /**
     * @param string[] $relations
     *
     * @dataProvider validRelationNamesDataProvider
     */
    public function test_it_sets_relations(array $relations): void
    {
        $context = $this->getSubject()->getContext();
        self::assertFalse($context->areRelationsSet());

        $this->getSubject()->withRelated($relations);

        self::assertTrue($context->areRelationsSet());
        self::assertEquals($relations, $context->getRelations());
    }

    /**
     * @return iterable<int, array<int, string[]>>
     */
    public function validRelationNamesDataProvider(): iterable
    {
        yield [['val1dRelation']];
        yield [['val1dRelation.nestedRelation']];
        yield [['val1d_Relation.nested_Relat1on']];
    }

    /**
     * @param string[] $relations
     *
     * @dataProvider invalidRelationNamesDataProvider
     */
    public function test_it_throws_exception_if_invalid_relation_name_given(array $relations): void
    {
        $this->expectException(InvalidRelationName::class);

        $this->getSubject()->withRelated($relations);
    }

    /**
     * @return iterable<int, array<int, string[]>>
     */
    public function invalidRelationNamesDataProvider(): iterable
    {
        yield [['validRelation', '.invalidRelation']];
        yield [['invalidRelation.']];
        yield [['invalidRelation.nestedRelation.']];
        yield [['.invalidRelation.nestedRelation']];
        yield [['invalid-Relation']];
        yield [['invalid*Relation']];
    }

    public function test_it_throws_an_exception_if_try_to_search_by_attribute_but_method_doesnt_exist(): void
    {
        $this->expectException(ExternalModelCanNotBeSearchedByAttribute::class);

        $this->getSubject()->searchBy('fakeAttribute', []);
    }

    abstract protected function getSubject(): AbstractPestRoutesRepository;

    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    abstract public static function assertNotSame($expected, $actual, string $message = ''): void;

    abstract public static function assertFalse($condition, string $message = ''): void;

    abstract public static function assertTrue($condition, string $message = ''): void;

    abstract public static function assertEquals($expected, $actual, string $message = ''): void;
}
