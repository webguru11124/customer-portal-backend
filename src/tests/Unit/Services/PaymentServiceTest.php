<?php

namespace Tests\Unit\Services;

use App\DTO\AddPaymentDTO;
use App\Enums\Models\Customer\AutoPay;
use App\Enums\Models\Payment\PaymentMethod;
use App\Events\Payment\PaymentMade;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Interfaces\Repository\PaymentRepository;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentModel;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Event;
use Mockery;
use RuntimeException;
use Tests\Data\CustomerData;
use Tests\Data\PaymentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class PaymentServiceTest extends TestCase
{
    use RandomIntTestData;

    private const PAYMENT_AMOUNT_CENTS = 123456;

    public $mockPaymentRepository;
    public $paymentId;
    public $branchId;
    public $accountNumber;
    public $wrongAccountNumber;
    public $paymentProfileId;
    public CustomerModel $customer;

    public function setUp(): void
    {
        parent::setUp();
        $this->branchId = $this->getTestOfficeId();
        $this->paymentId = $this->getTestPaymentId();
        $this->paymentProfileId = $this->getTestPaymentProfileId();
        $this->accountNumber = $this->getTestAccountNumber();
        $this->wrongAccountNumber = $this->accountNumber + 1;

        $this->mockPaymentRepository = Mockery::mock(PaymentRepository::class);
        $this->customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->accountNumber,
            'officeID' => $this->branchId,
            'aPay' => AutoPay::CREDIT_CARD->value,
            'autoPayPaymentProfileID' => $this->paymentProfileId,
        ])->first();
    }

    public function test_getpaymentids_gets_payment_ids(): void
    {
        $paymentIds = [999887, 999888, 999889];
        $payments = PaymentData::getTestEntityData(
            3,
            ['paymentID' => $paymentIds[0]],
            ['paymentID' => $paymentIds[1]],
            ['paymentID' => $paymentIds[2]],
        );

        $this->mockPaymentRepository
            ->shouldReceive('office')
            ->once()
            ->with($this->customer->officeId)
            ->andReturnSelf();

        $this->mockPaymentRepository
            ->shouldReceive('searchByCustomerId')
            ->with([$this->customer->id])
            ->andReturn($payments)
            ->once();

        $this->assertEquals(
            $paymentIds,
            (new PaymentService($this->mockPaymentRepository))->getPaymentIds($this->customer)
        );
    }

    public function test_getpaymentids_throws_paymentsnotfoundexception_on_paymentsnotfoundexception(): void
    {
        $this->mockPaymentRepository
            ->shouldReceive('office')
            ->once()
            ->with($this->customer->officeId)
            ->andReturnSelf();

        $this->mockPaymentRepository
            ->shouldReceive('searchByCustomerId')
            ->with([$this->customer->id])
            ->andThrow(RuntimeException::class)
            ->once();

        $this->expectException(RuntimeException::class);

        (new PaymentService($this->mockPaymentRepository))->getPaymentIds($this->customer);
    }

    public function test_getpayment_gets_payment(): void
    {
        $paymentModel = $this->getValidPayment();
        $this->givenPaymentRepositoryFindsPaymentById($paymentModel);

        $payment = (new PaymentService($this->mockPaymentRepository))->getPayment($this->customer, $this->paymentId);

        $this->assertSame($this->paymentId, $payment->id);
    }

    public function test_getpayment_throws_unauthorized_on_invalid_customer_id(): void
    {
        $paymentModel = $this->getValidPayment(['customerID' => $this->wrongAccountNumber]);

        $this->givenPaymentRepositoryFindsPaymentById($paymentModel);

        $this->expectException(UnauthorizedException::class);

        (new PaymentService($this->mockPaymentRepository))->getPayment($this->customer, $this->paymentId);
    }

    public function test_addpayment_creates_payment_and_gets_it_for_customer(): void
    {
        $paymentDTO = $this->getPaymentDTO($this->paymentProfileId);
        $this->givenPaymentRepositoryAddsPayment($paymentDTO);
        $paymentModel = $this->getValidPayment();
        $this->givenPaymentRepositoryFindsPaymentById($paymentModel);

        Event::fake();

        $payment = (new PaymentService($this->mockPaymentRepository))->addPayment($this->customer, $paymentDTO);

        Event::assertDispatched(function (PaymentMade $event): bool {
            return $event->getAccountNumber() === $this->customer->id
                && $event->quantity === (int) round(self::PAYMENT_AMOUNT_CENTS / 100);
        });

        $this->assertEquals($this->paymentId, $payment->id);
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function test_addpayment_throws_exceptions(string $exceptionClass): void
    {
        $paymentDTO = $this->getPaymentDTO($this->paymentProfileId);

        $this->mockPaymentRepository
            ->shouldReceive('office')
            ->once()
            ->with($this->customer->officeId)
            ->andReturnSelf();

        $this->mockPaymentRepository
            ->shouldReceive('addPayment')
            ->with($paymentDTO)
            ->andThrow($exceptionClass)
            ->once();

        $this->expectException($exceptionClass);

        Event::fake();

        (new PaymentService($this->mockPaymentRepository))->addPayment($this->customer, $paymentDTO);

        Event::assertNotDispatched(PaymentMade::class);
    }

    public function exceptionDataProvider(): array
    {
        return [
            [PaymentNotCreatedException::class],
            [CreditCardAuthorizationException::class],
        ];
    }

    public function test_addpayment_creates_payment_and_throws_entitynotfoundexception_on_entitynotfoundexception(): void
    {
        $paymentDTO = $this->getPaymentDTO($this->paymentProfileId);
        $this->givenPaymentRepositoryAddsPayment($paymentDTO);

        $this->mockPaymentRepository
            ->shouldReceive('office')
            ->once()
            ->with($this->customer->officeId)
            ->andReturnSelf();

        $this->mockPaymentRepository
            ->shouldReceive('find')
            ->with($this->paymentId)
            ->andThrow(new EntityNotFoundException())
            ->once();

        Event::fake();

        $this->expectException(EntityNotFoundException::class);

        (new PaymentService($this->mockPaymentRepository))->addPayment($this->customer, $paymentDTO);

        Event::assertNotDispatched(PaymentMade::class);
    }

    protected function getPaymentDTO($paymentProfileId): AddPaymentDTO
    {
        return AddPaymentDTO::from([
            'paymentMethod' => PaymentMethod::CREDIT_CARD,
            'customerId' => $this->customer->id,
            'amountCents' => self::PAYMENT_AMOUNT_CENTS,
            'paymentProfileId' => $paymentProfileId,
        ]);
    }

    protected function givenPaymentRepositoryAddsPayment(AddPaymentDTO $paymentDTO): void
    {
        $this->mockPaymentRepository
            ->shouldReceive('office')
            ->once()
            ->with($this->customer->officeId)
            ->andReturnSelf();

        $this->mockPaymentRepository
            ->shouldReceive('addPayment')
            ->with($paymentDTO)
            ->andReturn($this->paymentId)
            ->once();
    }

    protected function givenPaymentRepositoryFindsPaymentById(PaymentModel $payment): void
    {
        $this->mockPaymentRepository
            ->shouldReceive('office')
            ->once()
            ->with($this->customer->officeId)
            ->andReturnSelf();

        $this->mockPaymentRepository
            ->shouldReceive('find')
            ->with($this->paymentId)
            ->andReturn($payment)
            ->once();
    }

    /**
     * @param array<string, string> $override
     *
     * @return PaymentModel
     */
    protected function getValidPayment(array $override = []): PaymentModel
    {
        $paymentData = array_merge(
            [
                'paymentID' => (string) $this->paymentId,
                'customerID' => $this->customer->id,
                'paymentMethod' => '4',
                'date' => '2022-07-01 06:30:55',
                'amount' => '1234.56',
            ],
            $override
        );

        return PaymentData::getTestEntityData(1, $paymentData)->first();
    }
}
