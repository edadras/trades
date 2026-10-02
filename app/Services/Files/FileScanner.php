<?php

namespace App\Services\Files;

interface FileScanner
{
    /** @return string clean | infected | error | skipped */
    public function scan(string $disk, string $path): string;
}
