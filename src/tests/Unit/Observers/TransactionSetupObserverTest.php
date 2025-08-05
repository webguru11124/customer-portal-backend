<?php

namespace Tests\Unit\Observers;

use App\Enums\Models\TransactionSetupStatus;
use App\Models\TransactionSetup;
use App\Services\TransactionSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSetupObserverTest extends TestCase
{
    use RefreshDatabase;
    protected $mockTransactionSetupService;
    protected $slug;

    public function setUp(): void
    {
        parent::setUp();
        $this->slug = 'slug12';
        $this->mockTransactionSetupService = $this->mock(TransactionSetupService::class);
    }

    public function test_creates_a_slug()
    {
        $this->mockTransactionSetupService->shouldReceive('createUniqueSlug')
                ->with()
                ->once()
                ->andReturn($this->slug);

        $accountNumber = 123456;
        TransactionSetup::create([
            'status' => TransactionSetupStatus::INITIATED,
            'account_number' => $accountNumber,
        ]);

        $transactionSetup = TransactionSetup::where('account_number', $accountNumber)->first();

        $this->assertNotEmpty($transactionSetup->slug);
    }
}
