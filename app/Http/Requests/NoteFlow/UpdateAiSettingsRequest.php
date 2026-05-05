<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_language' => ['sometimes', 'string', 'max:80'],
            'tone' => ['sometimes', Rule::in(['Professional', 'Casual', 'Academic', 'Creative'])],
            'output_length' => ['sometimes', Rule::in(['Short', 'Medium', 'Long'])],
        ];
    }
}
