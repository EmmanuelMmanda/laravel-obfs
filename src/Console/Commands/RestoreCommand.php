<?php

namespace Mmanda\LaravelObfs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreCommand extends Command
{
    protected $signature = 'mObfuscate:restore {backup}';
    protected $description = 'Restore a backed-up file or directory';

    public function handle()
    {
        $backupName = $this->argument('backup');
        $backupPath = base_path('M_obfuscate_backups') . DIRECTORY_SEPARATOR . $backupName;

        if (!File::exists($backupPath)) {
            $this->error("Backup '$backupName' not found.");
            return;
        }

        // Extract original path from backup name
        preg_match('/^M_(.*?)_/', $backupName, $matches);
        $originalPath = str_replace('_', '/', $matches[1]);

        $restorePath = base_path($originalPath);

        $this->obfuscationService->restoreBackup($backupPath, $restorePath);
    }
}
