<?php

namespace App\Services;

class TemplateBuilderService
{
    public function normalize(array $payload): array
    {
        $normalized = [];

        foreach (['schema', 'fields', 'submitters', 'preferences', 'variables_schema'] as $key) {
            if (array_key_exists($key, $payload)) {
                $normalized[$key] = $this->ensureArray($payload[$key]);
            }
        }

        return $normalized;
    }

    private function ensureArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
