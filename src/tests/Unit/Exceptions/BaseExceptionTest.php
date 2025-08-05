<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BaseException;
use Tests\TestCase;

class BaseExceptionTest extends TestCase
{
    public function test_it_returns_the_correct_custom_message()
    {
        $exceptions = new BaseException();

        $this->assertEquals($exceptions->getCustomerMessage(), config('app.default_error_message'));
    }
}
