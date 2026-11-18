<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submitters' => 'required|array|min:1|max:100',
            'submitters.*.email' => 'nullable|email',
            'submitters.*.name' => 'nullable|string|max:255',
            'submitters.*.phone' => 'nullable|string|max:30',
        ];
    }

    /** @return array<int, array{name?:string, email?:string, phone?:string}> */
    public function validRecipients(): array
    {
        return collect($this->input('submitters'))
            ->filter(fn ($row) => ! empty($row['email']) || ! empty($row['phone']))
            ->map(fn ($row) => array_filter([
                'name' => $row['name'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
            ]))
            ->values()
            ->all();
    }
}
