<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'emoji' => ['sometimes', 'nullable', 'string', 'max:32'],
            'folder_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('folders', 'id')->where('user_id', $this->user()?->id),
            ],
            'blocks' => ['sometimes', 'array', 'max:500'],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.type' => ['required_with:blocks', Rule::in(['text', 'heading1', 'heading2', 'bulleted', 'todo', 'image', 'code', 'quote', 'divider'])],
            'blocks.*.content' => ['nullable', 'string'],
            'blocks.*.checked' => ['nullable', 'boolean'],
            'blocks.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
