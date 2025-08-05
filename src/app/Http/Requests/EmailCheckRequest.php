<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\FusionAuth\FusionAuthJwtGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $email
 * @property string $auth
 */
class EmailCheckRequest extends FormRequest
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
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'auth' => [
                'nullable',
                Rule::in(['Auth0', FusionAuthJwtGuard::TYPE]),
            ],
        ];
    }
}
