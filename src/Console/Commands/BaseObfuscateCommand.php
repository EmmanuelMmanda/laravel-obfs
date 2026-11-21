<?php

namespace Mmanda\LaravelObfs\Console\Commands;

use Illuminate\Console\Command;
use Mmanda\LaravelObfs\Services\ObfuscationService;

abstract class BaseObfuscateCommand extends Command
{
    protected $obfuscationService;

    public function __construct(ObfuscationService $obfuscationService)
    {
        parent::__construct();
        $this->obfuscationService = $obfuscationService;
    }

    protected function obfuscateDirectory($directoryPath, $backup = false, $jobs = 4)
    {
        try {
            $skipped = $this->obfuscationService->obfuscateDirectory($directoryPath, $backup, $jobs);
            $this->info("Directory '$directoryPath' obfuscated successfully. Skipped {$skipped} symlinks.");
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    protected function obfuscateFile($filePath, $backup = false)
    {
        try {
            $this->obfuscationService->obfuscateFile($filePath, $backup);
            $this->info("File '$filePath' obfuscated successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
