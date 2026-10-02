<?php

namespace App\Http\Controllers;

use App\Domain\Identity\AuditLogger;
use App\Policies\FileAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only way to read a private file. Requires a valid short-lived signature AND an authenticated
 * user who is allowed to see the file. Every download is written to the audit log.
 */
class FileDownloadController extends Controller
{
    public function __invoke(Request $request, string $type, int $id, FileAccess $access, AuditLogger $audit): StreamedResponse
    {
        $class = FileAccess::TYPES[$type] ?? abort(404);
        $file = $class::query()->findOrFail($id);

        abort_unless($access->allows($request->user(), $file), 403);
        abort_unless($file->isDownloadable(), 423, 'File is not available (scan pending or failed).');
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $audit->log('file.downloaded', $file, ['type' => $type, 'name' => $file->original_name]);

        $inline = $request->boolean('inline') && str_starts_with((string) $file->mime_type, 'audio/') || ($request->boolean('inline') && str_starts_with((string) $file->mime_type, 'image/'));

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }
}
