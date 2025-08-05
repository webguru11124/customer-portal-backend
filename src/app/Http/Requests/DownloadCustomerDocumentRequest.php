<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Resources;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class DownloadCustomerDocumentRequest extends FormRequest
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
     * @return array<string, array<int, string|In>>
     */
    public function rules(): array
    {
        return [
            'documentType' => [
                'required',
                Rule::in([
                    Resources::DOCUMENT->value,
                    Resources::CONTRACT->value,
                    Resources::FORM->value,
                ]),
            ],
        ];
    }
}
