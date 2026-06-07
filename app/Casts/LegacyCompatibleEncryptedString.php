<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Like Laravel's "encrypted" cast, but returns the raw DB value when decryption fails
 * (plaintext secrets saved before casting, ciphertext from another APP_KEY, partial imports).
 */
final class LegacyCompatibleEncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Crypt::decrypt($value, false);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [$key => null];
        }

        return [$key => Crypt::encrypt((string) $value, false)];
    }
}
