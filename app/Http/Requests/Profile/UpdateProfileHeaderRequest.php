<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // User must be logged in via sanctum
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bio'           => ['nullable', 'string', 'max:255'],

            // Social links
            'facebook_url'  => ['nullable', 'url', 'max:255'],
            'x_url'         => ['nullable', 'url', 'max:255'],
            'linkedin_url'  => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],

            // Images
            'picture'       => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB
            'cover'         => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'], // 8MB
        ];
    }
}
