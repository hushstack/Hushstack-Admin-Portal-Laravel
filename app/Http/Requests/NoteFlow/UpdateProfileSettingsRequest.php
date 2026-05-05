<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
