<?php

namespace Tests\Unit\Enums\Models;

use App\Enums\Models\TransactionSetupStatus;
use Tests\TestCase;

class TransactionSetupStatusTest extends TestCase
{
    public function test_enum_is_initiated()
    {
        $enum = TransactionSetupStatus::INITIATED;

        $this->assertTrue($enum->isInitiated());
    }

    public function test_enum_is_generated()
    {
        $enum = TransactionSetupStatus::GENERATED;

        $this->assertTrue($enum->isGenerated());
    }

    public function test_enum_is_complete()
    {
        $enum = TransactionSetupStatus::COMPLETE;

        $this->assertTrue($enum->isComplete());
    }

    public function test_enum_is_failed_authorization()
    {
        $enum = TransactionSetupStatus::FAILED_AUTHORIZATION;

        $this->assertTrue($enum->isFailedAuthorization());
    }
}
