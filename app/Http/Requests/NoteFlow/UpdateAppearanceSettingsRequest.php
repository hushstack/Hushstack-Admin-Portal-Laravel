<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppearanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => ['sometimes', Rule::in(['Light', 'Dark', 'System'])],
            'compact_mode' => ['sometimes', 'boolean'],
            'show_animations' => ['sometimes', 'boolean'],
        ];
    }
}
