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
        $mObfsPath = $this->enginePath();
        $configPath = config('mObfs.config_file');
        if (!File::isFile($mObfsPath)) {
            throw new \RuntimeException('Yakpro executable not found: ' . $mObfsPath);
        }
        if (!File::isFile($configPath)) {
            if ($configPath === base_path('config/mObfs.cnf')) {
                $configPath = __DIR__ . '/../config/mObfs.cnf';
            } else {
                throw new \RuntimeException('Yakpro config not found: ' . $configPath);
            }
        }

        $tempFilePath = $this->getTempFilePath($filePath);
        $tempOutPath = $this->getTempFilePath($filePath);

        try {
            if (!copy($filePath, $tempFilePath)) {
                throw new \RuntimeException('Failed to create temporary file for obfuscation: ' . $filePath);
            }

            $process = new Process([
                PHP_BINARY,
                $mObfsPath,
                '--config-file',
                $configPath,
                $tempFilePath,
                '-o',
                $tempOutPath,
            ]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Error obfuscating file: ' . $filePath . ' - ' . trim($process->getErrorOutput()));
            }
            if (!File::isFile($tempOutPath) || !copy($tempOutPath, $filePath)) {
                throw new \RuntimeException('Failed to write obfuscated output to original file: ' . $filePath);
            }
        } finally {
            @unlink($tempFilePath);
            @unlink($tempOutPath);
        }
    }

    private function getTempFilePath($filePath)
    {
        $basePath = dirname($filePath);
        $fileName = basename($filePath);
        $tempFileName = uniqid('temp_', true) . '_' . $fileName;
        return $basePath . DIRECTORY_SEPARATOR . $tempFileName;
    }

    private function enginePath()
    {
        $path = config('mObfs.mObfs_path');
        $oldDefault = base_path('vendor/pmdunggh/yakpro-po/yakpro-po.php');
        if ($path === $oldDefault) {
            return base_path('vendor/douglas-fortunatus/yakpro-po/yakpro-po.php');
        }
        return $path;
    }
}
