<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUploadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ocr' => $this->normalizeBoolean($this->input('ocr')),
            'generate_summary' => $this->normalizeBoolean($this->input('generate_summary')),
            'searchable_index' => $this->normalizeBoolean($this->input('searchable_index')),
            'extract_key_points' => $this->normalizeBoolean($this->input('extract_key_points')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,png,jpg,jpeg,gif,txt,md'],
            'ocr' => ['nullable', 'boolean'],
            'generate_summary' => ['nullable', 'boolean'],
            'searchable_index' => ['nullable', 'boolean'],
            'extract_key_points' => ['nullable', 'boolean'],
            'output_format' => ['nullable', Rule::in(['Markdown', 'Plain Text', 'Structured'])],
        ];
    }

    private function normalizeBoolean(mixed $value): mixed
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }

        return match (strtolower((string) $value)) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}
