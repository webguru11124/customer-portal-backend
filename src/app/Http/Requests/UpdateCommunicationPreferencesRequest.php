<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCommunicationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string|array<int, string|Rule>>
     */
    public function rules()
    {
        return [
            'smsReminders' => 'required|boolean',
            'emailReminders' => 'required|boolean',
            'phoneReminders' => 'required|boolean',
        ];
    }
}
