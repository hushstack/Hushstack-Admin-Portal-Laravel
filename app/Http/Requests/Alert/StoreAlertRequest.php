<?php

namespace App\Http\Requests\Alert;

use App\Models\Alert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'server_name' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'severity' => ['required', 'string', Rule::in(Alert::SEVERITIES)],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:10000'],
            'source_ip' => ['nullable', 'ip', 'max:45'],
            'user_name' => ['nullable', 'string', 'max:150'],
            'auth_method' => ['nullable', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'service_name' => ['nullable', 'string', 'max:150'],
            'path' => ['nullable', 'string', 'max:2048'],
            'fingerprint' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
