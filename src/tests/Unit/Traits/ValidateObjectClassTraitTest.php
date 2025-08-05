<?php

namespace Tests\Unit\Traits;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Traits\ValidateObjectClass;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Tests\TestCase;

class ValidateObjectClassTraitTest extends TestCase
{
    public $testedClass;
    public $object;

    public function setUp(): void
    {
        parent::setUp();

        $this->testedClass = new class () {
            use ValidateObjectClass;

            public function validationPass(object $object, string $expectedClass): bool
            {
                try {
                    $this->validateObjectClass($object, $expectedClass);
                } catch (ValidationException $exception) {
                    return false;
                }

                return true;
            }
        };

        $this->object = SearchAppointmentsDTO::from([
            'officeId' => 1,
            'accountNumber' => [2222],
            'dateStart' => '2022-08-10',
            'dateEnd' => '2022-09-01',
        ]);
    }

    public function test_it_passes_validation()
    {
        $result = $this->testedClass->validationPass($this->object, SearchAppointmentsDTO::class);

        self::assertTrue($result);
    }

    public function test_validation_fails()
    {
        $result = $this->testedClass->validationPass($this->object, 'FakeClassName');

        self::assertFalse($result);
    }
}
