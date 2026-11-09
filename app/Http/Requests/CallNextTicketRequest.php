<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallNextTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'counter_id' => ['required', 'integer', 'exists:counters,id'],
            'officer_name' => ['required', 'string', 'max:120'],
        ];
    }
}
