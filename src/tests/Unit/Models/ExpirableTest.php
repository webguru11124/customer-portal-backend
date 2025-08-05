<?php

namespace Tests\Unit\Models;

use App\Enums\Models\TransactionSetupStatus;
use App\Models\Expirable;
use Carbon\Carbon;
use Tests\TestCase;

class ExpirableTest extends TestCase
{
    /**
     * @dataProvider provideExpirableData
     */
    public function test_isExpired_returns_valid_result($expirableData, $result)
    {
        $expirable = new Expirable($expirableData);

        $this->assertEquals($result, $expirable->isExpired());
    }

    public function provideExpirableData()
    {
        return [
            [
                [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' => TransactionSetupStatus::INITIATED,
                ],
                false,
            ],
            [
                [
                    'created_at' => Carbon::createMidnightDate(2022, 1, 1),
                    'updated_at' => Carbon::createMidnightDate(2022, 1, 1),
                    'status' => TransactionSetupStatus::INITIATED,
                ],
                true,
            ],
        ];
    }
}
