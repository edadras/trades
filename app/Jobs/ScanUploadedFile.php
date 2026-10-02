<?php

namespace App\Jobs;

use App\Domain\Identity\AuditLogger;
use App\Services\Files\FileScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ScanUploadedFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $modelClass, public int $id) {}

    public function handle(FileScanner $scanner, AuditLogger $audit): void
    {
        $file = $this->modelClass::query()->find($this->id);
        if (! $file) {
            return;
        }

        $status = $scanner->scan($file->disk, $file->path);
        $file->forceFill(['scan_status' => $status])->save();

        if ($status === 'infected') {
            Storage::disk($file->disk)->delete($file->path);
            $audit->log('file.infected_removed', $file, ['name' => $file->original_name], null);
        }
    }
}
