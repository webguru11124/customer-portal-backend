<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TransactionSetupCreateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TransactionSetupCreateRequestTest extends TestCase
{
    public $transactionSetupCreateRequest;
    public $validPayload = [
        'accountId' => 2550260,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->transactionSetupCreateRequest = new TransactionSetupCreateRequest();
    }

    public function test_it_authorized_the_request()
    {
        $this->assertTrue($this->transactionSetupCreateRequest->authorize());
    }

    public function test_it_has_the_correct_rules()
    {
        $this->assertEquals($this->transactionSetupCreateRequest->rules(), ['accountId' => 'required|gt:0']);
    }

    public function test_valid_data_passes_validation()
    {
        $validator = Validator::make($this->validPayload, $this->transactionSetupCreateRequest->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider provideInvalidData
     */
    public function test_invalid_data_failed_validation(array $data, string $message)
    {
        $validator = Validator::make($data, $this->transactionSetupCreateRequest->rules());
        $this->assertFalse($validator->passes(), $message);
    }

    public function provideInvalidData()
    {
        return [
            [
                'data' => [
                ],
                'message' => 'empty request',
            ],
            [
                'data' => [
                    'accountId' => null,
                ],
                'message' => 'accountId is null',
            ],
            [
                'data' => [
                    'accountId' => 'Test',
                ],
                'message' => 'accountId is string',
            ],
        ];
    }
}
