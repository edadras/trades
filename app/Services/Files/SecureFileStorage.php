<?php

namespace App\Services\Files;

use App\Jobs\ScanUploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Stores uploads on a private disk under random names, records checksum/mime/size and queues a malware scan.
 */
class SecureFileStorage
{
    /** @return array<string, mixed> attributes for a SecureFile model */
    public function store(UploadedFile $file, string $directory): array
    {
        $disk = config('platform.uploads.disk');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $path = $file->storeAs(trim($directory, '/').'/'.now()->format('Y/m'), Str::uuid()->toString().'.'.$extension, ['disk' => $disk]);

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'scan_status' => 'pending',
        ];
    }

    /** Queue a scan for a freshly created file model. */
    public function scan(Model $file): void
    {
        ScanUploadedFile::dispatch($file::class, $file->getKey());
    }

    /** @return array<int, string> validation rules for a regular document upload */
    public static function documentRules(): array
    {
        return ['file', 'max:'.config('platform.uploads.max_kb'), 'mimes:'.implode(',', config('platform.uploads.mimes'))];
    }

    /** @return array<int, string> validation rules for a voice recording */
    public static function voiceRules(): array
    {
        return ['file', 'max:'.config('platform.uploads.max_kb'), 'mimetypes:audio/webm,audio/ogg,audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,video/webm,application/octet-stream'];
    }
}
