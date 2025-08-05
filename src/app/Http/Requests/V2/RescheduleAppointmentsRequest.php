<?php

namespace App\Http\Requests\V2;

use App\Enums\FlexIVR\Window;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RescheduleAppointmentsRequest extends FormRequest
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
            'window' => ['required', new Enum(Window::class)],
            'spot_id' => 'required|int|gt:0',
            'is_aro_spot' => 'required|bool',
            'notes' => 'nullable|string|min:3',
        ];
    }
}
