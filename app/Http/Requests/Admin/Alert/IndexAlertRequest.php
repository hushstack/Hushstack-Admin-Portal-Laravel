<?php

namespace App\Http\Requests\Admin\Alert;

use App\Models\Alert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'severity' => ['sometimes', 'string', Rule::in(Alert::SEVERITIES)],
            'status' => ['sometimes', 'string', Rule::in(Alert::STATUSES)],
            'type' => ['sometimes', 'string', 'max:100'],
            'server_name' => ['sometimes', 'string', 'max:255'],
            'source_ip' => ['sometimes', 'ip', 'max:45'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'search' => ['sometimes', 'string', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
