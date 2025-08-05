<?php

namespace Tests\Unit\Http\Responses;

use App\DTO\Appointment\SearchAppointmentsResultDTO;
use App\Enums\Resources;
use App\Http\Responses\AbstractSearchResponse;
use App\Interfaces\DTO\SearchResultDto;
use App\Models\External\AppointmentModel;
use App\Models\External\DocumentModel;
use Aptive\Component\JsonApi\CollectionDocument;
use Aptive\Component\JsonApi\Document;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Component\JsonApi\Objects\LinksObject;
use Aptive\Component\JsonApi\Objects\RelationshipObject;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Mockery;
use stdClass;
use Tests\Data\AppointmentData;
use Tests\Data\DocumentData;
use Tests\Data\SpotData;
use Tests\TestCase;
use TypeError;

class AbstractSearchResponseTest extends TestCase
{
    private const TEST_URI = 'test/uri';

    public AbstractSearchResponse $testClass;
    public Request $requestMock;
    public SearchResultDto $searchResponseDTOMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestMock = Mockery::mock(Request::class);
        $this->requestMock
            ->shouldReceive('getRequestUri')
            ->withNoArgs()
            ->andReturn(self::TEST_URI);

        $this->testClass = new class () extends AbstractSearchResponse {
            private string $expectedEntityClass;
            private Resources $expectedResourceType;
            private array $relationships = [];

            protected function getExpectedEntityClass(): string
            {
                return $this->expectedEntityClass;
            }

            protected function getExpectedResourceType(): Resources
            {
                return $this->expectedResourceType;
            }

            protected function getRelationships(): array
            {
                return $this->relationships;
            }

            public function setExpectedEntityClass(string $expectedEntityClass): self
            {
                $this->expectedEntityClass = $expectedEntityClass;

                return $this;
            }

            public function setExpectedResourceType(Resources $expectedResourceType): self
            {
                $this->expectedResourceType = $expectedResourceType;

                return $this;
            }

            public function setRelationships(array $relationships): self
            {
                $this->relationships = $relationships;

                return $this;
            }

            public function testToDocument(Request $request, mixed $result): Document
            {
                return parent::toDocument($request, $result);
            }

            public function testHasMany(
                Closure $relatedFromSearchResultCallback,
                Resources $relatedResourceType,
                string $primaryKey = 'id'
            ): Closure {
                return parent::hasMany($relatedFromSearchResultCallback, $relatedResourceType, $primaryKey);
            }
        };
    }

    public function test_it_creates_a_proper_document_with_relations_when_SearchResultDtoInterface_is_given()
    {
        $appointmentsCollection = AppointmentData::getTestEntityData();
        /** @var AppointmentModel $appointmentModel */
        $appointmentModel = $appointmentsCollection->first();

        $documentsCollection = DocumentData::getTestEntityData(
            2,
            ['appointmentID' => $appointmentModel->id],
            ['appointmentID' => $appointmentModel->id]
        );
        /** @var DocumentModel $documentEntity */
        $documentEntity = $documentsCollection->first();

        $appointmentModel->setRelated('documents', $documentsCollection);

        $this->testClass
            ->setExpectedEntityClass(AppointmentModel::class)
            ->setExpectedResourceType(Resources::APPOINTMENT)
            ->setRelationships([
                'documents' => $this->testClass->testHasMany(
                    fn (AppointmentModel $appointmentModel) => $appointmentModel->documents,
                    Resources::DOCUMENT
                ),
            ]);

        $document = $this->testClass->testToDocument($this->requestMock, $appointmentsCollection);

        self::assertInstanceOf(CollectionDocument::class, $document);

        $documentArray = $document->toArray();
        /** @var LinksObject $linksObject */
        $linksObject = $documentArray['links'];
        self::assertEquals(self::TEST_URI, $linksObject->getSelfLink()->getHref());

        self::assertCount($appointmentsCollection->count(), $documentArray['data']);

        /** @var ResourceObject $appointmentResource */
        $appointmentResource = reset($documentArray['data']);
        self::assertEquals($appointmentModel->id, $appointmentResource->getId());
        self::assertEquals(Resources::APPOINTMENT->value, $appointmentResource->getType());

        $relationships = $appointmentResource->getRelationships();
        self::assertArrayHasKey('documents', $relationships);

        /** @var RelationshipObject $documentsRelationship */
        $documentsRelationship = $relationships['documents'];

        $includedResources = $documentsRelationship->getIncludedResources();
        self::assertCount($documentsCollection->count(), $includedResources);

        /** @var ResourceObject $includedDocumentResource */
        $includedDocumentResource = reset($includedResources);
        self::assertEquals($documentEntity->id, $includedDocumentResource->getId());
        self::assertEquals(Resources::DOCUMENT->value, $includedDocumentResource->getType());
    }

    public function test_it_throws_error_when_collection_contains_wrong_objects()
    {
        $spotsCollection = SpotData::getTestData();
        $spot = $spotsCollection->first();

        $documentsCollection = DocumentData::getTestData(1, ['appointmentID' => $spot->id]);

        $searchAppointmentResultDTO = new SearchAppointmentsResultDTO(
            appointments: $spotsCollection,
            documents: $documentsCollection
        );

        $this->expectException(ValidationException::class);

        $this->testClass
            ->setExpectedEntityClass(Appointment::class)
            ->setExpectedResourceType(Resources::APPOINTMENT)
            ->testToDocument($this->requestMock, $searchAppointmentResultDTO);
    }

    public function test_it_throws_error_if_wrong_object_given()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->testClass->testToDocument($this->requestMock, new stdClass());
    }

    public function test_it_throws_error_if_wrong_relationship_is_set()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->testClass
            ->setExpectedResourceType(Resources::APPOINTMENT)
            ->setExpectedEntityClass(Appointment::class)
            ->setRelationships([
                'relationship' => new stdClass(),
            ]);

        $this->expectException(TypeError::class);

        $this->testClass->testToDocument($this->requestMock, AppointmentData::getTestData());
    }

    public function test_it_creates_document_from_collection()
    {
        $this->testClass
            ->setExpectedResourceType(Resources::SPOT)
            ->setExpectedEntityClass(Spot::class);

        $spotsCollection = SpotData::getTestData(3);

        /** @var Document $document */
        $document = $this->testClass->testToDocument($this->requestMock, SpotData::getTestData(3));
        $documentArray = $document->toArray();
        self::assertCount($spotsCollection->count(), $documentArray['data']);
    }
}
