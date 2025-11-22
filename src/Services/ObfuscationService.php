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
        $targets = config('mObfs.target_directories');
        if (is_array($targets) && !empty($targets)) {
            foreach ($targets as $dir) {
                $path = base_path($dir);
                if (File::isDirectory($path)) {
                    $this->obfuscateDirectory($path, $backup);
                }
            }
        } else {
            $this->obfuscateDirectory(base_path('app'), $backup);
            $this->obfuscateDirectory(base_path('routes'), $backup);
        }
    }

    public function restoreBackup($backupPath, $restorePath)
    {
        if (File::exists($backupPath)) {
            File::copy($backupPath, $restorePath);
        } else {
            throw new \RuntimeException("Backup file '$backupPath' does not exist.");
        }
    }

    private function createBackup($filePath)
    {
        $backupDir = base_path('M_obfuscate_backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $root = str_replace('\\','/', base_path());
        $file = str_replace('\\','/', $filePath);
        $relative = ltrim(str_replace($root.'/', '', $file), '/');
        $encoded = base64_encode($relative);
        $backupFileName = 'M_' . $encoded . '_' . time();
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupFileName;

        File::copy($filePath, $backupPath);
    }

    private function obfuscate($filePath)
    {
        $mObfsPath = config('mObfs.mObfs_path');
        $configPath = config('mObfs.config_file');

        $tempFilePath = $this->getTempFilePath($filePath);
        $tempOutPath = $this->getTempFilePath($filePath);

        if (!copy($filePath, $tempFilePath)) {
            throw new \RuntimeException('Failed to create temporary file for obfuscation: ' . $filePath);
        }

        $command = [
            'php',
            $mObfsPath,
            '--config-file',
            $configPath,
            $tempFilePath,
            '-o',
            $tempOutPath,
        ];

        $process = new Process($command);
        $process->run();

        if (File::exists($tempFilePath)) {
            @unlink($tempFilePath);
        }

        if (!$process->isSuccessful()) {
            if (File::exists($tempOutPath)) {
                @unlink($tempOutPath);
            }
            throw new \RuntimeException('Error obfuscating file: ' . $filePath . ' - ' . $process->getErrorOutput());
        }

        if (!copy($tempOutPath, $filePath)) {
            @unlink($tempOutPath);
            throw new \RuntimeException('Failed to write obfuscated output to original file: ' . $filePath);
        }

        @unlink($tempOutPath);
    }

    private function getTempFilePath($filePath)
    {
        $basePath = dirname($filePath);
        $fileName = basename($filePath);
        $tempFileName = uniqid('temp_', true) . '_' . $fileName;
        return $basePath . DIRECTORY_SEPARATOR . $tempFileName;
    }


}
