<?php

namespace App\Http\Requests\CliAuth;

use Illuminate\Foundation\Http\FormRequest;

class StartCliAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['nullable', 'string', 'max:80'],
            'client_version' => ['nullable', 'string', 'max:40'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'requested_abilities' => ['nullable', 'array', 'max:20'],
            'requested_abilities.*' => ['string', 'max:80', 'regex:/^[a-z0-9:_\.-]+$/'],
        ];
    }
}
