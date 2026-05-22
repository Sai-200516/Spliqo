<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageEncoder
{
    /**
     * Encode an uploaded file as a base64 data URI for MongoDB storage.
     * No PHP image extension (GD/Imagick) required.
     */
    public function encode(UploadedFile $file, int $maxPx = 400): string
    {
        $raw  = file_get_contents($file->getRealPath());
        $mime = $file->getMimeType() ?? 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    /**
     * Download a remote image URL and encode it as a base64 data URI.
     * Used for Google OAuth avatars. Returns null on any failure.
     */
    public function encodeUrl(string $url, int $maxPx = 400): ?string
    {
        try {
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $raw = @file_get_contents($url, false, $context);
            if (!$raw) {
                return null;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->buffer($raw) ?: 'image/jpeg';

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
