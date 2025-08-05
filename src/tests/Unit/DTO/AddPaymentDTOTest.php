<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\AddPaymentDTO;
use App\Enums\Models\Payment\PaymentMethod;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class AddPaymentDTOTest extends TestCase
{
    public AddPaymentDTO $dto;

    public function setUp(): void
    {
        parent::setUp();
        $this->dto = AddPaymentDTO::from([
            'customerId' => '2550260',
            'paymentProfileId' => '220704',
            'amountCents' => '12995',
            'paymentMethod' => PaymentMethod::CREDIT_CARD,
        ]);
    }

    public function test_it_has_validation_rules(): void
    {
        $validRules = [
            'customerId' => ['gt:0'],
            'paymentProfileId' => ['gt:0'],
            'amountCents' => ['gt:0'],
            'paymentMethod' => Rule::in([PaymentMethod::CREDIT_CARD->value, PaymentMethod::ACH->value]),
        ];
        $this->assertEquals($this->dto->getRules(), $validRules);
    }

    public function test_it_returns_valid_amount(): void
    {
        $this->assertSame(129.95, $this->dto->getAmount());
    }
}
