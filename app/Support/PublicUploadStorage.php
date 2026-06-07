<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicUploadStorage
{
    /**
     * Persist an upload on the public uploads disk without using {@see UploadedFile::store()},
     * which opens the file via {@see \Symfony\Component\HttpFoundation\File\File::getRealPath()}.
     * On Windows that can return false and trigger "Path cannot be empty" inside fopen.
     */
    public static function store(UploadedFile $file, string $directory): ?string
    {
        $source = $file->getRealPath();
        if ($source === false || $source === '') {
            $source = $file->getPathname();
        }

        if ($source === '' || ! is_readable($source)) {
            return null;
        }

        $contents = @file_get_contents($source);
        if ($contents === false) {
            return null;
        }

        $ext = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $suffix = ($ext !== null && $ext !== '') ? '.'.ltrim(strtolower((string) $ext), '.') : '';
        $relative = trim($directory, '/').'/'.Str::random(40).$suffix;

        if (! Storage::disk('uploads')->put($relative, $contents, ['visibility' => 'public'])) {
            return null;
        }

        return $relative;
    }
}
