<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Appointment\CreateAppointmentDTO;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\Appointment\UpdateAppointmentDTO;
use App\Exceptions\Appointment\AppointmentNotCancelledException;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\Entity\RelationNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\External\AppointmentModel;
use App\Repositories\Mappers\PestRoutesAppointmentToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\AppointmentParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\DateFilterAware;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @extends AbstractPestRoutesRepository<AppointmentModel, Appointment>
 */
class PestRoutesAppointmentRepository extends AbstractPestRoutesRepository implements AppointmentRepository
{
    use PestRoutesClientAwareTrait;
    /**
     * @use EntityMapperAware<Appointment, AppointmentModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;
    use LoggerAwareTrait;
    use DateFilterAware;

    public function __construct(
        PestRoutesAppointmentToExternalModelMapper $entityMapper,
        AppointmentParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function createAppointment(CreateAppointmentDTO $createAppointmentDTO): int
    {
        return $this->getPestRoutesClient()
            ->office($createAppointmentDTO->officeId)
            ->appointments()
            ->create($this->buildCreateAppointmentParams($createAppointmentDTO));
    }

    private function buildCreateAppointmentParams(CreateAppointmentDTO $createAppointmentDTO): CreateAppointmentsParams
    {
        return new CreateAppointmentsParams(
            customerId: $createAppointmentDTO->accountNumber,
            typeId: $createAppointmentDTO->typeId,
            start: Carbon::instance($createAppointmentDTO->start),
            end: Carbon::instance($createAppointmentDTO->end),
            duration: $createAppointmentDTO->duration,
            employeeId: $createAppointmentDTO->employeeId,
            notes: $createAppointmentDTO->notes,
            spotId: $createAppointmentDTO->spotId,
            routeId: $createAppointmentDTO->routeId,
            officeId: $createAppointmentDTO->officeId,
            subscriptionId: $createAppointmentDTO->subscriptionId
        );
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function updateAppointment(UpdateAppointmentDTO $updateAppointmentDTO): int
    {
        return $this->getPestRoutesClient()
            ->office($updateAppointmentDTO->officeId)
            ->appointments()
            ->update($this->buildUpdateAppointmentParams($updateAppointmentDTO));
    }

    private function buildUpdateAppointmentParams(UpdateAppointmentDTO $updateAppointmentDTO): UpdateAppointmentsParams
    {
        return new UpdateAppointmentsParams(
            appointmentId: $updateAppointmentDTO->appointmentId,
            typeId: $updateAppointmentDTO->typeId,
            start: $updateAppointmentDTO->start !== null
                ? Carbon::instance($updateAppointmentDTO->start)
                : null,
            end: $updateAppointmentDTO->end !== null
                ? Carbon::instance($updateAppointmentDTO->end)
                : null,
            duration: $updateAppointmentDTO->duration,
            employeeId: $updateAppointmentDTO->employeeId,
            notes: $updateAppointmentDTO->notes,
            spotId: $updateAppointmentDTO->spotId,
            routeId: $updateAppointmentDTO->routeId,
            subscriptionId: $updateAppointmentDTO->subscriptionId,
            officeId: $updateAppointmentDTO->officeId
        );
    }

    public function cancelAppointment(AppointmentModel $appointment): void
    {
        try {
            $this
                ->getPestRoutesClient()
                ->office($appointment->officeId)
                ->appointments()
                ->cancel($appointment->id);
        } catch (InternalServerErrorHttpException $exception) {
            throw new AppointmentNotCancelledException(previous: $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function getUpcomingAppointments(int $accountNumber): Collection
    {
        $dto = new SearchAppointmentsDTO(
            officeId: $this->getOfficeId(),
            accountNumber: [$accountNumber],
            dateStart: Carbon::now()->format(DateTimeHelper::defaultDateFormat()),
            status: [AppointmentStatus::Pending]
        );

        /** @var Collection<int, AppointmentModel> $result */
        $result = $this->search($dto);

        return $result->filter(
            fn (AppointmentModel $appointmentModel) => $appointmentModel->isTodayOrUpcoming()
        );
    }

    /**
     * @return Collection<int, Appointment>
     *
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotSetException
     * @throws InvalidSearchedResourceException
     * @throws ValidationException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchAppointmentsDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        return $this->searchNative($searchDto);
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->appointments();
    }

    /**
     * @param int[] $customerIds
     *
     * @return Collection<int, AppointmentModel>
     *
     * @throws ValidationException
     * @throws RelationNotFoundException
     */
    public function searchByCustomerId(array $customerIds): Collection
    {
        $searchDto = new SearchAppointmentsDTO(
            officeId: $this->getOfficeId(),
            accountNumber: $customerIds
        );

        /** @var Collection<int, AppointmentModel> $result */
        $result = $this->search($searchDto);

        return $result;
    }
}
