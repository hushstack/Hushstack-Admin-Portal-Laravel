<?php

namespace App\Http\Requests\Admin\Member;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'position_id' => ['sometimes', 'required', 'integer', 'exists:positions,id'],
            'long_description' => ['nullable', 'string'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
