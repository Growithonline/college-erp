<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup {--keep=14 : Days to keep backups}';
    protected $description = 'Full database backup — compressed .sql.gz saved to storage/app/backups/';

    public function handle(): int
    {
        $cfg  = config('database.connections.mysql');
        $host = $cfg['host'];
        $port = $cfg['port'] ?? 3306;
        $db   = $cfg['database'];
        $user = $cfg['username'];
        $pass = $cfg['password'];

        $dir  = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $date     = now()->format('Y-m-d_H-i-s');
        $filename = "{$dir}/backup_{$db}_{$date}.sql.gz";

        $cmd = sprintf(
            'mysqldump --single-transaction --quick --skip-lock-tables -h %s -P %s -u %s %s %s 2>&1 | gzip > %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            $pass !== '' ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($db),
            escapeshellarg($filename)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($filename) || filesize($filename) < 1000) {
            $this->error('Backup FAILED!');
            if ($output) $this->line(implode("\n", $output));
            return self::FAILURE;
        }

        $sizeMb = round(filesize($filename) / 1048576, 2);
        $this->info("Backup saved: {$filename} ({$sizeMb} MB)");

        // Delete backups older than --keep days
        $keepDays = (int) $this->option('keep');
        $deleted  = 0;
        foreach (glob("{$dir}/backup_*.sql.gz") as $file) {
            if (filemtime($file) < now()->subDays($keepDays)->timestamp) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->line("Deleted {$deleted} old backup(s) older than {$keepDays} days.");
        }

        return self::SUCCESS;
    }
}
