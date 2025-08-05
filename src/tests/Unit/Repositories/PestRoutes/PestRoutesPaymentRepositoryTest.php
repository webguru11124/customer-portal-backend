<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\AddPaymentDTO;
use App\Enums\Models\Payment\PaymentMethod;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\Payment\UnknownPaymentMethodException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Repositories\Mappers\PestRoutesPaymentToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\PaymentParametersFactory;
use App\Repositories\PestRoutes\PestRoutesPaymentRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Client;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Payments\Params\CreatePaymentsParams;
use Aptive\PestRoutesSDK\Resources\Payments\Params\SearchPaymentsParams;
use Aptive\PestRoutesSDK\Resources\Payments\Payment;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentMethod as PestRoutesPaymentMethod;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentsResource;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentStatus;
use Illuminate\Support\Collection;
use Mockery;
use Tests\Data\PaymentData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\PestroutesSdkExceptionProvider;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesPaymentRepositoryTest extends TestCase
{
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;
    use PestroutesSdkExceptionProvider;
    use PestRoutesClientMockBuilderAware;
    use RandomIntTestData;

    private PestRoutesPaymentRepository $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesPaymentRepository(
            new PestRoutesPaymentToExternalModelMapper(),
            new PaymentParametersFactory(),
        );
    }

    public function tearDown(): void
    {
        unset($this->subject);
    }

    protected function getSubject(): PestRoutesPaymentRepository
    {
        return $this->subject;
    }

    public function test_search_by_customer_loads_payments(): void
    {
        $payments = PaymentData::getTestData(3);

        $pestRoutesClient = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentsResource::class)
            ->callSequense('payments', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (SearchPaymentsParams $params): bool {
                return $params->toArray() === [
                    'officeIDs' => [$this->getTestOfficeId()],
                    'customerIDs' => [$this->getTestAccountNumber()],
                    'status' => PaymentStatus::Successful->value,
                    'includeData' => 0,
                ];
            })
            ->willReturn(new PestRoutesCollection($payments->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClient);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->searchByCustomerId(
                [$this->getTestAccountNumber()],
            );

        foreach ($result as $key => $payment) {
            self::assertEquals($payments[$key]->id, $payment->id);
        }
    }

    public function test_search_by_customer_throws_exception_on_error(): void
    {
        $pestRoutesClient = $this
            ->getPestRoutesClientMockBuilder()
             ->office($this->getTestOfficeId())
             ->resource(PaymentsResource::class)
             ->callSequense('payments', 'includeData', 'search')
             ->methodExpectsArgs('search', function (SearchPaymentsParams $params): bool {
                 return $params->toArray() === [
                         'officeIDs' => [$this->getTestOfficeId()],
                         'customerIDs' => [$this->getTestAccountNumber()],
                         'status' => PaymentStatus::Successful->value,
                         'includeData' => 0,
                     ];
             })
             ->willThrow(new ResourceNotFoundException('Test'))
             ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClient);

        $this->expectException(ResourceNotFoundException::class);

        $this->subject
            ->office($this->getTestOfficeId())
            ->searchByCustomerId(
                [$this->getTestAccountNumber()],
            );
    }

    public function test_find_loads_payment_by_id(): void
    {
        $payment = PaymentData::getTestData(1, [
            'paymentID' => $this->getTestPaymentId(),
        ])->first();

        $pestRoutesClient = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentsResource::class)
            ->callSequense('payments', 'find')
            ->methodExpectsArgs('find', [$this->getTestPaymentId()])
            ->willReturn($payment)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClient);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->find($this->getTestPaymentId());

        self::assertSame($this->getTestPaymentId(), $result->id);
    }

    public function test_find_throws_exception_when_payment_not_found(): void
    {
        $pestRoutesClient = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentsResource::class)
            ->callSequense('payments', 'find')
            ->methodExpectsArgs('find', [$this->getTestPaymentId()])
            ->willThrow(new ResourceNotFoundException())
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClient);

        $this->expectException(EntityNotFoundException::class);

        $this->subject
            ->office($this->getTestOfficeId())
            ->find($this->getTestPaymentId());
    }

    /**
     * @dataProvider paymentMethodProvider
     */
    public function test_it_creates_payment(
        PaymentMethod $paymentMethod,
        PestRoutesPaymentMethod $pestRoutesPaymentMethod,
    ): void {
        $newPaymentId = $this->getTestPaymentId();
        $dto = $this->getAddPaymentDTO($paymentMethod);

        $pestRoutesClient = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentsResource::class)
            ->callSequense('payments', 'create')
            ->methodExpectsArgs(
                'create',
                function (CreatePaymentsParams $params) use ($pestRoutesPaymentMethod): bool {
                    return $params->toArray() === [
                            'doCharge' => '1',
                            'paymentMethod' => $pestRoutesPaymentMethod->value,
                            'customerID' => $this->getTestAccountNumber(),
                            'amount' => 123.45,
                            'paymentProfileID' => $this->getTestPaymentProfileId(),
                            'officeID' => $this->getTestOfficeId(),
                        ];
                }
            )
            ->willReturn($newPaymentId)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClient);

        self::assertSame(
            $newPaymentId,
            $this->subject->office($this->getTestOfficeId())->addPayment($dto)
        );
    }

    public function test_it_throws_exception_when_invalid_payment_method(): void
    {
        $dto = $this->getAddPaymentDTO();
        $dto->paymentMethod = PaymentMethod::OTHER;

        $pestRoutesClientMock = Mockery::mock(Client::class);
        $pestRoutesClientMock->expects('office')->never();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(UnknownPaymentMethodException::class);

        $this->subject->office($this->getTestOfficeId())->addPayment($dto);
    }

    /**
     * @return iterable<string, array{0: PaymentMethod, 1: PestRoutesPaymentMethod}>
     */
    public function paymentMethodProvider(): iterable
    {
        yield 'CREDIT_CARD' => [
            PaymentMethod::CREDIT_CARD,
            PestRoutesPaymentMethod::CreditCard,
        ];
        yield 'ACH' => [
            PaymentMethod::ACH,
            PestRoutesPaymentMethod::ACH,
        ];
    }

    public function test_add_payment_throws_exception_on_error(): void
    {
        $dto = $this->getAddPaymentDTO();

        $pestRoutesClient = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentsResource::class)
            ->callSequense('payments', 'create')
            ->willThrow(new InternalServerErrorHttpException('Test'))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClient);

        $this->expectException(PaymentNotCreatedException::class);

        $this->subject->office($this->getTestOfficeId())->addPayment($dto);
    }

    /**
     * @dataProvider errorPaymentMessagesProvider
     */
    public function test_add_payment_throws_authorization_exception_on_cc_error(string $error): void
    {
        $dto = $this->getAddPaymentDTO();

        $pestRoutesClient = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentsResource::class)
            ->callSequense('payments', 'create')
            ->willThrow(new InternalServerErrorHttpException(sprintf('Payment failed: %s error', $error)))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClient);

        $this->expectException(CreditCardAuthorizationException::class);

        $this->subject->office($this->getTestOfficeId())->addPayment($dto);
    }

    public function errorPaymentMessagesProvider(): array
    {
        $messages = [];
        foreach (CreditCardAuthorizationException::ERROR_MESSAGES as $error) {
            $messages[] = ['error' => $error];
        }
        return $messages;
    }
    protected function getAddPaymentDTO(PaymentMethod $paymentMethod = PaymentMethod::CREDIT_CARD): AddPaymentDTO
    {
        return new AddPaymentDTO(
            paymentMethod: $paymentMethod,
            customerId: $this->getTestAccountNumber(),
            amountCents: 12345,
            paymentProfileId: $this->getTestPaymentProfileId(),
        );
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestPaymentId(),
            $this->getTestPaymentId() + 1,
        ];

        /** @var Collection<int, Payment> $payments */
        $payments = PaymentData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentsResource::class)
            ->callSequense('payments', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchPaymentsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['paymentIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesCollection($payments->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($payments->count(), $result);
    }
}
