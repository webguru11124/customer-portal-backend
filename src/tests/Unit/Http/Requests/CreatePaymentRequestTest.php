<?php

namespace Tests\Unit\Http\Requests;

use App\Enums\Models\Payment\PaymentMethod;
use App\Http\Requests\CreatePaymentRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class CreatePaymentRequestTest extends TestCase
{
    public $paymentRequest;
    public $validPayload = [
        'payment_profile_id' => 4566364,
        'amount_cents' => 1234,
        'payment_method' => 'CC',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->paymentRequest = new CreatePaymentRequest();
    }

    public function test_it_authorized_the_request()
    {
        $this->assertTrue($this->paymentRequest->authorize());
    }

    public function test_it_has_the_correct_rules()
    {
        $this->assertEquals(
            $this->paymentRequest->rules(),
            [
                'payment_profile_id' => 'required|gt:0',
                'amount_cents' => 'required|gt:0',
                'payment_method' => ['required', Rule::in([PaymentMethod::CREDIT_CARD->value, PaymentMethod::ACH->value])],
            ]
        );
    }

    public function test_valid_data_passes_validation()
    {
        $validator = Validator::make($this->validPayload, $this->paymentRequest->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider provideInvalidData
     */
    public function test_invalid_data_failed_validation(array $data, string $message)
    {
        $validator = Validator::make($data, $this->paymentRequest->rules());
        $this->assertFalse($validator->passes(), $message);
    }

    public function provideInvalidData()
    {
        $invalidData[] = [[$this->validPayload], 'missing payment_profile_id'];
        $invalidData[] = [[$this->validPayload], 'missing amount_cents'];
        $invalidData[] = [[$this->validPayload], 'missing payment_method'];
        $invalidData[] = [[$this->validPayload], 'invalid payment_profile_id'];
        $invalidData[] = [[$this->validPayload], 'invalid amount_cents'];
        $invalidData[] = [[$this->validPayload], 'invalid payment_method'];

        unset($invalidData[0][0]['payment_profile_id']);
        unset($invalidData[1][0]['amount_cents']);
        unset($invalidData[2][0]['payment_method']);
        $invalidData[3][0]['payment_profile_id'] = 0;
        $invalidData[4][0]['amount_cents'] = 0;
        $invalidData[5][0]['payment_method'] = 'CASH';

        return $invalidData;
    }
}
