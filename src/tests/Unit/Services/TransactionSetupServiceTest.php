<?php

namespace Tests\Unit\Services;

use App\DTO\CreateTransactionSetupDTO;
use App\DTO\InitiateTransactionSetupDTO;
use App\Enums\Models\TransactionSetupStatus;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\TransactionSetup;
use App\Services\AccountService;
use App\Services\TransactionSetupService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class TransactionSetupServiceTest extends TestCase
{
    use RandomIntTestData;
    use RefreshDatabase;

    public MockInterface|AccountService $mockAccountService;
    public MockInterface|TransactionSetupRepository $mockTransactionSetupRepositoryInterface;
    public TransactionSetupService $transactionSetupService;
    public int $accountNumber = 123456;
    public CreateTransactionSetupDTO $dto;
    public string $slug = 'av23bs';
    public string $transactionSetupId;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTransactionSetupId();
        $this->setupMockRepositories();
        $this->setupTransactionSetupService();
        $this->setupTransactionSetupDTO();
    }

    protected function setupTransactionSetupId(): void
    {
        $this->transactionSetupId = Str::random(24);
    }

    protected function setupMockRepositories(): void
    {
        $this->mockAccountService = $this->mock(AccountService::class);
        $this->mockTransactionSetupRepositoryInterface = $this->mock(TransactionSetupRepository::class);
    }

    protected function setupTransactionSetupService(): void
    {
        $this->transactionSetupService = new TransactionSetupService(
            $this->mockAccountService,
            $this->mockTransactionSetupRepositoryInterface
        );
    }

    protected function setupTransactionSetupDTO(): void
    {
        $this->dto = new CreateTransactionSetupDTO(
            slug: $this->slug,
            officeId: $this->getTestOfficeId(),
            email: 'test@email.com',
            phone_number: 1342452445,
            billing_name: 'John Doe',
            billing_address_line_1: 'Aptive Street',
            billing_address_line_2: 'Unit 105c',
            billing_city: 'Orlando',
            billing_state: 'FL',
            billing_zip: '32832',
            auto_pay: null,
        );
    }

    public function test_it_initiate_a_new_transaction_setup(): void
    {
        $initiateTransactionSetupDTO = new InitiateTransactionSetupDTO(
            accountNumber: $this->accountNumber,
            email: $this->dto->email,
            phoneNumber: $this->dto->phone_number,
        );

        $transactionSetup = $this->transactionSetupService->initiate($initiateTransactionSetupDTO);

        $this->assertDatabaseHas('transaction_setups', [
            'slug' => $transactionSetup->slug,
            'account_number' => $this->accountNumber,
            'status' => TransactionSetupStatus::INITIATED,
        ]);
    }

    public function test_it_returns_a_transaction_setup_by_transaction_setup_id(): void
    {
        $transactionSetup = TransactionSetup::factory()->initiated()->create();

        $transactionSetupFromSlug = $this->transactionSetupService->findByTransactionSetupId($transactionSetup->transaction_setup_id);
        $this->assertEquals($transactionSetupFromSlug->transaction_setup_id, $transactionSetup->transaction_setup_id);
        $this->assertEquals($transactionSetupFromSlug->slug, $transactionSetup->slug);
        $this->assertEquals($transactionSetupFromSlug->account_number, $transactionSetup->account_number);
    }

    public function test_it_does_not_return_a_completed_transaction_setup_by_transaction_setup_id(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $transactionSetup = TransactionSetup::factory()->initiated()->completed()->create();

        $this->transactionSetupService->findByTransactionSetupId($transactionSetup->transaction_setup_id);
    }

    public function test_it_finds_transaction_setup_generated_by_account_number_and_setup_id(): void
    {
        $this->transactionSetupId = Str::uuid()->toString();
        TransactionSetup::factory()
            ->generated()
            ->create([
                'account_number' => $this->getTestAccountNumber(),
                'transaction_setup_id' => $this->transactionSetupId,
            ]);

        /* @var TransactionSetup $transactionSetup */
        $transactionSetup = $this->transactionSetupService->findGeneratedByAccountNumberAndSetupId(
            $this->getTestAccountNumber(),
            $this->transactionSetupId
        );

        $this->assertSame($this->getTestAccountNumber(), $transactionSetup->account_number);
        $this->assertSame($this->transactionSetupId, $transactionSetup->transaction_setup_id);
        $this->assertSame(TransactionSetupStatus::GENERATED, $transactionSetup->status);
    }

    public function test_it_throws_exception_when_generated_transaction_setup_not_found_by_account_number_and_setup_id(): void
    {
        $this->accountNumber = $this->getTestAccountNumber();
        $this->transactionSetupId = Str::uuid()->toString();
        $status = TransactionSetupStatus::GENERATED;

        TransactionSetup::factory()->createMany([
            [
                'account_number' => $this->accountNumber + 1,
                'transaction_setup_id' => $this->transactionSetupId,
                'status' => $status,
            ],
            [
                'account_number' => $this->accountNumber,
                'transaction_setup_id' => Str::uuid()->toString(),
                'status' => $status,
            ],
            [
                'account_number' => $this->accountNumber,
                'transaction_setup_id' => $this->transactionSetupId,
                'status' => TransactionSetupStatus::INITIATED,
            ],
        ]);

        $this->expectException(ModelNotFoundException::class);
        $this->transactionSetupService->findGeneratedByAccountNumberAndSetupId(
            $this->accountNumber,
            $this->transactionSetupId
        );
    }

    public function test_it_returns_a_transaction_setup_by_slug(): void
    {
        $transactionSetup = TransactionSetup::factory()->create();

        $transactionSetupFromSlug = $this->transactionSetupService->findBySlug($transactionSetup->slug);
        $this->assertEquals($transactionSetupFromSlug->slug, $transactionSetup->slug);
        $this->assertEquals($transactionSetupFromSlug->account_number, $transactionSetup->account_number);
    }

    public function test_it_does_not_return_a_completed_transaction_setup_by_slug(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $transactionSetup = TransactionSetup::factory()->completed()->create();

        $this->transactionSetupService->findBySlug($transactionSetup->slug);
    }

    public function test_it_completes_a_transaction_setup(): void
    {
        $transactionSetup = TransactionSetup::factory()->initiated()->create();

        $this->transactionSetupService->complete($transactionSetup);

        $transactionSetup->refresh();

        $this->assertEquals($transactionSetup->status, TransactionSetupStatus::COMPLETE);
    }

    public function test_it_failed_authorization_a_transaction_setup(): void
    {
        $transactionSetup = TransactionSetup::factory()->initiated()->create();

        $this->transactionSetupService->failAuthorization($transactionSetup);

        $transactionSetup->refresh();

        $this->assertEquals($transactionSetup->status, TransactionSetupStatus::FAILED_AUTHORIZATION);
    }

    public function test_it_creates_a_slug(): void
    {
        $slug = $this->transactionSetupService->createSlug();

        $this->assertEquals(6, strlen($slug));
    }

    public function test_it_handles_duplicated_slug(): void
    {
        $accountNumber = 123456;
        $anotherSlug = 'slug98';
        TransactionSetup::make([
            'slug' => $this->slug,
            'status' => TransactionSetupStatus::INITIATED,
            'account_number' => $accountNumber,
        ])->saveQuietly();

        $mockTransactionSetupService = Mockery::mock(TransactionSetupService::class, [
            $this->mockAccountService,
            $this->mockTransactionSetupRepositoryInterface,
        ])->makePartial();

        $mockTransactionSetupService->shouldReceive('createSlug')->once()->andReturn($this->slug);
        $mockTransactionSetupService->shouldReceive('createSlug')->once()->andReturn($anotherSlug);

        $createdSlug = $mockTransactionSetupService->createUniqueSlug();

        $this->assertEquals($anotherSlug, $createdSlug);
    }

    public function test_it_returns_true_when_transaction_setup_is_complete(): void
    {
        $transactionSetup = TransactionSetup::factory()->initiated()->create();

        $this->assertFalse($this->transactionSetupService->transactionSetupIdIsComplete(
            $transactionSetup->account_number,
            $transactionSetup->transaction_setup_id
        ));

        $transactionSetup->status = TransactionSetupStatus::COMPLETE;
        $transactionSetup->save();

        $this->assertTrue($this->transactionSetupService->transactionSetupIdIsComplete(
            $transactionSetup->account_number,
            $transactionSetup->transaction_setup_id
        ));
    }
}
