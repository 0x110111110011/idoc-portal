<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = config('seeding.admin.email');
        $adminPassword = config('seeding.admin.password');

        if (! is_string($adminEmail) || trim($adminEmail) === ''
            || ! is_string($adminPassword) || $adminPassword === '') {
            if (app()->environment('production')) {
                throw new RuntimeException(
                    'Set SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD in the production environment before running AdminUserSeeder.'
                );
            }

            $adminEmail = 'admin@example.com';
            $adminPassword = 'password';
        }

        $adminEmail = strtolower(trim($adminEmail));

        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('SEED_ADMIN_EMAIL must be a valid email address.');
        }

        if (app()->environment('production') && strlen($adminPassword) < 16) {
            throw new RuntimeException(
                'SEED_ADMIN_PASSWORD must contain at least 16 characters in production.'
            );
        }

        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => config('seeding.admin.name', 'System Administrator'),
                'password' => Hash::make($adminPassword),
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $demoEmail = config('seeding.demo_user.email');
        $demoPassword = config('seeding.demo_user.password');

        if (filled($demoEmail) xor filled($demoPassword)) {
            throw new RuntimeException(
                'Set both SEED_DEMO_USER_EMAIL and SEED_DEMO_USER_PASSWORD, or leave both empty.'
            );
        }

        if (filled($demoEmail) && filled($demoPassword)) {
            if (! is_string($demoEmail) || ! filter_var($demoEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('SEED_DEMO_USER_EMAIL must be a valid email address.');
            }

            if (app()->environment('production') && strlen((string) $demoPassword) < 16) {
                throw new RuntimeException(
                    'SEED_DEMO_USER_PASSWORD must contain at least 16 characters in production.'
                );
            }

            User::query()->updateOrCreate(
                ['email' => strtolower(trim($demoEmail))],
                [
                    'name' => config('seeding.demo_user.name', 'John Doe'),
                    'password' => Hash::make((string) $demoPassword),
                    'role' => UserRole::USER,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        } elseif (! app()->environment('production')) {
            User::query()->updateOrCreate(
                ['email' => 'john@example.com'],
                [
                    'name' => 'John Doe',
                    'password' => Hash::make('password'),
                    'role' => UserRole::USER,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
