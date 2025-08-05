<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Helpers\DateTimeHelper;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SearchAppointmentsRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dateFormat = DateTimeHelper::defaultDateFormat();

        return [
            'date_start' => sprintf('nullable|date_format:%s', $dateFormat),
            'date_end' => sprintf('nullable|date_format:%s|after_or_equal:date_start', $dateFormat),
            'status' => 'nullable|array',
            'status.*' => new Enum(AppointmentStatus::class),
        ];
    }
}
