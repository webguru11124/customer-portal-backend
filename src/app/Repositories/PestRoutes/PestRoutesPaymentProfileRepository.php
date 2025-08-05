<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\CreatePaymentProfileDTO;
use App\DTO\PaymentProfile\SearchPaymentProfilesDTO;
use App\DTO\UpdatePaymentProfileDTO;
use App\Exceptions\PaymentProfile\AddCreditCardException;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfileNotUpdatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\External\PaymentProfileModel;
use App\Repositories\Mappers\PestRoutesPaymentProfileToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\PaymentProfileParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\Params\CreatePaymentProfilesParams;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\Params\UpdatePaymentProfilesParams;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfile;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileAccountType;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileCheckType;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Handle PestRoutes's payment profile related API calls.
 *
 * @extends AbstractPestRoutesRepository<PaymentProfileModel, PaymentProfile>
 */
class PestRoutesPaymentProfileRepository extends AbstractPestRoutesRepository implements PaymentProfileRepository
{
    use LoggerAwareTrait;
    use PestRoutesClientAwareTrait;
    /**
     * @use EntityMapperAware<PaymentProfile, PaymentProfileModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;
    public function __construct(
        PestRoutesPaymentProfileToExternalModelMapper $entityMapper,
        PaymentProfileParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * Create a new payment profile.
     *
     * @param int $officeId
     * @param CreatePaymentProfileDTO $dto
     *
     * @return int
     *
     * @throws AddCreditCardException
     * @throws PaymentProfileIsEmptyException
     */
    public function addPaymentProfile(int $officeId, CreatePaymentProfileDTO $dto): int
    {
        $params = new CreatePaymentProfilesParams(
            $dto->customerId,
            $dto->paymentMethod,
            $dto->billingName,
            $dto->billingAddressLine1,
            $dto->billingAddressLine2,
            $dto->billingCity,
            $dto->billingState,
            $dto->billingZip,
            null,
            $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayCC ? 'element' : null,
            $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH ? $dto->bankName : null,
            $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH ? $dto->accountNumber : null,
            $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH ? $dto->routingNumber : null,
            $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH && $dto->checkType !== null
                ? PaymentProfileCheckType::from($dto->checkType->value)
                : null,
            $dto->accountType !== null ? PaymentProfileAccountType::from($dto->accountType->value) : null,
            $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayCC ? $dto->token : null,
            null,
            $dto->auto_pay,
            $officeId
        );

        $client = $this->getPestRoutesClient();

        try {
            $paymentProfileId = $client->office($officeId)->paymentProfiles()->create($params);
            /** @var PaymentProfileModel $paymentProfile */
            $paymentProfile = $this->office($officeId)->find($paymentProfileId);
        } catch (Throwable $e) {
            foreach (CreditCardAuthorizationException::ERROR_MESSAGES as $errorMessage) {
                if (str_contains($e->getMessage(), $errorMessage)) {
                    throw new CreditCardAuthorizationException(previous: $e);
                }
            }

            throw new AddCreditCardException(previous: $e);
        }

        if ($paymentProfile->status === PaymentProfileStatus::Empty) {
            throw new PaymentProfileIsEmptyException('Empty payment profile was created');
        }

        // PestRoute does not set BillingName in create API, so we need to call update API to set it properly
        if ($dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayACH) {
            $this->setACHBillingName($officeId, $paymentProfileId, (string) $dto->billingName);
        }

        return $paymentProfileId;
    }

    /**
     * @param int $officeId
     * @param int $paymentProfileID
     * @param string $billingName
     * @return void
     * @throws AddCreditCardException
     */
    protected function setACHBillingName(int $officeId, int $paymentProfileID, string $billingName): void
    {
        $names = explode(' ', $billingName);
        $billingLName = array_pop($names);
        $billingFName = implode(' ', $names);

        try {
            $this->updatePaymentProfile(UpdatePaymentProfileDTO::from([
                'officeId' => $officeId,
                'paymentProfileID' => $paymentProfileID,
                'billingLName' => $billingLName,
                'billingFName' => $billingFName,
            ]));
        } catch (Throwable $exception) {
            throw new AddCreditCardException(previous: $exception);
        }
    }

    /**
     * updates Payment Profile.
     *
     * @param UpdatePaymentProfileDTO $dto
     * @return void
     * @throws PaymentProfileNotFoundException when API failed to load existing payment profile
     * @throws PaymentProfileNotUpdatedException when API failed to update profile
     * @throws InternalServerErrorHttpException on other API http exception
     */
    public function updatePaymentProfile(UpdatePaymentProfileDTO $dto): void
    {
        /** @var PaymentProfileModel $currentProfile */
        $currentProfile = $this->office($dto->officeId)->find($dto->paymentProfileID);
        $currentBillingFName = $currentBillingLName = null;

        if ($dto->billingFName || $dto->billingLName) {
            $names = explode(' ', (string) $currentProfile->billingName);
            $currentBillingLName = array_pop($names);
            $currentBillingFName = implode(' ', $names);
        }

        $payload = new UpdatePaymentProfilesParams(
            $dto->paymentProfileID,
            $dto->billingFName ?? ($dto->billingLName ? $currentBillingFName : $dto->billingFName),
            $dto->billingLName ?? ($dto->billingFName ? $currentBillingLName : $dto->billingLName),
            $dto->billingAddressLine1,
            $dto->billingAddressLine2,
            $dto->billingCity,
            $dto->billingState,
            $dto->billingZip,
            null,
            $dto->expMonth ? substr('0' . $dto->expMonth, 0, 2) : $currentProfile->cardExpirationMonth,
            $dto->expYear ?? $currentProfile->cardExpirationYear,
            $dto->officeId
        );
        $client = $this->getPestRoutesClient();

        if (!$client->office($dto->officeId)->paymentProfiles()->update($payload)) {
            throw new PaymentProfileNotUpdatedException();
        }
    }

    public function deletePaymentProfile(int $officeId, int $paymentProfileId): void
    {
        $client = $this->getPestRoutesClient();

        $isDeleted = $client
                ->office($officeId)
                ->paymentProfiles()
                ->delete($paymentProfileId);

        if (!$isDeleted) {
            throw new PaymentProfileNotDeletedException();
        }
    }

    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchPaymentProfilesDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        return $this->searchNative($searchDto);
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->paymentProfiles();
    }

    /**
     * @inheritDoc
     */
    public function searchByCustomerId(array $customerIds): Collection
    {
        $searchDto = new SearchPaymentProfilesDTO(
            officeId: $this->getOfficeId(),
            accountNumbers: $customerIds
        );

        /** @var Collection<int, PaymentProfileModel> $paymentProfiles */
        $paymentProfiles = $this->search($searchDto);

        return $paymentProfiles;
    }
}
