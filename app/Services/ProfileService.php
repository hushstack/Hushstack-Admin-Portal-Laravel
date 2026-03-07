<?php

namespace App\Services;

use App\Models\User;
use App\Services\UploadService;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function __construct(private readonly UploadService $uploadService)
    {
    }

    public function updatePersonalInformation(User $user, array $data): User
    {
        // Simple fields
        if (array_key_exists('first_name', $data)) {
            $user->first_name = $data['first_name'];
        }

        if (array_key_exists('last_name', $data)) {
            $user->last_name = $data['last_name'];
        }

        if (array_key_exists('phone', $data)) {
            $user->phone_number = $data['phone'];
        }

        if (array_key_exists('bio', $data)) {
            $user->bio = $data['bio'];
        }

        // Username change rule: only once every 7 days
        if (array_key_exists('username', $data) && $data['username'] !== $user->username) {
            $now = now();

            if ($user->username_changed_at) {
                $daysDiff = $user->username_changed_at->diffInDays($now);

                if ($daysDiff < 7) {
                    $remaining = 7 - $daysDiff;

                    throw ValidationException::withMessages([
                        'username' => [
                            "You can change your username again in {$remaining} day(s).",
                        ],
                    ]);
                }
            }

            $user->username = $data['username'];
            $user->username_changed_at = $now;
        }

        $user->save();

        return $user->refresh();
    }

    public function updateAddress(User $user, array $data): User
    {
        if (array_key_exists('country', $data)) {
            $user->country = $data['country'];
        }

        if (array_key_exists('city_state', $data)) {
            $user->city_state = $data['city_state'];
        }

        if (array_key_exists('postal_code', $data)) {
            $user->postal_code = $data['postal_code'];
        }

        if (array_key_exists('tax_id', $data)) {
            $user->tax_id = $data['tax_id'];
        }

        if (array_key_exists('address', $data)) {
            $user->address = $data['address'];
        }

        $user->save();

        return $user->refresh();
    }
    /**
     * Get data for profile header section.
     */
    public function getProfileHeader(User $user): User
    {
        return $user;
    }

    /**
     * Get data for personal information section.
     */
    public function getPersonalInformation(User $user): User
    {
        return $user;
    }

    /**
     * Get data for address section.
     */
    public function getAddress(User $user): User
    {
        return $user;
    }

    /**
     * Update profile header: bio, social links, picture & cover.
     */
    public function updateProfileHeader(User $user, array $data): User
    {
        // Simple fields
        if (array_key_exists('bio', $data)) {
            $user->bio = $data['bio'];
        }

        foreach (['facebook_url', 'x_url', 'linkedin_url', 'instagram_url'] as $field) {
            if (array_key_exists($field, $data)) {
                $user->{$field} = $data[$field];
            }
        }

        // Upload picture
        if (!empty($data['picture'])) {
            $user->picture = $this->uploadService->uploadAndReplace(
                $data['picture'],
                $user->picture,
                'users/profile_pictures'
            );
        }

        // Upload cover
        if (!empty($data['cover'])) {
            $user->cover = $this->uploadService->uploadAndReplace(
                $data['cover'],
                $user->cover,
                'users/profile_covers'
            );
        }

        $user->save();

        return $user->refresh();
    }
}
