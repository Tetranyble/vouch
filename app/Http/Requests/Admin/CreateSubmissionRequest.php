<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submitters' => 'nullable|array',
            'submitters.*.email' => 'nullable|email',
            'submitters.*.name' => 'nullable|string|max:255',
            'submitters.*.phone' => 'nullable|string|max:30',
            'submitters.*.values' => 'nullable|array',
            'preserve_order' => 'nullable|boolean',
            'expire_at' => 'nullable|date|after:now',
        ];
    }

    public function allSubmitterInputs(): array
    {
        return collect($this->input('submitters', []))
            ->map(fn ($s) => array_filter([
                'name' => $s['name'] ?? null,
                'email' => $s['email'] ?? null,
                'phone' => $s['phone'] ?? null,
                'values' => $s['values'] ?? null,
            ]))
            ->toArray();
    }

    public function firstSubmitterInput(): array
    {
        return $this->allSubmitterInputs()[0] ?? [];
    }
}
