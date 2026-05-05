<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:180'],
            'message' => ['sometimes', 'string', 'max:5000'],
            'type' => ['sometimes', Rule::in(['event', 'reminder', 'ai', 'system', 'deadline'])],
            'scheduled_for' => ['sometimes', 'date'],
            'email_enabled' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['pending', 'sent', 'failed'])],
        ];
    }
}
