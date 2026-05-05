<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'emoji' => ['nullable', 'string', 'max:32'],
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('folders', 'id')->where('user_id', $this->user()?->id),
            ],
            'blocks' => ['nullable', 'array', 'max:500'],
            'blocks.*.type' => ['required_with:blocks', Rule::in(['text', 'heading1', 'heading2', 'bulleted', 'todo', 'image', 'code', 'quote', 'divider'])],
            'blocks.*.content' => ['nullable', 'string'],
            'blocks.*.checked' => ['nullable', 'boolean'],
            'blocks.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
