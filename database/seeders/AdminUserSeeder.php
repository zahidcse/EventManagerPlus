<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\User;
use App\Support\Installer\SeededAdminCredentials;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default admin user (idempotent by email).
     */
    public function run(): void
    {
        $seed = config('installer.seed_admin');

        if (is_array($seed)
            && isset($seed['email'], $seed['password'])
            && is_string($seed['email'])
            && is_string($seed['password'])
            && $seed['email'] !== ''
            && $seed['password'] !== '') {
            $email = trim($seed['email']);
            $password = $seed['password'];
            $name = isset($seed['name']) && is_string($seed['name']) && $seed['name'] !== ''
                ? trim($seed['name'])
                : SeededAdminCredentials::NAME;
        } else {
            $email = (string) env('ADMIN_EMAIL', SeededAdminCredentials::EMAIL);
            $password = (string) env('ADMIN_PASSWORD', SeededAdminCredentials::PASSWORD);
            $name = (string) env('ADMIN_NAME', SeededAdminCredentials::NAME);
        }

        $superRoleId = AdminRole::query()->where('slug', 'super-admin')->value('id');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'is_admin' => true,
                'admin_role_id' => $superRoleId,
            ],
        );
    }
}
