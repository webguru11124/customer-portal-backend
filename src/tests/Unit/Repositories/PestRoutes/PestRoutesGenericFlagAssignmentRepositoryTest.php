<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\GenericFlagAssignmentsRequestDTO;
use App\Repositories\Mappers\PestRoutesGenericFlagAssignmentToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Repositories\PestRoutes\PestRoutesGenericFlagAssignmentRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignment;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignmentsResource;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignmentType;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\Params\CreateGenericFlagAssignmentsParams;
use Tests\Data\GenericFlagAssignmentData;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

final class PestRoutesGenericFlagAssignmentRepositoryTest extends GenericRepositoryWithoutSearchTest
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesGenericFlagAssignmentRepository $genericFlagAssignmentRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->genericFlagAssignmentRepository = new PestRoutesGenericFlagAssignmentRepository(
            new PestRoutesGenericFlagAssignmentToExternalModelMapper(),
            new OfficeParametersFactory(),
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->genericFlagAssignmentRepository;
    }

    public function test_it_assign_generic_flag(): void
    {
        /** @var GenericFlagAssignment $genericFlagAssignment */
        $genericFlagAssignment = GenericFlagAssignmentData::getTestEntityData(1, [
            'genericFlagAssignmentID' => 12345
        ])->first();

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->callSequense('genericFlagAssignments', 'create')
            ->resource(GenericFlagAssignmentsResource::class)
            ->methodExpectsArgs(
                'create',
                function (CreateGenericFlagAssignmentsParams $params) {
                    $array = $params->toArray();

                    return $array['genericFlagID'] === $this->getTestSubscriptionFlagId() &&
                        $array['entityID'] === $this->getTestSubscriptionId() &&
                        $array['type'] === GenericFlagAssignmentType::SUBS;
                }
            )
            ->willReturn($genericFlagAssignment->id)
            ->mock();

        $this->getSubject()->setPestRoutesClient($clientMock);

        self::assertEquals(
            12345,
            $this
                ->getSubject()
                ->office($this->getTestOfficeId())
                ->assignGenericFlag($this->getTestGenericFlagAssignmentsRequestDTO())
        );
    }

    public function test_create_throws_internal_server_error_http_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->getSubject()->office($this->getTestOfficeId())->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->getSubject()->assignGenericFlag($this->getTestGenericFlagAssignmentsRequestDTO());
    }

    private function getTestGenericFlagAssignmentsRequestDTO(): GenericFlagAssignmentsRequestDTO
    {
        return new GenericFlagAssignmentsRequestDTO(
            genericFlagId: $this->getTestSubscriptionFlagId(),
            entityId: $this->getTestSubscriptionId(),
            type: GenericFlagAssignmentType::SUBS,
        );
    }
}
