<?php

namespace App\Repositories\PestRoutes;

use App\DTO\AddPaymentDTO;
use App\DTO\Payment\SearchPaymentDTO;
use App\Enums\Models\Payment\PaymentMethod;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\Payment\UnknownPaymentMethodException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Interfaces\Repository\PaymentRepository;
use App\Models\External\PaymentModel;
use App\Repositories\Mappers\PestRoutesPaymentToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\PaymentParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Payments\Params\CreatePaymentsParams;
use Aptive\PestRoutesSDK\Resources\Payments\Payment;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentMethod as PestRoutesPaymentMethod;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentStatus;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Handle PestRoutes's payment related API calls.
 *
 * @extends AbstractPestRoutesRepository<PaymentModel, Payment>
 */
final class PestRoutesPaymentRepository extends AbstractPestRoutesRepository implements PaymentRepository
{
    /**
     * @use EntityMapperAware<Payment, PaymentModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;
    use LoggerAwareTrait;

    public function __construct(
        PestRoutesPaymentToExternalModelMapper $entityMapper,
        PaymentParametersFactory $parametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $parametersFactory;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchPaymentDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        return $this->searchNative($searchDto);
    }

    /**
     * @inheritdoc
     */
    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->payments();
    }

    /**
     * get payment ids for customer.
     *
     * @param int[] $customerIds
     *
     * @return Collection<int, PaymentModel>
     */
    public function searchByCustomerId(array $customerIds): Collection
    {
        $searchDto = new SearchPaymentDTO(
            officeId: $this->getOfficeId(),
            accountNumber: $customerIds,
            status: PaymentStatus::Successful
        );

        /** @var Collection<int, PaymentModel> $payments */
        $payments = $this->search($searchDto);

        return $payments;
    }

    /**
     * add payment (payment will be processed via PestRoutes).
     *
     * @param AddPaymentDTO $dto
     *
     * @return int
     * @throws CreditCardAuthorizationException
     * @throws PaymentNotCreatedException
     */
    public function addPayment(AddPaymentDTO $dto): int
    {
        $params = new CreatePaymentsParams(
            paymentMethod: $this->matchPaymentMethod($dto->paymentMethod),
            customerId: $dto->customerId,
            amount: $dto->getAmount(),
            doCharge: true,
            paymentProfileId: $dto->paymentProfileId,
            officeId: $this->getOfficeId(),
        );

        try {
            return $this
                ->getPestRoutesClient()
                ->office($this->getOfficeId())
                ->payments()
                ->create($params);
        } catch (Throwable $e) {
            foreach (CreditCardAuthorizationException::ERROR_MESSAGES as $errorMessage) {
                if (str_contains($e->getMessage(), $errorMessage)) {
                    throw new CreditCardAuthorizationException(previous: $e);
                }
            }

            throw new PaymentNotCreatedException(previous: $e);
        }
    }

    private function matchPaymentMethod(PaymentMethod $paymentMethod): PestRoutesPaymentMethod
    {
        return match ($paymentMethod) {
            PaymentMethod::CREDIT_CARD => PestRoutesPaymentMethod::CreditCard,
            PaymentMethod::ACH => PestRoutesPaymentMethod::ACH,
            default => throw new UnknownPaymentMethodException(__(
                'exceptions.unknown_payment_method',
                ['method' => $paymentMethod->value]
            )),
        };
    }
}
