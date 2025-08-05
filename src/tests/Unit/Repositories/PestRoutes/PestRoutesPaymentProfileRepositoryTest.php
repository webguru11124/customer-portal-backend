<?php

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\CreatePaymentProfileDTO;
use App\DTO\PaymentProfile\SearchPaymentProfilesDTO;
use App\DTO\UpdatePaymentProfileDTO;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Exceptions\PaymentProfile\AddCreditCardException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfileNotUpdatedException;
use App\Models\Account;
use App\Models\External\PaymentProfileModel;
use App\Repositories\Mappers\PestRoutesPaymentProfileToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\PaymentProfileParametersFactory;
use App\Repositories\PestRoutes\PestRoutesPaymentProfileRepository;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\Params\CreatePaymentProfilesParams;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\Params\SearchPaymentProfilesParams;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\Params\UpdatePaymentProfilesParams;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfile;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilesResource;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;
use Tests\Traits\GetPestRoutesPaymentProfile;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\PestroutesSdkExceptionProvider;
use Tests\Traits\RandomIntTestData;

class PestRoutesPaymentProfileRepositoryTest extends TestCase
{
    use GetPestRoutesPaymentProfile;
    use PestroutesSdkExceptionProvider;
    use PestRoutesClientMockBuilderAware;
    use RandomIntTestData;

    protected int $officeId;
    protected int $accountId;
    protected int $paymentProfileId;
    protected string $token = 'ABC-123';
    protected Account $account;
    protected CreatePaymentProfileDTO $dtoForCreditCard;
    protected CreatePaymentProfileDTO $dtoForAch;
    protected PestRoutesPaymentProfileRepository $pestRoutesPaymentProfileRepository;
    protected PaymentProfilesResource|MockInterface $paymentProfilesResourceMock;
    protected string $apiUrl;
    private const ERROR_INVALID_CC = 'PAYMENT ACCOUNT NOT FOUND [103]';

    public function setUp(): void
    {
        parent::setUp();
        $this->apiUrl = config('pestroutes.url') . 'paymentProfile/';
        $this->officeId = $this->getTestOfficeId();
        $this->accountId = $this->getTestAccountNumber();
        $this->paymentProfileId = $this->getTestPaymentProfileId();
        $this->account = Account::factory()->make([
            'office_id' => $this->officeId,
            'account_number' => $this->accountId,
        ]);
        $this->paymentProfilesResourceMock = Mockery::mock(PaymentProfilesResource::class);
        $this->prepareDtoForCreditCard();
        $this->prepareDtoForAch();

        $modelMapper = new PestRoutesPaymentProfileToExternalModelMapper();
        $appointmentParametersFactory = new PaymentProfileParametersFactory();

        $this->pestRoutesPaymentProfileRepository = new PestRoutesPaymentProfileRepository(
            $modelMapper,
            $appointmentParametersFactory
        );
    }

    protected function prepareDtoForCreditCard(): void
    {
        $this->dtoForCreditCard = CreatePaymentProfileDTO::from([
            'customerId' => $this->accountId,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayCC,
            'token' => $this->token,
            'billingName' => 'John Doe',
            'billingAddressLine1' => 'Aptive Street',
            'billingAddressLine2' => 'Unit 105c',
            'billingCity' => 'Orlando',
            'billingState' => 'FL',
            'billingZip' => '32832',
            'auto_pay' => true,
        ]);
    }

    protected function prepareDtoForAch(): void
    {
        $this->dtoForAch = CreatePaymentProfileDTO::from([
            'customerId' => $this->accountId,
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH,
            'bankName' => 'Aptive Bank',
            'accountNumber' => '1234',
            'routingNumber' => '987567987',
            'checkType' => CheckType::PERSONAL,
            'accountType' => AccountType::CHECKING,
            'billingName' => 'John Doe',
            'billingAddressLine1' => 'Aptive Street',
            'billingAddressLine2' => 'Unit 105c',
            'billingCity' => 'Orlando',
            'billingState' => 'FL',
            'billingZip' => '32832',
            'auto_pay' => true,
        ]);
    }

    public function test_add_payment_profile_submits_credit_card_payment_profile(): void
    {
        $pestRoutesPaymentProfile = PaymentProfileData::getTestData(1, [
            'paymentProfileID' => $this->paymentProfileId,
        ])->first();

        $this->setUpPaymentProfileRepositoryToCreateAndReturnPaymentProfile($pestRoutesPaymentProfile);
        $this->pestRoutesPaymentProfileRepository->addPaymentProfile(
            $this->officeId,
            $this->dtoForCreditCard
        );
    }

    public function test_add_payment_profile_throws_payment_profile_is_empty_exception(): void
    {
        $pestRoutesPaymentProfile = PaymentProfileData::getTestData(1, [
            'paymentProfileID' => $this->paymentProfileId,
            'status' => 0,
        ])->first();

        $this->setUpPaymentProfileRepositoryToCreateAndReturnPaymentProfile($pestRoutesPaymentProfile);
        $this->expectException(PaymentProfileIsEmptyException::class);
        $this->pestRoutesPaymentProfileRepository->addPaymentProfile(
            $this->officeId,
            $this->dtoForCreditCard
        );
    }

    private function setUpPaymentProfileRepositoryToCreateAndReturnPaymentProfile(
        PaymentProfile $pestRoutesPaymentProfile
    ): void {
        $this->paymentProfilesResourceMock = Mockery::mock(PaymentProfilesResource::class);
        $this->paymentProfilesResourceMock
            ->expects('create')
            ->withArgs(fn (CreatePaymentProfilesParams $p) => $this->validateCreateCcPaymentProfileParameters($p))
            ->once()
            ->andReturn($this->paymentProfileId);

        $this->paymentProfilesResourceMock
            ->expects('find')
            ->once()
            ->withArgs([$this->paymentProfileId])
            ->andReturn($pestRoutesPaymentProfile);

        $pestRoutesClientMock = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->times(2)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles')
            ->willReturn($this->paymentProfilesResourceMock)
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClientMock);
    }

    private function validateCreateCcPaymentProfileParameters(CreatePaymentProfilesParams $params): bool
    {
        return $params->toArray() === [
            'customerID' => $this->accountId,
            'billingName' => 'John Doe',
            'billingAddress1' => 'Aptive Street',
            'billingAddress2' => 'Unit 105c',
            'billingCity' => 'Orlando',
            'billingState' => 'FL',
            'billingZip' => '32832',
            'paymentMethod' => 1,
            'gateway' => 'element',
            'merchantID' => 'ABC-123',
            'autopay' => '1',
            'officeID' => $this->officeId,
        ];
    }

    private function validateCreateAchPaymentProfileParameters(CreatePaymentProfilesParams $params): bool
    {
        return $params->toArray() === [
            'customerID' => $this->accountId,
            'billingName' => 'John Doe',
            'billingAddress1' => 'Aptive Street',
            'billingAddress2' => 'Unit 105c',
            'billingCity' => 'Orlando',
            'billingState' => 'FL',
            'billingZip' => '32832',
            'paymentMethod' => 2,
            'bankName' => 'Aptive Bank',
            'accountNumber' => '1234',
            'routingNumber' => '987567987',
            'checkType' => 0,
            'accountType' => 0,
            'autopay' => '1',
            'officeID' => $this->officeId,
        ];
    }

    private function validateCreateUpdateAchPaymentProfileParameters(UpdatePaymentProfilesParams $params): bool
    {
        return $params->toArray() === [
                'paymentProfileID' => $this->paymentProfileId,
                'billingFName' => 'John',
                'billingLName' => 'Doe',
                'officeID' => $this->officeId,
            ];
    }

    public function test_add_payment_profile_submits_ach_payment_profile(): void
    {
        $pestRoutesPaymentProfile = PaymentProfileData::getTestData(1, [
            'paymentProfileID' => $this->paymentProfileId,
        ])->first();

        $this->paymentProfilesResourceMock = Mockery::mock(PaymentProfilesResource::class);
        $this->paymentProfilesResourceMock
            ->expects('create')
            ->withArgs(fn (CreatePaymentProfilesParams $p) => $this->validateCreateAchPaymentProfileParameters($p))
            ->once()
            ->andReturn($this->paymentProfileId);
        $this->paymentProfilesResourceMock
            ->expects('find')
            ->twice()
            ->withArgs([$this->paymentProfileId])
            ->andReturn($pestRoutesPaymentProfile);
        $this->paymentProfilesResourceMock
            ->expects('update')
            ->withArgs(
                fn (UpdatePaymentProfilesParams $p) => $this->validateCreateUpdateAchPaymentProfileParameters($p)
            )
            ->once()
            ->andReturn(true);

        $pestroutesClientMock = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles')
            ->willReturn($this->paymentProfilesResourceMock)
            ->times(4)
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestroutesClientMock);

        $this->assertSame(
            $this->paymentProfileId,
            $this->pestRoutesPaymentProfileRepository->addPaymentProfile(
                $this->officeId,
                $this->dtoForAch
            )
        );
    }

    public function test_add_payment_profile_handles_payment_profile_create_failure(): void
    {
        $pestroutesClientMock = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles')
            ->willThrow(new Exception('Test'))
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestroutesClientMock);

        $this->expectException(AddCreditCardException::class);

        $this->pestRoutesPaymentProfileRepository->addPaymentProfile(
            $this->officeId,
            $this->dtoForCreditCard,
        );
    }

    public function test_add_payment_profile_handles_credit_card_authorization_exception(): void
    {
        $pestroutesClientMock = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles')
            ->willThrow(new Exception(self::ERROR_INVALID_CC))
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestroutesClientMock);

        $this->expectException(CreditCardAuthorizationException::class);

        $this->pestRoutesPaymentProfileRepository->addPaymentProfile(
            $this->officeId,
            $this->dtoForCreditCard,
        );
    }

    public function test_add_payment_profile_handles_payment_profile_update_failure(): void
    {
        $pestRoutesPaymentProfile = PaymentProfileData::getTestData(1, [
            'paymentProfileID' => $this->paymentProfileId,
        ])->first();

        $this->paymentProfilesResourceMock = Mockery::mock(PaymentProfilesResource::class);
        $this->paymentProfilesResourceMock
            ->expects('create')
            ->withArgs(fn (CreatePaymentProfilesParams $p) => $this->validateCreateAchPaymentProfileParameters($p))
            ->once()
            ->andReturn($this->paymentProfileId);
        $this->paymentProfilesResourceMock
            ->expects('find')
            ->once()
            ->withArgs([$this->paymentProfileId])
            ->andReturn($pestRoutesPaymentProfile);

        $this->paymentProfilesResourceMock
            ->expects('find')
            ->once()
            ->withArgs([$this->paymentProfileId])
            ->andThrow(new Exception('Test'));

        $pestroutesClientMock = $this
            ->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles')
            ->willReturn($this->paymentProfilesResourceMock)
            ->times(3)
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestroutesClientMock);

        $this->expectException(AddCreditCardException::class);

        $this->pestRoutesPaymentProfileRepository->addPaymentProfile(
            $this->officeId,
            $this->dtoForAch
        );
    }

    /**
     * @dataProvider provideUpdatePaymentProfileRequestData
     */
    public function test_update_payment_profile_updates_payment_profile(array $requestData, array $validParams): void
    {
        $requestData['paymentProfileID'] = $this->paymentProfileId;
        $validParams['paymentProfileID'] = $this->paymentProfileId;
        $validParams['officeID'] = $this->officeId;

        $this->givenPaymentProfilesResourceFindsProfile();
        $this->paymentProfilesResourceMock->expects('update')
            ->with(Mockery::capture($params))
            ->once()
            ->andReturn($this->paymentProfileId);
        $this->givenPaymentProfileRepositoryReturnsResourceTimes(2);

        $this->pestRoutesPaymentProfileRepository->updatePaymentProfile(
            $this->getUpdatePaymentProfileDTO($requestData)
        );
        $this->assertEquals($validParams, $params->toArray());
    }

    public function test_update_payment_profile_throws_exception_on_not_found_payment_profile(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new PaymentProfileNotFoundException())
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(PaymentProfileNotFoundException::class);

        $this->pestRoutesPaymentProfileRepository->updatePaymentProfile(
            $this->getUpdatePaymentProfileDTO()
        );
    }

    public function test_update_payment_profile_throws_exception_on_not_updated_payment_profile(): void
    {
        $this->givenPaymentProfilesResourceFindsProfile();
        $this->paymentProfilesResourceMock->expects('update')
            ->with(UpdatePaymentProfilesParams::class)
            ->once()
            ->andReturn(0);
        $this->givenPaymentProfileRepositoryReturnsResourceTimes(2);

        $this->expectException(PaymentProfileNotUpdatedException::class);

        $this->pestRoutesPaymentProfileRepository->updatePaymentProfile(
            $this->getUpdatePaymentProfileDTO()
        );
    }

    public function test_search_payment_profiles_fetches_payment_profiles(): void
    {
        $paymentProfiles = PaymentProfileData::getTestData();
        $paymentProfile = $paymentProfiles->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (SearchPaymentProfilesParams $searchParams): bool {
                $params = $searchParams->toArray();

                return $params['officeIDs'] === [$this->officeId]
                    && $params['customerIDs'] === [$this->accountId]
                    && $params['status'] === [PaymentProfileStatus::Valid, PaymentProfileStatus::LastTransactionFailed]
                    && $params['paymentMethod'] === [PaymentProfilePaymentMethod::AutoPayCC]
                    && $params['paymentProfileIDs'] === [$this->getTestPaymentProfileId()];
            })
            ->willReturn(new PestRoutesCollection($paymentProfiles->all()))
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClientMock);
        $dto = new SearchPaymentProfilesDTO(
            officeId: $this->officeId,
            accountNumbers: [$this->accountId],
            statuses: [StatusType::VALID, StatusType::FAILED],
            paymentMethods: [PaymentMethod::CREDIT_CARD],
            ids: [$this->getTestPaymentProfileId()]
        );

        /** @var Collection<int, PaymentProfileModel> $result */
        $result = $this->pestRoutesPaymentProfileRepository
            ->office($this->officeId)
            ->search($dto);

        $this->assertSame($paymentProfile->id, $result->first()->id);
    }

    public function test_find_fetches_payment_profile_by_id(): void
    {
        $paymentProfile = PaymentProfileData::getTestData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles', 'find')
            ->methodExpectsArgs('find', [$this->paymentProfileId])
            ->willReturn($paymentProfile)
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->pestRoutesPaymentProfileRepository
            ->office($this->officeId)
            ->find($this->paymentProfileId);

        $this->assertInstanceOf(PaymentProfileModel::class, $result);
        $this->assertSame($paymentProfile->id, $result->id);
    }

    public function test_delete_payment_profile_deletes_payment_profile(): void
    {
        $this->paymentProfileId = random_int(1999, 99999);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles', 'delete')
            ->methodExpectsArgs('delete', [$this->paymentProfileId])
            ->willReturn(true)
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClientMock);

        $this
            ->pestRoutesPaymentProfileRepository
            ->deletePaymentProfile($this->officeId, $this->paymentProfileId);
    }

    public function test_delete_payment_profile_throws_exception_when_delete_payment_profile_fails(): void
    {
        $this->paymentProfileId = random_int(1999, 99999);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles', 'delete')
            ->methodExpectsArgs('delete', [$this->paymentProfileId])
            ->willReturn(false)
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(PaymentProfileNotDeletedException::class);

        $this
            ->pestRoutesPaymentProfileRepository
            ->deletePaymentProfile($this->officeId, $this->paymentProfileId);
    }

    protected function getUpdatePaymentProfileDTO(array $requestData = []): UpdatePaymentProfileDTO
    {
        return new UpdatePaymentProfileDTO(
            $this->officeId,
            $this->paymentProfileId,
            $requestData['billingFName'] ?? null,
            $requestData['billingLName'] ?? null,
            $requestData['billingAddressLine1'] ?? null,
            $requestData['billingAddressLine2'] ?? null,
            $requestData['billingCity'] ?? 'Testing',
            $requestData['billingState'] ?? null,
            $requestData['billingZip'] ?? null,
            $requestData['billingCountryId'] ?? null,
            $requestData['expMonth'] ?? null,
            $requestData['expYear'] ?? null,
        );
    }

    public function provideUpdatePaymentProfileRequestData(): array
    {
        return [
            [
                'requestData' => [
                    'paymentProfileID' => null,
                    'billingFName' => 'Jane Testone',
                    'billingLName' => 'Doeone',
                    'expMonth' => 1,
                    'expYear' => 25,
                ],
                'validParams' => [
                    'paymentProfileID' => null,
                    'billingFName' => 'Jane Testone',
                    'billingLName' => 'Doeone',
                    'expMonth' => '01',
                    'expYear' => '25',
                    'billingCity' => 'Testing',
                ],
            ],
            [
                'requestData' => [
                    'paymentProfileID' => null,
                    'billingFName' => 'Jane Testtwo',
                    'billingLName' => 'Doetwo',
                ],
                'validParams' => [
                    'paymentProfileID' => null,
                    'billingFName' => 'Jane Testtwo',
                    'billingLName' => 'Doetwo',
                    'expMonth' => '12',
                    'expYear' => '24',
                    'billingCity' => 'Testing',
                ],
            ],
            [
                'requestData' => [
                    'paymentProfileID' => null,
                    'billingFName' => 'Jane Testthree',
                ],
                'validParams' => [
                    'paymentProfileID' => null,
                    'billingFName' => 'Jane Testthree',
                    'billingLName' => 'Smith',
                    'expMonth' => '12',
                    'expYear' => '24',
                    'billingCity' => 'Testing',
                ],
            ],
            [
                'requestData' => [
                    'paymentProfileID' => null,
                    'billingLName' => 'Testfour',
                    'expMonth' => 1,
                    'expYear' => 25,
                ],
                'validParams' => [
                    'paymentProfileID' => null,
                    'billingFName' => 'John Doe',
                    'billingLName' => 'Testfour',
                    'expMonth' => '01',
                    'expYear' => '25',
                    'billingCity' => 'Testing',
                ],
            ],
        ];
    }

    protected function getCreatePaymentProfileUrl(): string
    {
        return $this->apiUrl . 'create';
    }

    protected function givenHttpCreateReturnsResponse(array $response): void
    {
        Http::fake([
            $this->getCreatePaymentProfileUrl() . '*' => $response,
        ]);
    }

    protected function givenPaymentProfilesResourceFindsProfile(): void
    {
        $this->paymentProfilesResourceMock->expects('find')
            ->with($this->paymentProfileId)
            ->once()
            ->andReturn($this->getPestRoutesPaymentProfile(billingName: 'John Doe Smith'));
    }

    protected function givenPaymentProfileRepositoryReturnsResourceTimes(int $times): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->officeId)
            ->callSequense('paymentProfiles')
            ->willReturn($this->paymentProfilesResourceMock)
            ->times($times)
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClientMock);
    }

    public function test_search_by_customer_loads_payments(): void
    {
        $paymentProfiles = PaymentProfileData::getTestData(3);

        $pestRoutesClient = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (SearchPaymentProfilesParams $params): bool {
                $array = $params->toArray();

                return $array['customerIDs'] === [$this->getTestAccountNumber()]
                    && $array['officeIDs'] === [$this->getTestOfficeId()];
            })
            ->willReturn(new PestRoutesCollection($paymentProfiles->all()))
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($pestRoutesClient);

        $result = $this->pestRoutesPaymentProfileRepository
            ->office($this->getTestOfficeId())
            ->searchByCustomerId([$this->getTestAccountNumber()]);

        foreach ($result as $key => $payment) {
            self::assertEquals($paymentProfiles[$key]->id, $payment->id);
        }
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestPaymentProfileId(),
            $this->getTestPaymentProfileId() + 1,
        ];

        /** @var Collection<int, PaymentProfile> $paymentProfiles */
        $paymentProfiles = PaymentProfileData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(PaymentProfilesResource::class)
            ->callSequense('paymentProfiles', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchPaymentProfilesParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['paymentProfileIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesCollection($paymentProfiles->all()))
            ->mock();

        $this->pestRoutesPaymentProfileRepository->setPestRoutesClient($clientMock);

        $result = $this->pestRoutesPaymentProfileRepository
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($paymentProfiles->count(), $result);
    }
}
