<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRtspSchemeRequest extends FormRequest
{
    private const REQUIRED_PLACEHOLDERS = ['{username}', '{password}', '{ip}', '{port}'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:rtsp_schemes,name'],
            'scheme_template' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $template = (string) $this->input('scheme_template', '');

            $missing = array_values(array_filter(
                self::REQUIRED_PLACEHOLDERS,
                fn (string $placeholder): bool => ! str_contains($template, $placeholder),
            ));

            if ($missing !== []) {
                $validator->errors()->add(
                    'scheme_template',
                    'Scheme template must include placeholders: '.implode(', ', self::REQUIRED_PLACEHOLDERS).'. Missing: '.implode(', ', $missing).'.',
                );
            }
        });
    }
}
