<?php

declare(strict_types=1);

namespace App\Support\Installer;

use Illuminate\Support\Facades\File;

final class EnvFileWriter
{
    /**
     * @param  array<string, string|null>  $pairs
     */
    public static function merge(string $pathToEnv, array $pairs): void
    {
        if (! File::exists($pathToEnv)) {
            File::copy(base_path('.env.example'), $pathToEnv);
        }

        $content = File::get($pathToEnv);

        foreach ($pairs as $key => $value) {
            $line = $key.'='.self::formatValue((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            if (preg_match($pattern, $content)) {
                $content = (string) preg_replace($pattern, $line, $content, 1);
            } else {
                $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
            }
        }

        File::put($pathToEnv, $content);
    }

    private static function formatValue(string $value): string
    {
        // Use KEY= not KEY="" — quoted empty broke DB passwords on Windows for some setups.
        if ($value === '') {
            return '';
        }

        $needsQuotes = preg_match('/[\s#"\'\$\\\\]|^`/u', $value) === 1;

        if ($needsQuotes) {
            return '"'.addcslashes($value, "\\\$\"\r\n\t").'"';
        }

        return $value;
    }
}
