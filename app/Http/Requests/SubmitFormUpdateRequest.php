<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFormUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'values' => ['sometimes', 'array'],
            'preferences' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'signature' => ['sometimes', 'string'],
            'completed' => ['sometimes', 'boolean'],
            'completed_at' => ['sometimes', 'date'],
        ];
    }
}
