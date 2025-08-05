<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TransactionSetupCreateCreditCardRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TransactionSetupCreateCreditCardRequestTest extends TestCase
{
    public $transactionSetupCreateCreditCardRequest;
    public $validPayload = [
        'billing_name' => 'John Joe',
        'billing_address_line_1' => 'Aptive Street',
        'billing_address_line_2' => 'Unit #456',
        'billing_city' => 'Orlando',
        'billing_state' => 'FL',
        'billing_zip' => '32832',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->transactionSetupCreateCreditCardRequest = new TransactionSetupCreateCreditCardRequest();
    }

    public function test_it_authorized_the_request()
    {
        $this->assertTrue($this->transactionSetupCreateCreditCardRequest->authorize());
    }

    public function test_it_has_the_correct_rules()
    {
        $this->assertEquals(
            $this->transactionSetupCreateCreditCardRequest->rules(),
            [
                'billing_name' => 'required|max:100',
                'billing_address_line_1' => 'required|max:50',
                'billing_address_line_2' => 'sometimes|max:50',
                'billing_city' => 'required|max:40',
                'billing_state' => 'required|size:2|alpha',
                'billing_zip' => 'required|size:5',
            ]
        );
    }

    public function test_valid_data_passes_validation()
    {
        $validator = Validator::make($this->validPayload, $this->transactionSetupCreateCreditCardRequest->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider provideInvalidData
     */
    public function test_invalid_data_failed_validation(array $data, string $message)
    {
        $validator = Validator::make($data, $this->transactionSetupCreateCreditCardRequest->rules());
        $this->assertFalse($validator->passes(), $message);
    }

    public function provideInvalidData()
    {
        $ultraLongString = 'ultra long string 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890';

        $invalidData[] = [[$this->validPayload], 'missing billing_name'];
        $invalidData[] = [[$this->validPayload], 'missing billing_address_line_1'];
        $invalidData[] = [[$this->validPayload], 'missing billing_city'];
        $invalidData[] = [[$this->validPayload], 'missing billing_state'];
        $invalidData[] = [[$this->validPayload], 'missing billing_zip'];
        $invalidData[] = [[$this->validPayload], 'invalid billing_name'];
        $invalidData[] = [[$this->validPayload], 'invalid billing_address_line_1'];
        $invalidData[] = [[$this->validPayload], 'invalid billing_address_line_2'];
        $invalidData[] = [[$this->validPayload], 'invalid billing_city'];
        $invalidData[] = [[$this->validPayload], 'invalid billing_state'];
        $invalidData[] = [[$this->validPayload], 'invalid billing_zip'];

        unset($invalidData[0][0]['billing_name']);
        unset($invalidData[1][0]['billing_address_line_1']);
        unset($invalidData[2][0]['billing_city']);
        unset($invalidData[3][0]['billing_state']);
        unset($invalidData[4][0]['billing_zip']);
        $invalidData[5][0]['billing_name'] = $ultraLongString;
        $invalidData[6][0]['billing_address_line_1'] = $ultraLongString;
        $invalidData[7][0]['billing_address_line_2'] = $ultraLongString;
        $invalidData[8][0]['billing_city'] = $ultraLongString;
        $invalidData[9][0]['billing_state'] = 'TEST';
        $invalidData[10][0]['billing_zip'] = '1';

        return $invalidData;
    }
}
