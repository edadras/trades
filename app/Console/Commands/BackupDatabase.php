<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

#[Signature('platform:backup')]
#[Description('Dump the database to the backup disk (gzip) and prune old dumps')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $disk = Storage::disk(config('platform.backup.disk'));
        $name = 'db-'.now()->format('Ymd-His').'.sql.gz';
        $tmp = storage_path('app/'.$name);

        if ($config['driver'] === 'sqlite') {
            file_put_contents($tmp, gzencode((string) file_get_contents($config['database'])));
        } elseif (in_array($config['driver'], ['mysql', 'mariadb'], true)) {
            $process = Process::fromShellCommandline('mysqldump --single-transaction --quick --host="$H" --port="$P" --user="$U" "$D" | gzip > "$OUT"', null, [
                'H' => $config['host'], 'P' => (string) $config['port'], 'U' => $config['username'], 'D' => $config['database'],
                'MYSQL_PWD' => (string) $config['password'], 'OUT' => $tmp,
            ], null, 3600);
            $process->run();
            if (! $process->isSuccessful()) {
                $this->error($process->getErrorOutput());

                return self::FAILURE;
            }
        } else {
            $this->error("Unsupported driver {$config['driver']}");

            return self::FAILURE;
        }

        $disk->put($name, fopen($tmp, 'r'));
        @unlink($tmp);

        $files = collect($disk->files())->filter(fn ($f) => str_starts_with($f, 'db-'))->sort()->values();
        $files->slice(0, max(0, $files->count() - config('platform.backup.keep')))->each(fn ($f) => $disk->delete($f));

        $this->info("Backup stored: {$name}");

        return self::SUCCESS;
    }
}
