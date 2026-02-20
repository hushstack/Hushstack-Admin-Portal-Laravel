<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'country'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'city_state'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'tax_id'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'address'     => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
