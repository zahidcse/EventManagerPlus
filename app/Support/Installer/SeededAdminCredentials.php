<?php

declare(strict_types=1);

namespace App\Support\Installer;

/**
 * Default admin User created by {@see \Database\Seeders\AdminUserSeeder}.
 * Written to .env during install and echoed on the completion screen.
 */
final class SeededAdminCredentials
{
    public const NAME = 'Admin User';

    public const EMAIL = 'admin@eventflow.com';

    public const PASSWORD = 'password';
}
