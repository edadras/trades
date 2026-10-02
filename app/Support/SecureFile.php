<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * Shared behaviour for every model that points to a private, scanned file.
 * Files are never exposed with a direct URL: downloads go through a short-lived signed route
 * that re-checks authorization and writes an audit entry.
 */
trait SecureFile
{
    abstract public static function fileType(): string;

    public function downloadUrl(int $minutes = 10, bool $inline = false): string
    {
        return URL::temporarySignedRoute('files.download', now()->addMinutes($minutes), array_filter([
            'type' => static::fileType(),
            'id' => $this->getKey(),
            'inline' => $inline ? 1 : null,
        ]));
    }

    public function isDownloadable(): bool
    {
        return in_array($this->scan_status, ['clean', 'skipped'], true);
    }

    /** @return array<string, mixed> */
    public function fileSummary(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->title ?? $this->original_name,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'scan_status' => $this->scan_status,
            'url' => $this->isDownloadable() ? $this->downloadUrl() : null,
            'inline_url' => $this->isDownloadable() && preg_match('#^(audio|image)/#', (string) $this->mime_type) ? $this->downloadUrl(30, true) : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
