<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    /**
     * Seed 30 test users.
     */
    public function run(): void
    {
        $states = collect(range(1, 30))->map(function (int $i) {
            $suffix = str_pad((string) $i, 3, '0', STR_PAD_LEFT);

            return [
                'email' => "testuser{$i}@example.test",
                'username' => "testuser{$i}",
                'phone_number' => "0800000{$suffix}",
            ];
        })->all();

        User::factory()
            ->count(30)
            ->state(new Sequence(...$states))
            ->create();
    }
}
