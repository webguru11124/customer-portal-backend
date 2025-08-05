<?php

namespace Tests\Unit\Models;

use App\Enums\Models\TransactionSetupStatus;
use App\Models\TransactionSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setStatusExpired_sets_status(): void
    {
        $transactionSetup = TransactionSetup::factory()->initiated()->make();

        $transactionSetup->setStatusExpired();

        $this->assertEquals(TransactionSetupStatus::EXPIRED, $transactionSetup->status);
    }

    public function test_complete_sets_correct_status(): void
    {
        $transactionSetup = TransactionSetup::factory()->initiated()->make();

        $transactionSetup->complete();

        $this->assertEquals(TransactionSetupStatus::COMPLETE, $transactionSetup->status);
    }
}
