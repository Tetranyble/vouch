<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartFormUpdateRequest extends FormRequest
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
            'email' => ['sometimes', 'email'],
            'name' => ['sometimes', 'string'],
        ];
    }
}
