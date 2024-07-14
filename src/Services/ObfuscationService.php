<?php

namespace Mmanda\LaravelObfs\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ObfuscationService
{
    public function obfuscateFile($filePath, $backup = false)
    {
        if ($backup) {
            $this->createBackup($filePath);
        }

        $this->obfuscate($filePath);
    }

    public function obfuscateDirectory($directoryPath, $backup = false)
    {
        $files = File::allFiles($directoryPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $filePath = $file->getPathname();
                if ($backup) {
                    $this->createBackup($filePath);
                }
                $this->obfuscate($filePath);
            }
        }
    }

    public function obfuscateAll($backup = false)
    {
        $this->obfuscateDirectory(base_path(), $backup);
    }

    public function restoreBackup($backupPath, $restorePath)
    {
        if (File::exists($backupPath)) {
            File::copy($backupPath, $restorePath);
            $this->info("Restored backup to '$restorePath'.");
        } else {
            throw new \RuntimeException("Backup file '$backupPath' does not exist.");
        }
    }

    private function createBackup($filePath)
    {
        $backupDir = base_path('M_obfuscate_backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true); // Create backup directory if not exists
        }

        $backupFileName = 'M_' . str_replace('/', '_', $filePath) . '_' . time();
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupFileName;

        File::copy($filePath, $backupPath);

        $this->info("Created backup '$backupPath'.");
    }

    private function obfuscate($filePath)
    {
        $mObfsPath = config('mObfs.mObfs_path');
        $configPath = config('mObfs.config_file');
        $outputPath = $filePath; // Overwrite the original file

        $command = [
            $mObfsPath,
            '--config-file',
            $configPath,
            $filePath,
            '-o',
            $outputPath,
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Error obfuscating file: ' . $filePath . ' - ' . $process->getErrorOutput());
        }
    }
}
