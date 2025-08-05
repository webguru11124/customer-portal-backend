<?php

namespace App\DTO;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Data;

/**
 * Base DTO Class.
 */
class BaseDTO extends Data
{
    /**
     * Validate data based on rules.
     *
     * @return void
     * @throws ValidationException
     */
    protected function validateData(): void
    {
        $validator = Validator::make($this->toArray(), $this->getRules());
        $validator->validate();
    }

    /**
     * Get validation rules.
     *
     * @return array<string, string|Rule|array<int, string|Rule>>
     */
    public function getRules(): array
    {
        return [];
    }
}
