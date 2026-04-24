<?php

namespace App\Http\Requests\CliAuth;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeCliAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_code' => ['required', 'string', 'max:255'],
        ];
    }
}
