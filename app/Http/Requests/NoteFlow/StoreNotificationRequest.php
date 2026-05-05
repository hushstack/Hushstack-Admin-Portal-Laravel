<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['event', 'reminder', 'ai', 'system', 'deadline'])],
            'scheduled_for' => ['required', 'date'],
            'email_enabled' => ['required', 'boolean'],
        ];
    }
}
