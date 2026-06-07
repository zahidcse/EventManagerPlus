<?php

declare(strict_types=1);

namespace App\Support\Installer;

use Illuminate\Support\Facades\File;

final class InstallationStatus
{
    public static function lockFilePath(): string
    {
        return storage_path('app/install.lock');
    }

    public static function completed(): bool
    {
        return is_file(self::lockFilePath());
    }

    public static function markCompleted(?string $message = null): void
    {
        File::ensureDirectoryExists(dirname(self::lockFilePath()));
        $payload = json_encode([
            'installed_at' => now()->toIso8601String(),
            'message' => $message,
        ], JSON_THROW_ON_ERROR);
        File::put(self::lockFilePath(), $payload);
    }
}
