<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['role' => User::ROLE_ADMIN,   'prefix' => 'SEED_ADMIN'],
            ['role' => User::ROLE_PARTNER, 'prefix' => 'SEED_PARTNER'],
        ];

        foreach ($accounts as $account) {
            $name = env($account['prefix'].'_NAME');
            $email = env($account['prefix'].'_EMAIL');
            $password = env($account['prefix'].'_PASSWORD');

            if (! $name || ! $email || ! $password) {
                throw new RuntimeException("Missing {$account['prefix']}_NAME / _EMAIL / _PASSWORD in .env");
            }

            $user = User::firstOrNew(['email' => $email]);

            $user->forceFill([
                'name' => $name,
                'role' => $account['role'],
                'password' => $password, // hashed automatically by the model cast
                'email_verified_at' => now(),
            ])->save();
        }
    }
}