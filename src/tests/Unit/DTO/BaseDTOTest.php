<?php

namespace Tests\Unit\DTO;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BaseDTOTest extends TestCase
{
    public function test_its_validates_failed_data()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The age field is required.');

        $dto = new class (firstName: 'John', lastName: 'Doe', age: null) extends BaseDTO {
            public function __construct(public string $firstName, public string $lastName, public ?int $age)
            {
                $this->validateData();
            }

            public function getRules(): array
            {
                return [
                    'age' => 'required',
                ];
            }
        };
    }

    public function test_its_has_default_validation_values()
    {
        $dto = new class () extends BaseDTO {
            public function __construct()
            {
            }

            public function getRules(): array
            {
                return parent::getRules();
            }
        };

        $this->assertEquals([], $dto->getRules());
    }
}
