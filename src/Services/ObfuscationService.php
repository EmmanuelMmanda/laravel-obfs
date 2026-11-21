<?php

namespace Mmanda\LaravelObfs\Services;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class ObfuscationService
{
    private int $skippedSymlinks = 0;

    public function obfuscateFile($filePath, $backup = false): void
    {
        $file = $this->sourcePath($filePath, false);
        $job = $this->startFile($file, (bool) $backup);
        try {
            $job['process']->wait();
            $this->finishFile($job);
        } finally {
            @unlink($job['output']);
        }
    }

    public function obfuscateDirectory($directoryPath, $backup = false, $jobs = 4): int
    {
        $directory = $this->sourcePath($directoryPath, true);
        $limit = $this->jobLimit($jobs);
        $this->skippedSymlinks = 0;
        $this->runFiles($this->phpFiles($directory), (bool) $backup, $limit);
        return $this->skippedSymlinks;
    }

    public function obfuscateAll($backup = false, $jobs = 4): int
    {
        $limit = $this->jobLimit($jobs);
        $targets = config('mObfs.target_directories');
        if (!is_array($targets) || !$targets) {
            $targets = ['app', 'routes'];
        }
        $skipped = 0;
        foreach ($targets as $target) {
            if (!is_string($target) || $target === '') {
                throw new RuntimeException('Invalid target directory.');
            }
            $path = base_path($target);
            if (!file_exists($path) && !is_link($path)) {
                continue;
            }
            $skipped += $this->obfuscateDirectory($path, $backup, $limit);
        }
        return $skipped;
    }

    public function restoreBackupByName(string $name): void
    {
        if (basename($name) !== $name || $name === '.' || $name === '..') {
            throw new RuntimeException('Invalid backup name.');
        }
        $relative = $this->decodeBackupName($name);
        $destination = $this->restorePath($relative);
        $backupDir = $this->backupDirectory(false);
        $backup = $backupDir . DIRECTORY_SEPARATOR . $name;
        if (is_link($backup) || !is_file($backup)) {
            throw new RuntimeException("Backup '$name' not found or is not a regular file.");
        }
        $this->restoreBackup($backup, $destination);
    }

    public function restoreBackup($backupPath, $restorePath): void
    {
        $backupDir = $this->backupDirectory(false);
        $backup = realpath($backupPath);
        if (!$backup || dirname($backup) !== $backupDir || is_link($backupPath) || !is_file($backup)) {
            throw new RuntimeException('Invalid backup file.');
        }
        $relative = $this->decodeBackupName(basename($backup));
        $destination = $this->restorePath($relative);
        if ($destination !== $this->restorePathFromInput($restorePath)) {
            throw new RuntimeException('Restore destination does not match backup name.');
        }
        $temporary = $this->temporaryPath($destination);
        try {
            if (!copy($backup, $temporary)) {
                throw new RuntimeException('Failed to copy backup.');
            }
            if (is_file($destination)) {
                $mode = fileperms($destination);
                if ($mode !== false) {
                    chmod($temporary, $mode & 0777);
                }
            }
            $this->restorePath($relative);
            if (!rename($temporary, $destination)) {
                throw new RuntimeException('Failed to restore backup.');
            }
        } finally {
            @unlink($temporary);
        }
    }

    private function runFiles(iterable $files, bool $backup, int $limit): void
    {
        $iterator = $files instanceof \Iterator ? $files : (function () use ($files) { yield from $files; })();
        $iterator->rewind();
        $running = [];
        $failure = null;
        while (($failure === null && $iterator->valid()) || $running) {
            while ($failure === null && $iterator->valid() && count($running) < $limit) {
                $file = $iterator->current();
                $iterator->next();
                try {
                    $running[] = $this->startFile($file, $backup);
                } catch (Throwable $e) {
                    $failure = $e;
                }
            }
            $completed = false;
            foreach ($running as $index => $job) {
                if ($job['process']->isRunning()) {
                    continue;
                }
                $completed = true;
                try {
                    $this->finishFile($job);
                } catch (Throwable $e) {
                    $failure ??= $e;
                } finally {
                    @unlink($job['output']);
                    unset($running[$index]);
                }
            }
            if ($running && !$completed) {
                usleep(10000);
            }
        }
        if ($failure !== null) {
            throw $failure;
        }
    }

    private function phpFiles(string $directory): \Generator
    {
        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            function ($entry) {
                if ($entry->isLink()) {
                    ++$this->skippedSymlinks;
                    return false;
                }
                if ($entry->isDir()) {
                    return !$this->excluded($entry->getPathname());
                }
                return $entry->isFile() && strtolower($entry->getExtension()) === 'php';
            }
        );
        foreach (new RecursiveIteratorIterator($filter) as $entry) {
            yield $entry->getPathname();
        }
    }

    private function startFile(string $path, bool $backup): array
    {
        $path = $this->sourcePath($path, false);
        $engine = $this->enginePath();
        $config = config('mObfs.config_file');
        if (!is_string($engine) || !is_file($engine)) {
            throw new RuntimeException('Yakpro executable not found: ' . (string) $engine);
        }
        if (!is_string($config) || !is_file($config)) {
            if ($config === base_path('config/mObfs.cnf')) {
                $config = __DIR__ . '/../config/mObfs.cnf';
            } else {
                throw new RuntimeException('Yakpro config not found: ' . (string) $config);
            }
        }
        if ($backup) {
            $this->createBackup($path);
        }
        $output = $this->temporaryPath($path);
        try {
            $process = new Process([PHP_BINARY, $engine, '--config-file', $config, $path, '-o', $output]);
            $process->start();
            return ['path' => $path, 'output' => $output, 'process' => $process];
        } catch (Throwable $e) {
            @unlink($output);
            throw $e;
        }
    }

    private function finishFile(array $job): void
    {
        $process = $job['process'];
        if (!$process->isSuccessful()) {
            throw new RuntimeException('Error obfuscating file: ' . $job['path'] . ' - ' . trim($process->getErrorOutput() . ' ' . $process->getOutput()));
        }
        if (!is_file($job['output'])) {
            throw new RuntimeException('Obfuscator produced no output: ' . $job['path']);
        }
        $this->sourcePath($job['path'], false);
        $mode = fileperms($job['path']);
        if ($mode !== false) {
            chmod($job['output'], $mode & 0777);
        }
        if (!rename($job['output'], $job['path'])) {
            throw new RuntimeException('Failed to replace source: ' . $job['path']);
        }
    }

    private function createBackup(string $path): void
    {
        $relative = substr($path, strlen($this->root()) + 1);
        $encoded = rtrim(strtr(base64_encode($relative), '+/', '-_'), '=');
        $name = 'M2_' . $encoded . '_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(8));
        $destination = $this->backupDirectory(true) . DIRECTORY_SEPARATOR . $name;
        $handle = fopen($destination, 'x');
        if ($handle === false) {
            throw new RuntimeException('Failed to create backup: ' . $destination);
        }
        try {
            if (!chmod($destination, 0600)) {
                throw new RuntimeException('Failed to secure backup: ' . $destination);
            }
            $source = fopen($path, 'rb');
            if ($source === false) {
                throw new RuntimeException('Failed to read source: ' . $path);
            }
            try {
                if (stream_copy_to_stream($source, $handle) === false) {
                    throw new RuntimeException('Failed to write backup: ' . $destination);
                }
            } finally {
                fclose($source);
            }
        } catch (Throwable $e) {
            fclose($handle);
            @unlink($destination);
            throw $e;
        }
        fclose($handle);
    }

    private function decodeBackupName(string $name): string
    {
        if (preg_match('/^M2_([A-Za-z0-9_-]+)_[0-9]{14}_[a-f0-9]{16}$/D', $name, $matches)) {
            $encoded = strtr($matches[1], '-_', '+/');
            $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        } elseif (preg_match('/^M_([A-Za-z0-9+=]+)_[0-9]+$/D', $name, $matches)) {
            $encoded = $matches[1];
        } else {
            throw new RuntimeException('Malformed backup name.');
        }
        $relative = base64_decode($encoded, true);
        if ($relative === false || str_contains($relative, "\0")) {
            throw new RuntimeException('Malformed backup path.');
        }
        return $relative;
    }

    private function restorePath(string $relative): string
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '\\')) {
            throw new RuntimeException('Backup path escapes project root.');
        }
        $parts = explode('/', $relative);
        if (in_array('.', $parts, true) || in_array('..', $parts, true) || in_array('', $parts, true)
            || strtolower(pathinfo($relative, PATHINFO_EXTENSION)) !== 'php') {
            throw new RuntimeException('Invalid PHP restore path.');
        }
        $destination = $this->root() . DIRECTORY_SEPARATOR . $relative;
        $this->assertNoLinks($destination);
        $parent = realpath(dirname($destination));
        if (!$parent || !$this->withinRoot($parent) || $this->excluded($destination) || (file_exists($destination) && !is_file($destination))) {
            throw new RuntimeException('Invalid restore destination.');
        }
        return $destination;
    }

    private function restorePathFromInput(string $path): string
    {
        $relative = str_starts_with($path, $this->root() . DIRECTORY_SEPARATOR)
            ? substr($path, strlen($this->root()) + 1) : $path;
        return $this->restorePath($relative);
    }

    private function sourcePath(string $path, bool $directory): string
    {
        $absolute = str_starts_with($path, '/') ? $path : base_path($path);
        $this->assertNoLinks($absolute);
        $resolved = realpath($absolute);
        if (!$resolved || !$this->withinRoot($resolved) || $this->excluded($resolved)
            || ($directory ? !is_dir($resolved) : (!is_file($resolved) || strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) !== 'php'))) {
            throw new RuntimeException('Source must be a regular ' . ($directory ? 'directory' : 'PHP file') . ' inside the project.');
        }
        return $resolved;
    }

    private function assertNoLinks(string $path): void
    {
        $root = $this->root();
        $candidate = str_starts_with($path, '/') ? $path : $root . DIRECTORY_SEPARATOR . $path;
        $parts = explode('/', trim($candidate, '/'));
        $current = '';
        foreach ($parts as $part) {
            $current .= '/' . $part;
            if (is_link($current)) {
                throw new RuntimeException('Symlink paths are not allowed: ' . $current);
            }
        }
    }

    private function excluded(string $path): bool
    {
        $root = $this->root();
        if (!str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            return false;
        }
        $first = explode(DIRECTORY_SEPARATOR, substr($path, strlen($root) + 1))[0];
        return in_array($first, ['vendor', 'M_obfuscate_backups'], true);
    }

    private function withinRoot(string $path): bool
    {
        return $path === $this->root() || str_starts_with($path, $this->root() . DIRECTORY_SEPARATOR);
    }

    private function root(): string
    {
        $root = realpath(base_path());
        if (!$root) {
            throw new RuntimeException('Project root does not exist.');
        }
        return $root;
    }

    private function backupDirectory(bool $create): string
    {
        $path = $this->root() . '/M_obfuscate_backups';
        if ($create && !file_exists($path) && !mkdir($path, 0755)) {
            throw new RuntimeException('Failed to create backup directory.');
        }
        if (is_link($path) || !is_dir($path) || realpath($path) !== $path) {
            throw new RuntimeException('Invalid backup directory.');
        }
        return $path;
    }

    private function temporaryPath(string $path): string
    {
        $temporary = tempnam(dirname($path), '.mObfs-');
        if ($temporary === false) {
            throw new RuntimeException('Failed to create temporary output.');
        }
        return $temporary;
    }

    private function jobLimit($jobs): int
    {
        if (!is_scalar($jobs) || !preg_match('/^(?:[1-9]|[12][0-9]|3[012])$/D', (string) $jobs)) {
            throw new RuntimeException('--jobs must be an integer from 1 to 32.');
        }
        return (int) $jobs;
    }

    private function enginePath()
    {
        $path = config('mObfs.mObfs_path');
        if ($path === base_path('vendor/pmdunggh/yakpro-po/yakpro-po.php')) {
            return base_path('vendor/douglas-fortunatus/yakpro-po/yakpro-po.php');
        }
        return $path;
    }
}
