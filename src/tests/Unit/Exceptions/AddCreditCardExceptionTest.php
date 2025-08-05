<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\PaymentProfile\AddCreditCardException;
use ReflectionClass;
use Tests\TestCase;

class AddCreditCardExceptionTest extends TestCase
{
    public function test_its_externds_base_exception_class()
    {
        $class = new ReflectionClass(AddCreditCardException::class);
        $this->assertTrue($class->isSubclassOf(BaseException::class));
    }
}
