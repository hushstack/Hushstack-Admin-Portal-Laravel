<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class MicrosoftCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Socialite callback params are provider-specific and can vary.
        // Keeping empty rules is fine.
        return [];
    }
}
