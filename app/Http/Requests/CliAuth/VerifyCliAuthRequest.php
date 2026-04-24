<?php

namespace App\Http\Requests\CliAuth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCliAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_code' => ['nullable', 'string', 'max:32'],
        ];
    }
}
