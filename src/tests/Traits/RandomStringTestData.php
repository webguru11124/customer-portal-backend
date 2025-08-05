<?php

namespace Tests\Traits;

use Illuminate\Support\Str;

trait RandomStringTestData
{
    protected array $randomStringTestData = [];

    public function getTestCrmAccountUuid(): string
    {
        return $this->addRandomStringToFunctionCall(__FUNCTION__);
    }

    public function getTestPaymentMethodUuid(): string
    {
        return $this->addRandomStringToFunctionCall(__FUNCTION__);
    }

    public function getTestTransactionUuid(): string
    {
        return $this->addRandomStringToFunctionCall(__FUNCTION__);
    }

    private function addRandomStringToFunctionCall(string $functionName): string
    {
        if (!empty($this->randomStringTestData[$functionName])) {
            return $this->randomStringTestData[$functionName];
        }

        return $this->randomStringTestData[$functionName] = (string) Str::uuid();
    }
}
