<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;

class BackupController extends Controller
{
    public function index()
    {
        $backupDir = storage_path('app/backups');
        $files = [];

        if (is_dir($backupDir)) {
            foreach (glob("{$backupDir}/backup_*.sql.gz") as $path) {
                $files[] = [
                    'name'     => basename($path),
                    'size'     => $this->humanSize(filesize($path)),
                    'modified' => date('d M Y, h:i A', filemtime($path)),
                    'ts'       => filemtime($path),
                ];
            }
            usort($files, fn($a, $b) => $b['ts'] - $a['ts']);
        }

        return view('super_admin.backup.index', compact('files'));
    }

    public function fullBackup()
    {
        $cfg      = config('database.connections.mysql');
        $date     = now()->format('Y-m-d_H-i-s');
        $filename = "full_backup_{$cfg['database']}_{$date}.sql.gz";

        $cmd = sprintf(
            'mysqldump --single-transaction --quick --skip-lock-tables -h %s -P %s -u %s %s %s',
            escapeshellarg($cfg['host']),
            escapeshellarg((string) ($cfg['port'] ?? 3306)),
            escapeshellarg($cfg['username']),
            $cfg['password'] !== '' ? '-p' . escapeshellarg($cfg['password']) : '',
            escapeshellarg($cfg['database'])
        );

        return response()->stream(function () use ($cmd) {
            passthru("{$cmd} | gzip");
        }, 200, [
            'Content-Type'        => 'application/gzip',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    public function schemaBackup()
    {
        $cfg      = config('database.connections.mysql');
        $date     = now()->format('Y-m-d_H-i-s');
        $filename = "schema_only_{$cfg['database']}_{$date}.sql";

        $cmd = sprintf(
            'mysqldump --no-data --skip-lock-tables -h %s -P %s -u %s %s %s',
            escapeshellarg($cfg['host']),
            escapeshellarg((string) ($cfg['port'] ?? 3306)),
            escapeshellarg($cfg['username']),
            $cfg['password'] !== '' ? '-p' . escapeshellarg($cfg['password']) : '',
            escapeshellarg($cfg['database'])
        );

        return response()->stream(function () use ($cmd) {
            passthru($cmd);
        }, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    public function downloadFile(string $file)
    {
        $path = storage_path("app/backups/{$file}");
        abort_if(! file_exists($path), 404);

        return response()->download($path);
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
