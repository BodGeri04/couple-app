<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores uploads AES-256 encrypted (via Laravel's Crypt, keyed by APP_KEY)
 * on the PRIVATE "local" disk (storage/app/private), and serves them back
 * by decrypting in memory. Nothing readable ever touches the disk,
 * and nothing is ever publicly reachable.
 */
class EncryptedFileService
{
    /** Private disk. Never use the "public" disk for this. */
    protected string $disk = 'local';

    protected string $directory = 'snaps';

    /** Only real raster images; blocks SVG/HTML (XSS risk). */
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Encrypt an uploaded file and write it to disk.
     *
     * @return array{path: string, mime_type: string, size: int}
     */
    public function store(UploadedFile $file): array
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream'; // detected from content, not file name

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException('Unsupported image type.');
        }

        $plain = file_get_contents($file->getRealPath());
        $path  = $this->directory.'/'.Str::uuid().'.enc'; // random name, no original name leaks

        // second argument false = raw binary string, skip PHP serialization
        Storage::disk($this->disk)->put($path, Crypt::encrypt($plain, false));

        return [
            'path'      => $path,
            'mime_type' => $mime,
            'size'      => strlen($plain),
        ];
    }

    /**
     * Decrypt in memory and return it as an HTTP response.
     * Call this ONLY from an authenticated, authorized route.
     */
    public function response(string $path, string $mime): Response
    {
        $disk = Storage::disk($this->disk);

        abort_unless($disk->exists($path), 404);

        $plain = Crypt::decrypt($disk->get($path), false);

        return response($plain, 200, [
            'Content-Type'           => $mime,
            'Content-Length'         => strlen($plain),
            'Content-Disposition'    => 'inline',
            'Cache-Control'          => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(string $path): void
    {
        Storage::disk($this->disk)->delete($path);
    }
}