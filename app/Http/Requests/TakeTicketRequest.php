<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TakeTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'student_nim' => ['required', 'string', 'max:30'],
        ];
    }
}
