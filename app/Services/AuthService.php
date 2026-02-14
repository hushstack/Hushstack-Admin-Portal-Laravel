<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): User
    {
        return User::create([
            'username' => $data['username'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'birth_of_date' => $data['birth_of_date'],
            'password' => Hash::make($data['password']),
            'is_verified' => false,
        ]);
    }

    public function validateCredentials(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        if (!$user) return null;

        if (!Hash::check($password, $user->password)) return null;
        return $user;
    }

    public function issueToken(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return true;
    }
}
