<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class SendMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'telegram_number' => ['required', 'string', 'max:50'],
        ];
    }
}
