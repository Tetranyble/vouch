<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TemplateBuilderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schema' => ['sometimes', 'array'],
            'fields' => ['sometimes', 'array'],
            'submitters' => ['sometimes', 'array'],
            'preferences' => ['sometimes', 'array'],
            'variables_schema' => ['sometimes', 'array'],
        ];
    }
}
