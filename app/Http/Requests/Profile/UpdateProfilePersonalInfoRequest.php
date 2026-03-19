<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePersonalInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:255'],

            // username: unique except current user
            'username' => ['sometimes', 'nullable', 'string', 'max:255',
                'unique:users,username,'.$userId,
            ],

            // email is INTENTIONALLY missing => cannot be updated via this endpoint
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'phone number',
        ];
    }
}
