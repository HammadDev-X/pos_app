<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PublicStorage
{
    public static function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');

        self::mirror($path);

        return $path;
    }

    public static function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $path = self::normalize($path);

        Storage::disk('public')->delete($path);

        $mirrorPath = public_path('storage/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));

        if (! self::usesStorageSymlink() && is_file($mirrorPath)) {
            @unlink($mirrorPath);
        }
    }

    public static function mirror(string $path): void
    {
        if (app()->runningUnitTests() || self::usesStorageSymlink()) {
            return;
        }

        $path = self::normalize($path);
        $source = Storage::disk('public')->path($path);
        $target = public_path('storage/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));

        if (! is_file($source)) {
            return;
        }

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        copy($source, $target);
    }

    private static function normalize(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    private static function usesStorageSymlink(): bool
    {
        return is_link(public_path('storage'));
    }
}
