<?php

namespace App\Services\Files;

class NullScanner implements FileScanner
{
    public function scan(string $disk, string $path): string
    {
        return 'skipped';
    }
}
