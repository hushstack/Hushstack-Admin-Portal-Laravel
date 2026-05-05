<?php

namespace App\Http\Requests\NoteFlow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_notifications' => ['sometimes', 'array'],
            'email_notifications.email_address' => ['sometimes', 'email', 'max:255'],
            'email_notifications.daily_digest' => ['sometimes', 'boolean'],
            'email_notifications.weekly_digest' => ['sometimes', 'boolean'],
            'email_notifications.event_reminders' => ['sometimes', 'boolean'],
            'email_notifications.ai_completions' => ['sometimes', 'boolean'],
            'email_notifications.note_collaborations' => ['sometimes', 'boolean'],
            'push_notifications' => ['sometimes', 'array'],
            'push_notifications.shared_notes' => ['sometimes', 'boolean'],
            'push_notifications.comments' => ['sometimes', 'boolean'],
            'push_notifications.ai_generation_completions' => ['sometimes', 'boolean'],
            'push_notifications.system_updates' => ['sometimes', 'boolean'],
            'push_notifications.weekly_activity_summary' => ['sometimes', 'boolean'],
        ];
    }
}
