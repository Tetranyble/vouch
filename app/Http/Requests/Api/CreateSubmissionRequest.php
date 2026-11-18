<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'submitters'         => 'required|array|min:1',
            'submitters.*.email' => 'nullable|email',
            'submitters.*.phone' => 'nullable|string|max:30',
            'submitters.*.name'  => 'nullable|string|max:255',
            'submitters.*.role'  => 'nullable|string|max:255',
            'expire_at'          => 'nullable|date|after:now',
        ];

        if ($this->routeIs('api.submissions.store')) {
            $rules['template_id'] = 'required|integer';
        }

        return $rules;
    }

    public function submitterInputs(): array
    {
        return collect($this->input('submitters', []))
            ->map(fn ($s) => array_filter([
                'email' => $s['email'] ?? null,
                'phone' => $s['phone'] ?? null,
                'name'  => $s['name']  ?? null,
            ], fn ($v) => $v !== null))
            ->values()
            ->all();
    }
}
