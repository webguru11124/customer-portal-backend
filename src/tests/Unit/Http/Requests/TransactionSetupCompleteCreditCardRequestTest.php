<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TransactionSetupCompleteCreditCardRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TransactionSetupCompleteCreditCardRequestTest extends TestCase
{
    public $transactionSetupRequest;
    public $ultraLongString = 'ultra long string 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890';
    public $validPayload = [
        'HostedPaymentStatus' => 'Error',
        'ValidationCode' => '89F5694DC8814A73',
        'PaymentAccountID' => 'TST-128ASDF',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->transactionSetupRequest = new TransactionSetupCompleteCreditCardRequest();
    }

    public function test_it_authorized_the_request()
    {
        $this->assertTrue($this->transactionSetupRequest->authorize());
    }

    public function test_it_has_the_correct_rules()
    {
        $this->assertEquals(
            $this->transactionSetupRequest->rules(),
            [
                'HostedPaymentStatus' => 'required|max:128',
                'ValidationCode' => 'required|max:128',
                'PaymentAccountID' => 'nullable|max:128',
            ]
        );
    }

    public function test_valid_request_passes_validation()
    {
        $validator = Validator::make($this->validPayload, $this->transactionSetupRequest->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider provideInvalidData
     */
    public function test_invalid_request_fails_validation(array $data, string $message)
    {
        $validator = Validator::make($data, $this->transactionSetupRequest->rules());
        $this->assertFalse($validator->passes(), $message);
    }

    public function provideInvalidData()
    {
        $invalidData = [
            [[$this->validPayload], 'missing HostedPaymentStatus'],
            [[$this->validPayload], 'missing ValidationCode'],
            [[$this->validPayload], 'invalid HostedPaymentStatus'],
            [[$this->validPayload], 'invalid ValidationCode'],
        ];
        unset($invalidData[0][0]['HostedPaymentStatus']);
        unset($invalidData[1][0]['ValidationCode']);
        $invalidData[2][0]['HostedPaymentStatus'] = $this->ultraLongString;
        $invalidData[3][0]['ValidationCode'] = $this->ultraLongString;

        return $invalidData;
    }
}
