<?php

namespace Tests\Unit\DTO;

use App\DTO\AddCreditCardDTO;
use ReflectionClass;
use Spatie\LaravelData\Data;
use Tests\TestCase;

class AddCreditCardDTOTest extends TestCase
{
    public function test_its_extends_base_DTO()
    {
        $class = new ReflectionClass(AddCreditCardDTO::class);
        $this->assertTrue($class->isSubclassOf(Data::class));
    }

    public function test_its_set_up_validation()
    {
        $dto = AddCreditCardDTO::from([
            'credit_card_number' => '12132312',
            'expiration_month' => '12313112',
            'expiration_year' => '12313112',
        ]);

        $this->assertEquals($dto->getRules(), []);
    }
}
