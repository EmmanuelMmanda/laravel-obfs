<?php

namespace Mmanda\LaravelObfs\Console\Commands;

use Mmanda\LaravelObfs\Console\Commands\BaseObfuscateCommand;
use Illuminate\Support\Facades\File;

class RestoreCommand extends BaseObfuscateCommand
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

        // Extract original relative path from backup name (base64 between 'M_' and last '_')
        $lastUnderscore = strrpos($backupName, '_');
        $encoded = substr($backupName, 2, $lastUnderscore - 2);
        $originalPath = base64_decode($encoded);

        $restorePath = base_path($originalPath);

        $this->obfuscationService->restoreBackup($backupPath, $restorePath);
    }
}
