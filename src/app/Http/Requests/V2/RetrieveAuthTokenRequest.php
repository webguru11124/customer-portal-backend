<?php

namespace App\Http\Requests\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RetrieveAuthTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string|array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'origin' => 'required|string',
            'timestamp' => 'required|int|gt:0',
        ];
    }
}
