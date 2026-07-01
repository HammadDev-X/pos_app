<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicImageUrl
{
    public static function make(?string $path, string $fallback): string
    {
        if (blank($path)) {
            return asset($fallback);
        }

        $path = trim((string) $path);

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return route('media.public', ['path' => $path]);
        }

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return asset($fallback);
    }
}
