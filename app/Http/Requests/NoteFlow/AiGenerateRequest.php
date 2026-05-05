<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tool' => ['required', Rule::in(['summarize', 'rewrite', 'translate', 'code', 'ideas', 'chat'])],
            'input_text' => ['required', 'string', 'max:20000'],
            'language' => ['nullable', 'string', 'max:80'],
            'tone' => ['nullable', Rule::in(['Professional', 'Casual', 'Academic', 'Creative'])],
            'output_length' => ['nullable', Rule::in(['Short', 'Medium', 'Long'])],
            'source_note_id' => ['nullable', 'integer', Rule::exists('notes', 'id')->where('user_id', $this->user()?->id)],
            'source_upload_id' => ['nullable', 'integer', Rule::exists('uploads', 'id')->where('user_id', $this->user()?->id)],
        ];
    }
}
