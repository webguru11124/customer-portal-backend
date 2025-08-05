<?php

namespace Tests\Unit\Http\Requests;

use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Http\Requests\TransactionSetupCreateAchRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class TransactionSetupCreateAchRequestTest extends TestCase
{
    public TransactionSetupCreateAchRequest $transactionSetupCreateAchRequest;
    public array $validPayload = [
        'customer_id' => '234567',
        'billing_name' => 'John Joe',
        'billing_address_line_1' => 'Aptive Street',
        'billing_address_line_2' => 'Unit #456',
        'billing_city' => 'Orlando',
        'billing_state' => 'FL',
        'billing_zip' => '32832',
        'bank_name' => 'Test Bank',
        'account_number' => '2550260',
        'account_number_confirmation' => '2550260',
        'routing_number' => '1234567',
        'check_type' => 0,
        'account_type' => 0,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->transactionSetupCreateAchRequest = new TransactionSetupCreateAchRequest();
    }

    public function test_it_authorized_the_request(): void
    {
        $this->assertTrue($this->transactionSetupCreateAchRequest->authorize());
    }

    public function test_it_has_the_correct_rules(): void
    {
        $this->assertEquals(
            $this->transactionSetupCreateAchRequest->rules(),
            [
                'customer_id' => 'required|int|gt:0',
                'billing_name' => 'required|max:128',
                'billing_address_line_1' => 'required|max:128',
                'billing_address_line_2' => 'sometimes|max:128',
                'billing_city' => 'required|max:66',
                'billing_state' => 'required|size:2|alpha',
                'billing_zip' => 'required|size:5',
                'bank_name' => 'required|max:128',
                'account_number' => 'required|max:64',
                'account_number_confirmation' => 'required|max:64|same:account_number',
                'routing_number' => 'required|max:64',
                'check_type' => ['required', Rule::in([CheckType::BUSINESS->value, CheckType::PERSONAL->value])],
                'account_type' => ['nullable', Rule::in([AccountType::CHECKING->value, AccountType::SAVINGS->value])],
            ]
        );
    }

    /**
     * @dataProvider provideValidData
     */
    public function test_valid_data_passes_validation(array $payload): void
    {
        $validator = Validator::make($payload, $this->transactionSetupCreateAchRequest->rules());
        $this->assertTrue($validator->passes());
    }

    public function provideValidData(): array
    {
        $payloadWithoutAccountType = $this->validPayload;
        unset($payloadWithoutAccountType['account_type']);

        return [
            'with account' => [
                'payload' => $this->validPayload,
                ],
            'without account' => [
                'payload' => $payloadWithoutAccountType,
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidData
     */
    public function test_invalid_data_failed_validation(array $data, string $message): void
    {
        $validator = Validator::make($data, $this->transactionSetupCreateAchRequest->rules());
        $this->assertFalse($validator->passes(), $message);
    }

    public function provideInvalidData(): array
    {
        $ultraLongString = 'ultra long string 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890';

        $invalidData['no customer_id'] = [$this->validPayload, 'missing customer_id'];
        $invalidData['no billing_name'] = [$this->validPayload, 'missing billing_name'];
        $invalidData['no billing_address_line_1'] = [$this->validPayload, 'missing billing_address_line_1'];
        $invalidData['no billing_city'] = [$this->validPayload, 'missing billing_city'];
        $invalidData['no billing_state'] = [$this->validPayload, 'missing billing_state'];
        $invalidData['no billing_zip'] = [$this->validPayload, 'missing billing_zip'];
        $invalidData['no bank_name'] = [$this->validPayload, 'missing bank_name'];
        $invalidData['no account_number'] = [$this->validPayload, 'missing account_number'];
        $invalidData['no account_number_confirmation'] = [$this->validPayload, 'missing account_number_confirmation'];
        $invalidData['account_number_confirmation_mismatch'] = [$this->validPayload, 'account_number_confirmation does not match'];
        $invalidData['no routing_number'] = [$this->validPayload, 'missing routing_number'];
        $invalidData['no check_type'] = [$this->validPayload, 'missing check_type'];
        $invalidData['invalid customer_id'] = [$this->validPayload, 'invalid customer_id'];
        $invalidData['invalid billing_name'] = [$this->validPayload, 'invalid billing_name'];
        $invalidData['invalid billing_address_line_1'] = [$this->validPayload, 'invalid billing_address_line_1'];
        $invalidData['invalid billing_address_line_2'] = [$this->validPayload, 'invalid billing_address_line_2'];
        $invalidData['invalid billing_city'] = [$this->validPayload, 'invalid billing_city'];
        $invalidData['invalid billing_state'] = [$this->validPayload, 'invalid billing_state'];
        $invalidData['invalid billing_zip'] = [$this->validPayload, 'invalid billing_zip'];
        $invalidData['invalid bank_name'] = [$this->validPayload, 'invalid bank_name'];
        $invalidData['invalid account_number'] = [$this->validPayload, 'invalid account_number'];
        $invalidData['invalid routing_number'] = [$this->validPayload, 'invalid routing_number'];
        $invalidData['invalid check_type'] = [$this->validPayload, 'invalid check_type'];
        $invalidData['invalid account_type'] = [$this->validPayload, 'invalid account_type'];

        unset($invalidData['no customer_id'][0]['customer_id']);
        unset($invalidData['no billing_name'][0]['billing_name']);
        unset($invalidData['no billing_address_line_1'][0]['billing_address_line_1']);
        unset($invalidData['no billing_city'][0]['billing_city']);
        unset($invalidData['no billing_state'][0]['billing_state']);
        unset($invalidData['no billing_zip'][0]['billing_zip']);
        unset($invalidData['no bank_name'][0]['bank_name']);
        unset($invalidData['no account_number'][0]['account_number']);
        unset($invalidData['no account_number_confirmation'][0]['account_number_confirmation']);
        $invalidData['account_number_confirmation_mismatch'][0]['account_number_confirmation'] = '99223313';
        unset($invalidData['no routing_number'][0]['routing_number']);
        unset($invalidData['no check_type'][0]['check_type']);
        $invalidData['invalid customer_id'][0]['customer_id'] = $ultraLongString;
        $invalidData['invalid billing_name'][0]['billing_name'] = $ultraLongString;
        $invalidData['invalid billing_address_line_1'][0]['billing_address_line_1'] = $ultraLongString;
        $invalidData['invalid billing_address_line_2'][0]['billing_address_line_2'] = $ultraLongString;
        $invalidData['invalid billing_city'][0]['billing_city'] = $ultraLongString;
        $invalidData['invalid billing_state'][0]['billing_state'] = 'TEST';
        $invalidData['invalid billing_zip'][0]['billing_zip'] = '1';
        $invalidData['invalid bank_name'][0]['bank_name'] = $ultraLongString;
        $invalidData['invalid account_number'][0]['account_number'] = $ultraLongString;
        $invalidData['invalid routing_number'][0]['routing_number'] = $ultraLongString;
        $invalidData['invalid check_type'][0]['check_type'] = 9;
        $invalidData['invalid account_type'][0]['account_type'] = 9;

        return $invalidData;
    }
}
