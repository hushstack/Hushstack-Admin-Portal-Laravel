<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission' => ['nullable', Rule::in(['view'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
