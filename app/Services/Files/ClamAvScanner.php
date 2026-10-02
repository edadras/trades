<?php

namespace App\Services\Files;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Streams the file to clamd using the INSTREAM protocol. */
class ClamAvScanner implements FileScanner
{
    public function scan(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (! $stream) {
            return 'error';
        }

        try {
            $config = config('platform.scanner');
            $address = $config['clamav_socket'] ? 'unix://'.$config['clamav_socket'] : "tcp://{$config['clamav_host']}:{$config['clamav_port']}";
            $socket = stream_socket_client($address, $errno, $error, 10);
            if (! $socket) {
                throw new \RuntimeException("clamd unreachable: {$error}");
            }
            fwrite($socket, "zINSTREAM\0");
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }
            fwrite($socket, pack('N', 0));
            $reply = trim((string) stream_get_contents($socket));
            fclose($socket);

            return str_ends_with($reply, 'OK') ? 'clean' : (str_contains($reply, 'FOUND') ? 'infected' : 'error');
        } catch (Throwable $e) {
            Log::error('File scan failed', ['path' => $path, 'error' => $e->getMessage()]);

            return 'error';
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
