<?php

namespace App\Http\Requests;

use App\Helpers\DateTimeHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchSpotsRequest extends FormRequest
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
        $dateFormat = DateTimeHelper::defaultDateFormat();

        return [
            'date_start' => sprintf('required|date_format:%s', $dateFormat),
            'date_end' => sprintf('required|date_format:%s|after_or_equal:date_start', $dateFormat),
        ];
    }
}
